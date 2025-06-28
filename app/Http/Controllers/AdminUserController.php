<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;
use App\Models\{Permission, Role, User};


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
            ->with(['memberships', 'roles.permissions'])
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
                'is_admin' => $user->is_admin,
                'is_animator' => $user->is_animator,
                'roles' => $user->roles->map(fn($role) => [
                    'id' => $role->id,
                    'name' => $role->name,
                ])
            ])
            ->withQueryString();

        // Récupérer tous les rôles uniques (éviter les doublons par nom)
        $roles = Role::with('permissions')
            ->get()
            ->unique('name') // Éliminer les doublons par nom
            ->map(fn($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'display_name' => $this->getRoleDisplayName($role->name),
                'description' => $this->getRoleDescription($role->name),
                'permissions' => $role->permissions ? [
                    'manage_admin' => (bool) $role->permissions->manage_admin,
                    'admin_access' => (bool) $role->permissions->admin_access,
                    'manage_events' => (bool) $role->permissions->manage_events,
                    'create_events' => (bool) $role->permissions->create_events,
                    'manage_members' => (bool) $role->permissions->manage_members,
                    'manage_articles' => (bool) $role->permissions->manage_articles,
                    'create_articles' => (bool) $role->permissions->create_articles,
                ] : []
            ])
            ->values(); // Réindexer le tableau

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
            'roles' => $roles,
            'stats' => $stats,
            'filters' => $request->only('search'),
        ]);
    }

    public function createRole(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'display_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'permissions' => 'array',
            'permissions.manage_admin' => 'boolean',
            'permissions.admin_access' => 'boolean',
            'permissions.manage_events' => 'boolean',
            'permissions.create_events' => 'boolean',
            'permissions.manage_members' => 'boolean',
            'permissions.manage_articles' => 'boolean',
            'permissions.create_articles' => 'boolean',
        ]);

        $currentUser = $request->user();

        // Vérifier les permissions pour créer des rôles
        if (!$this->isOwner($currentUser)) {
            return back()->withErrors(['error' => 'Seul le propriétaire peut créer des rôles.']);
        }

        DB::beginTransaction();

        try {
            // Créer les permissions
            $permissions = Permission::create([
                'manage_admin' => $request->input('permissions.manage_admin', false),
                'admin_access' => $request->input('permissions.admin_access', false),
                'manage_events' => $request->input('permissions.manage_events', false),
                'create_events' => $request->input('permissions.create_events', false),
                'manage_members' => $request->input('permissions.manage_members', false),
                'manage_articles' => $request->input('permissions.manage_articles', false),
                'create_articles' => $request->input('permissions.create_articles', false),
            ]);

            // Créer le rôle (seulement avec les colonnes qui existent)
            Role::create([
                'name' => $request->input('name'),
                'permissions_id' => $permissions->id,
            ]);

            DB::commit();

            return redirect()->route('admin.users')->with('success', 'Rôle créé avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la création du rôle: ' . $e->getMessage()]);
        }
    }

    public function cleanupDuplicateRoles()
    {
        // Méthode utilitaire pour nettoyer les doublons
        $duplicateGroups = Role::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        foreach ($duplicateGroups as $roleName) {
            $roles = Role::where('name', $roleName)->orderBy('id')->get();

            // Garder le premier, supprimer les autres
            $keepRole = $roles->first();
            $duplicates = $roles->skip(1);

            foreach ($duplicates as $duplicate) {
                // Transférer les utilisateurs vers le rôle à garder
                $duplicate->users()->sync([]);
                $duplicate->delete();
            }
        }
    }

    public function updateRoles(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'role_ids' => 'array',
            'role_ids.*' => 'exists:roles,id'
        ]);

        $currentUser = $request->user();
        $targetUser = User::with('roles')->findOrFail($request->user_id);

        // Vérifier que l'utilisateur ne peut pas se modifier lui-même
        if ($currentUser->id === $targetUser->id) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas modifier vos propres permissions.']);
        }

        // Vérifier les permissions pour chaque rôle
        $roleIds = $request->input('role_ids', []);
        $roles = Role::whereIn('id', $roleIds)->get();

        foreach ($roles as $role) {
            if (!$this->canModifyRole($currentUser, $role)) {
                return back()->withErrors(['error' => "Vous n'avez pas les permissions pour assigner le rôle '{$this->getRoleDisplayName($role->name)}'.'"]);
            }
        }

        DB::beginTransaction();

        try {
            // Retirer tous les rôles existants
            $targetUser->roles()->detach();

            // Assigner les nouveaux rôles
            if (!empty($roleIds)) {
                $targetUser->roles()->attach($roleIds);
            }

            DB::commit();

            return back()->with('success', 'Rôles mis à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour des rôles: ' . $e->getMessage()]);
        }
    }

    private function canModifyRole(User $currentUser, Role $role): bool
    {
        // L'owner peut tout modifier
        if ($this->isOwner($currentUser)) {
            return true;
        }

        // L'admin peut modifier tous les rôles sauf admin et owner
        if ($currentUser->is_admin) {
            return !in_array($role->name, ['admin', 'owner']);
        }

        return false;
    }

    private function isOwner(User $user): bool
    {
        return $user->hasRole('owner') || $user->hasPermission('manage_admin');
    }
    private function getRoleDisplayName(string $roleName): string
    {
        return match ($roleName) {
            'owner' => 'Propriétaire',
            'admin' => 'Administrateur',
            'animator' => 'Animateur',
            'moderator' => 'Modérateur',
            'member' => 'Membre',
            default => ucfirst($roleName),
        };
    }

    private function getRoleDescription(string $roleName): string
    {
        return match ($roleName) {
            'owner' => 'Propriétaire avec tous les droits',
            'admin' => 'Administrateur avec droits étendus',
            'animator' => 'Animateur d\'événements et d\'articles',
            'moderator' => 'Modérateur de contenu',
            'member' => 'Membre standard',
            default => "Rôle {$roleName}",
        };
    }

    public function updateRole(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'action' => 'required|in:promote_admin,promote_animator,demote'
        ]);

        $currentUser = $request->user();
        $targetUser = User::with('roles')->findOrFail($request->user_id);

        // Vérifier que l'utilisateur ne peut pas se modifier lui-même
        if ($currentUser->id === $targetUser->id) {
            return back()->withErrors(['error' => 'Vous ne pouvez pas modifier vos propres permissions.']);
        }

        DB::beginTransaction();

        try {
            switch ($request->action) {
                case 'promote_admin':
                    $this->promoteToAdmin($currentUser, $targetUser);
                    break;
                case 'promote_animator':
                    $this->promoteToAnimator($currentUser, $targetUser);
                    break;
                case 'demote':
                    $this->demoteUser($currentUser, $targetUser);
                    break;
            }

            DB::commit();

            return back()->with('success', 'Permissions mises à jour avec succès.');
        } catch (\Exception $e) {
            DB::rollback();
            return back()->withErrors(['error' => 'Erreur lors de la mise à jour des permissions: ' . $e->getMessage()]);
        }
    }

    private function promoteToAdmin(User $currentUser, User $targetUser)
    {
        // Seul l'owner peut promouvoir des admins
        if (!$this->isOwner($currentUser)) {
            throw new \Exception('Seul le propriétaire peut nommer des administrateurs.');
        }

        // Retirer tous les rôles existants
        $targetUser->roles()->detach();

        // Assigner le rôle admin
        $adminRole = Role::where('name', 'admin')->first();
        if (!$adminRole) {
            throw new \Exception('Rôle administrateur non trouvé.');
        }

        $targetUser->roles()->attach($adminRole->id);
    }

    private function promoteToAnimator(User $currentUser, User $targetUser)
    {
        // L'admin ou l'owner peut promouvoir des animateurs
        if (!$currentUser->is_admin && !$this->isOwner($currentUser)) {
            throw new \Exception('Permissions insuffisantes pour nommer des animateurs.');
        }

        // Si l'utilisateur est déjà admin, ne rien faire
        if ($targetUser->is_admin) {
            throw new \Exception('Cet utilisateur est déjà administrateur.');
        }

        // Retirer les rôles existants
        $targetUser->roles()->detach();

        // Assigner le rôle animateur
        $animatorRole = Role::where('name', 'animator')->first();
        if (!$animatorRole) {
            throw new \Exception('Rôle animateur non trouvé.');
        }

        $targetUser->roles()->attach($animatorRole->id);
    }

    private function demoteUser(User $currentUser, User $targetUser)
    {
        if ($targetUser->is_admin) {
            // Seul l'owner peut dégrader un admin
            if (!$this->isOwner($currentUser)) {
                throw new \Exception('Seul le propriétaire peut dégrader un administrateur.');
            }
        } elseif ($targetUser->is_animator) {
            // L'admin ou l'owner peut dégrader un animateur
            if (!$currentUser->is_admin && !$this->isOwner($currentUser)) {
                throw new \Exception('Permissions insuffisantes pour dégrader cet animateur.');
            }
        } else {
            throw new \Exception('Cet utilisateur n\'a pas de rôle spécial à retirer.');
        }

        // Retirer tous les rôles spéciaux
        $targetUser->roles()->detach();

        // Optionnel : assigner explicitement le rôle membre de base
        $memberRole = Role::where('name', 'member')->first();
        if ($memberRole) {
            $targetUser->roles()->attach($memberRole->id);
        }
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
                'Statut',
                'Rôle'
            ]);

            $users = User::with(['memberships', 'roles'])->orderBy('id')->get();

            foreach ($users as $user) {
                $statusLabel = match ($user->membership_status) {
                    -1 => 'Désactivé',
                    0 => 'Ancien > 1 an ou jamais adhérent',
                    1 => 'Adhérent actif',
                    2 => 'Ancien < 1 an',
                    default => 'Inconnu',
                };

                $roleLabel = 'Membre';
                if ($user->is_admin) {
                    $roleLabel = 'Admin';
                } elseif ($user->is_animator) {
                    $roleLabel = 'Animateur';
                }

                fputcsv($handle, [
                    $user->id,
                    $user->firstname,
                    $user->lastname,
                    $user->birth_date,
                    $user->postal_code,
                    $user->phone,
                    $user->email,
                    $statusLabel,
                    $roleLabel
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }
}
