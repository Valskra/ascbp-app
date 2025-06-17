<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Permission, Role};

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Admin - Tous les droits
        $adminPermission = Permission::create([
            'manage_admin' => true,
            'admin_access' => true,
            'manage_events' => true,
            'create_events' => true,
            'manage_members' => true,
            'manage_articles' => true,
            'create_articles' => true,
        ]);

        Role::create([
            'name' => 'Admin',
            'permissions_id' => $adminPermission->id,
        ]);

        // Animateur - Gestion événements et articles
        $animatorPermission = Permission::create([
            'manage_admin' => false,
            'admin_access' => false,
            'manage_events' => true,
            'create_events' => true,
            'manage_members' => false,
            'manage_articles' => true,
            'create_articles' => true,
        ]);

        Role::create([
            'name' => 'Animateur',
            'permissions_id' => $animatorPermission->id,
        ]);

        // Contributeur - Création d'articles uniquement
        $contributorPermission = Permission::create([
            'manage_admin' => false,
            'admin_access' => false,
            'manage_events' => false,
            'create_events' => false,
            'manage_members' => false,
            'manage_articles' => false,
            'create_articles' => true,
        ]);

        Role::create([
            'name' => 'Contributeur',
            'permissions_id' => $contributorPermission->id,
        ]);

        // Membre - Aucun droit spécial
        $memberPermission = Permission::create([
            'manage_admin' => false,
            'admin_access' => false,
            'manage_events' => false,
            'create_events' => false,
            'manage_members' => false,
            'manage_articles' => false,
            'create_articles' => false,
        ]);

        Role::create([
            'name' => 'Membre',
            'permissions_id' => $memberPermission->id,
        ]);
    }
}
