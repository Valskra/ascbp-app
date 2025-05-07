<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {

        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%");
            });
        }

        $users = $query
            ->with(['memberships'])
            ->orderBy('id', 'asc')
            ->paginate(25)
            ->through(fn($user) => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'birth_date' => $user->birth_date,
                'postal_code' => $user->postal_code,
                'phone' => $user->phone,
                'email' => $user->email,
                'status' => $user->status,
                'membership_status' => $user->membership_status,
                'membership_expires_at' => $user->membership_expires_at?->toDateString(),
            ])
            ->withQueryString();

        $allUsers = User::with('memberships')->get();
        $stats = [
            'active' => 0,
            'expired_recently' => 0,
            'expired_long' => 0,
            'disabled' => 0,
            'total' => $allUsers->count(),
        ];

        foreach ($allUsers as $user) {
            if ($user->status === 'disabled' || $user->status === 'banned') {
                $stats['disabled']++;
                continue;
            }

            $status = $user->membership_status;

            if ($status === 1) {
                $stats['active']++;
            } elseif ($status === 2) {
                $stats['expired_recently']++;
            } elseif ($status === 0) {
                $stats['expired_long']++;
            }
        }

        return Inertia::render('Admin/ManageUsers', [
            'users' => $users,
            'stats' => $stats,
            'filters' => $request->only('search'),
        ]);
    }

    public function export()
    {
        abort_unless(Gate::allows('admin_access'), 403);

        $fileName = 'users_export_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Prénom',
                'Nom',
                'Date de naissance',
                'Code Postal',
                'Téléphone',
                'Email',
                'Statut'
            ]);

            $users = User::with('memberships')->orderBy('id')->get();

            foreach ($users as $user) {
                $statusLabel = match ($user->membership_status) {
                    -1 => 'Désactivé',
                    0 => 'Ancien > 1 an ou jamais adhérent',
                    1 => 'Adhérent actif',
                    2 => 'Ancien < 1 an',
                    default => 'Inconnu',
                };

                fputcsv($handle, [
                    $user->id,
                    $user->firstname,
                    $user->lastname,
                    $user->birth_date,
                    $user->postal_code,
                    $user->phone,
                    $user->email,
                    $statusLabel
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
