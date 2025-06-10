<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{Permission, Role, User};

class ArticlePermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permission = Permission::create([
            'manage_admin' => false,
            'admin_access' => false,
            'manage_events' => false,
            'create_events' => false,
            'manage_members' => false,
            'manage_articles' => false,
            'create_articles' => true,
        ]);

        $contributorRole = Role::create([
            'name' => 'Contributeur',
            'permissions_id' => $permission->id,
        ]);

        $users = User::all();
        foreach ($users as $user) {
            if (!$user->hasRole('Admin') && !$user->hasRole('Animateur')) {
                $user->roles()->attach($contributorRole->id);
            }
        }
    }
}
