<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;

class CreateRolesAndPermissions extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer les permissions
        $ownerPermissions = Permission::firstOrCreate([
            'manage_admin' => 1,
            'admin_access' => 1,
            'manage_events' => 1,
            'create_events' => 1,
            'manage_members' => 1,
            'manage_articles' => 1,
            'create_articles' => 1,
        ]);

        $adminPermissions = Permission::firstOrCreate([
            'manage_admin' => 0,
            'admin_access' => 1,
            'manage_events' => 1,
            'create_events' => 1,
            'manage_members' => 1,
            'manage_articles' => 1,
            'create_articles' => 1,
        ]);

        $animatorPermissions = Permission::firstOrCreate([
            'manage_admin' => 0,
            'admin_access' => 0,
            'manage_events' => 1,
            'create_events' => 1,
            'manage_members' => 0,
            'manage_articles' => 1,
            'create_articles' => 1,
        ]);

        $moderatorPermissions = Permission::firstOrCreate([
            'manage_admin' => 0,
            'admin_access' => 0,
            'manage_events' => 0,
            'create_events' => 1,
            'manage_members' => 0,
            'manage_articles' => 1,
            'create_articles' => 1,
        ]);

        $memberPermissions = Permission::firstOrCreate([
            'manage_admin' => 0,
            'admin_access' => 0,
            'manage_events' => 0,
            'create_events' => 0,
            'manage_members' => 0,
            'manage_articles' => 0,
            'create_articles' => 0,
        ]);

        // Créer les rôles (sans display_name et description)
        Role::firstOrCreate(
            ['name' => 'owner'],
            ['permissions_id' => $ownerPermissions->id]
        );

        Role::firstOrCreate(
            ['name' => 'admin'],
            ['permissions_id' => $adminPermissions->id]
        );

        Role::firstOrCreate(
            ['name' => 'animator'],
            ['permissions_id' => $animatorPermissions->id]
        );

        Role::firstOrCreate(
            ['name' => 'moderator'],
            ['permissions_id' => $moderatorPermissions->id]
        );

        Role::firstOrCreate(
            ['name' => 'member'],
            ['permissions_id' => $memberPermissions->id]
        );

        $this->command->info('Rôles et permissions créés avec succès !');
    }
}
