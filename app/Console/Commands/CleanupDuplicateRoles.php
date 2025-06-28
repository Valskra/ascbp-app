<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class CleanupDuplicateRoles extends Command
{
    protected $signature = 'roles:cleanup';
    protected $description = 'Nettoie les rôles en doublon';

    public function handle()
    {
        $this->info('Nettoyage des rôles en doublon...');

        $duplicateGroups = Role::select('name')
            ->groupBy('name')
            ->havingRaw('COUNT(*) > 1')
            ->pluck('name');

        if ($duplicateGroups->isEmpty()) {
            $this->info('Aucun doublon trouvé.');
            return;
        }

        $totalDeleted = 0;

        foreach ($duplicateGroups as $roleName) {
            $roles = Role::where('name', $roleName)->orderBy('id')->get();

            $this->line("Traitement du rôle: {$roleName} ({$roles->count()} doublons)");

            // Garder le premier, supprimer les autres
            $keepRole = $roles->first();
            $duplicates = $roles->skip(1);

            foreach ($duplicates as $duplicate) {
                // Transférer les utilisateurs vers le rôle à garder
                $userCount = $duplicate->users()->count();
                if ($userCount > 0) {
                    $this->line("  Transfert de {$userCount} utilisateur(s) de l'ID {$duplicate->id} vers l'ID {$keepRole->id}");

                    // Récupérer les IDs des utilisateurs
                    $userIds = $duplicate->users()->pluck('user_id')->toArray();

                    // Les attacher au rôle à garder (éviter les doublons)
                    foreach ($userIds as $userId) {
                        DB::table('user_roles')->updateOrInsert(
                            ['user_id' => $userId, 'role_id' => $keepRole->id],
                            ['user_id' => $userId, 'role_id' => $keepRole->id]
                        );
                    }

                    // Détacher du rôle dupliqué
                    $duplicate->users()->detach();
                }

                $this->line("  Suppression du rôle dupliqué ID: {$duplicate->id}");
                $duplicate->delete();
                $totalDeleted++;
            }
        }

        $this->info("Nettoyage terminé. {$totalDeleted} rôle(s) en doublon supprimé(s).");
    }
}
