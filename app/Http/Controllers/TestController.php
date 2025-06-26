<?php
// app/Http/Controllers/TestController.php - Routes pour les tests E2E

namespace App\Http\Controllers;

use App\Models\{User, Event, Article, Role};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{DB, Hash};

class TestController extends Controller
{
    public function resetDatabase()
    {
        if (!app()->environment('testing')) {
            abort(403, 'Test routes only available in testing environment');
        }

        DB::statement('SET FOREIGN_KEY_CHECKS=0');
        DB::table('users')->truncate();
        DB::table('events')->truncate();
        DB::table('articles')->truncate();
        DB::table('registrations')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1');

        return response()->json(['message' => 'Database reset']);
    }

    public function seedTestData()
    {
        // Créer les rôles de base
        Role::firstOrCreate(['name' => 'admin'], [
            'permissions' => [
                'admin_access' => 1,
                'manage_events' => 1,
                'create_articles' => 1,
            ]
        ]);

        Role::firstOrCreate(['name' => 'member'], [
            'permissions' => [
                'admin_access' => 0,
                'create_articles' => 1,
            ]
        ]);

        // Créer un utilisateur de test standard
        User::firstOrCreate(['email' => 'test@example.com'], [
            'firstname' => 'Test',
            'lastname' => 'User',
            'password' => Hash::make('password'),
            'birth_date' => '1990-01-01',
            'phone' => '0123456789',
            'email_verified_at' => now(),
        ]);

        return response()->json(['message' => 'Test data seeded']);
    }

    public function createAdminUser()
    {
        $admin = User::firstOrCreate(['email' => 'admin@example.com'], [
            'firstname' => 'Admin',
            'lastname' => 'User',
            'password' => Hash::make('password'),
            'email_verified_at' => now(),
        ]);

        $adminRole = Role::where('name', 'admin')->first();
        $admin->roles()->syncWithoutDetaching([$adminRole->id]);

        return response()->json(['user' => $admin]);
    }

    public function createUser(Request $request)
    {
        $user = User::create([
            'firstname' => $request->firstname ?? 'Test',
            'lastname' => $request->lastname ?? 'User',
            'email' => $request->email ?? 'test@example.com',
            'password' => Hash::make($request->password ?? 'password'),
            'birth_date' => $request->birth_date ?? '1990-01-01',
            'phone' => $request->phone ?? '0123456789',
            'email_verified_at' => now(),
        ]);

        return response()->json($user);
    }

    public function createEvent(Request $request)
    {
        $event = Event::create([
            'title' => $request->title ?? 'Test Event',
            'category' => $request->category ?? 'competition',
            'description' => $request->description ?? 'Test event description',
            'start_date' => $request->start_date ?? now()->addDays(7),
            'end_date' => $request->end_date ?? now()->addDays(7)->addHours(3),
            'registration_open' => $request->registration_open ?? now()->subDays(1),
            'registration_close' => $request->registration_close ?? now()->addDays(6),
            'max_participants' => $request->max_participants ?? 20,
            'price' => $request->price ?? null,
            'organizer_id' => $request->organizer_id ?? 1,
        ]);

        return response()->json($event);
    }

    public function createArticle(Request $request)
    {
        $article = Article::create([
            'title' => $request->title ?? 'Test Article',
            'content' => $request->content ?? 'Test article content',
            'excerpt' => $request->excerpt ?? 'Test excerpt',
            'status' => $request->status ?? 'published',
            'publish_date' => $request->publish_date ?? now(),
            'user_id' => $request->user_id ?? 1,
            'is_pinned' => $request->is_pinned ?? false,
        ]);

        return response()->json($article);
    }

    public function healthCheck()
    {
        return response()->json([
            'status' => 'ok',
            'database' => DB::connection()->getPdo() ? 'connected' : 'disconnected',
            'users_count' => User::count(),
        ]);
    }
}
