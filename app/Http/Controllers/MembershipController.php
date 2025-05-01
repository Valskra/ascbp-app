<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Membership;
use Inertia\Inertia;
use Inertia\Response;

use function Illuminate\Log\log;

class MembershipController extends Controller
{
    public function create(Request $request)
    {
        $user = $request->user();

        return Inertia::render('Membership/Create', [
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'membership' => $user->memberships()->latest('contribution_date')->first(),
                'has_membership' => $user->hasMembership(),
                'membership_status' => $user->membership_status,
            ],
            'time_since_join' => $user->getTimeToScreen($user->timeSinceJoin()),
            'time_left' => $user->getTimeToScreen($user->timeLeft()),
        ]);
    }




    public function store(Request $request)
    {
        $user = $request->user();
        $year = now()->year;

        $alreadyMember = $user->memberships()->where('year', $year)->exists();

        if ($alreadyMember) {
            return back()->with('error', 'Vous avez déjà adhéré pour cette année.');
        }

        $user->memberships()->create([
            'year' => $year,
            'contribution_date' => now()->toDateString(),
            'amount' => 0, // montant temporaire (à changer plus tard)
            'metadata' => null,
        ]);

        return back()->with('success', 'Votre adhésion a bien été enregistrée.');
    }
}
