<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\{
    User,
    Role,
    Event,
    Article,
    Registration,
    Address,
    Contact,
    Membership,
    EventAddress,
    ArticleComment,
    Document
};

class CompleteSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('🚀 Début du seeding complet...');

        // 1. Créer les rôles et permissions
        $this->call(RolePermissionSeeder::class);
        $this->command->info('✅ Rôles et permissions créés');

        // 2. Créer les utilisateurs (50 au total)
        $users = $this->createUsers();
        $this->command->info('✅ ' . $users->count() . ' utilisateurs créés');

        // 3. Créer les événements (20 au total)
        $events = $this->createEvents($users);
        $this->command->info('✅ ' . $events->count() . ' événements créés');

        // 4. Créer les articles (30 au total)
        $articles = $this->createArticles($users, $events);
        $this->command->info('✅ ' . $articles->count() . ' articles créés');

        // 5. Créer des inscriptions aux événements
        $this->createRegistrations($users, $events);
        $this->command->info('✅ Inscriptions aux événements créées');

        // 6. Créer quelques commentaires
        $this->createComments($users, $articles);
        $this->command->info('✅ Commentaires d\'articles créés');

        $this->command->info('🎉 Seeding terminé avec succès !');
        $this->printSummary($users, $events, $articles);
    }

    private function createUsers()
    {
        $users = collect();
        $roles = Role::all()->keyBy('name');

        // 1 Admin
        $admin = User::factory()->create([
            'firstname' => 'Alexandre',
            'lastname' => 'Martin',
            'email' => 'admin@ascbp.fr',
        ]);
        $admin->roles()->attach($roles['Admin']);
        $this->createUserData($admin, true); // Avec adhésion active
        $users->push($admin);

        // 4 Animateurs
        for ($i = 1; $i <= 4; $i++) {
            $animator = User::factory()->create();
            $animator->roles()->attach($roles['Animateur']);
            $this->createUserData($animator, $i <= 2); // 2 sur 4 avec adhésion active
            $users->push($animator);
        }

        // 15 Contributeurs
        for ($i = 1; $i <= 15; $i++) {
            $contributor = User::factory()->create();
            $contributor->roles()->attach($roles['Contributeur']);
            $this->createUserData($contributor, $i <= 3); // 3 sur 15 avec adhésion active
            $users->push($contributor);
        }

        // 30 Membres simples
        for ($i = 1; $i <= 30; $i++) {
            $member = User::factory()->create();
            $member->roles()->attach($roles['Membre']);
            $this->createUserData($member, $i <= 5); // 5 sur 30 avec adhésion active
            $users->push($member);
        }

        return $users;
    }

    private function createUserData(User $user, bool $hasActiveMembership = false)
    {
        // 70% des utilisateurs ont une adresse domicile
        if (rand(1, 10) <= 7) {
            Address::factory()->home()->create(['user_id' => $user->id]);
        }

        // 50% ont une adresse de naissance
        if (rand(1, 10) <= 5) {
            Address::factory()->birth()->create(['user_id' => $user->id]);
        }

        // 60% ont des contacts
        if (rand(1, 10) <= 6) {
            Contact::factory(rand(1, 3))->create(['user_id' => $user->id]);
        }

        // Adhésions selon le paramètre
        if ($hasActiveMembership) {
            Membership::factory()->active()->create(['user_id' => $user->id]);
        } else {
            // 30% ont une ancienne adhésion expirée
            if (rand(1, 10) <= 3) {
                Membership::factory()->expired()->create(['user_id' => $user->id]);
            }
        }

        // 20% ont des documents - DÉSACTIVÉ temporairement
        // if (rand(1, 10) <= 2) {
        //     Document::factory(rand(1, 2))->create(['user_id' => $user->id]);
        // }
    }

    private function createEvents($users)
    {
        $events = collect();

        // Récupérer les utilisateurs avec permissions pour organiser
        $organizers = $users->filter(function ($user) {
            $roleName = $user->roles->first()->name ?? '';
            return in_array($roleName, ['Admin', 'Animateur']);
        });

        // Si pas d'organisateurs, prendre les premiers utilisateurs
        if ($organizers->isEmpty()) {
            $organizers = $users->take(5);
        }

        // Créer 20 événements
        for ($i = 1; $i <= 20; $i++) {
            $event = Event::factory()->create([
                'organizer_id' => $organizers->random()->id
            ]);

            // Créer une adresse pour l'événement
            EventAddress::factory()->create(['event_id' => $event->id]);

            $events->push($event);
        }

        return $events;
    }

    private function createArticles($users, $events)
    {
        $articles = collect();

        // Récupérer les auteurs potentiels
        $authors = $users->filter(function ($user) {
            $roleName = $user->roles->first()->name ?? '';
            return in_array($roleName, ['Admin', 'Animateur', 'Contributeur']);
        });

        // Si pas d'auteurs, prendre tous les utilisateurs
        if ($authors->isEmpty()) {
            $authors = $users;
        }

        // 25 articles normaux
        for ($i = 1; $i <= 25; $i++) {
            $article = Article::factory()->create([
                'user_id' => $authors->random()->id,
                'event_id' => $i <= 8 ? $events->random()->id : null, // 8 articles liés à des événements
            ]);
            $articles->push($article);
        }

        // 5 posts courts
        for ($i = 1; $i <= 5; $i++) {
            $article = Article::factory()->post()->create([
                'user_id' => $authors->random()->id,
            ]);
            $articles->push($article);
        }

        return $articles;
    }

    private function createRegistrations($users, $events)
    {
        // Pour chaque événement, inscrire entre 3 et 15 participants
        $events->each(function ($event) use ($users) {
            $maxParticipants = $event->max_participants ?? 15;
            $participantCount = rand(3, min(15, $maxParticipants));
            $participants = $users->random(min($participantCount, $users->count()));

            $participants->each(function ($user) use ($event) {
                // Créer l'inscription directement (on ignore les validations pour le seeding)
                Registration::factory()->create([
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'amount' => $this->extractNumericPrice($event->price),
                ]);
            });
        });
    }

    private function extractNumericPrice($price)
    {
        if (!$price || $price === 'Gratuit') {
            return 0;
        }

        preg_match('/\d+/', $price, $matches);
        return isset($matches[0]) ? (float) $matches[0] : 0;
    }

    private function createComments($users, $articles)
    {
        // Créer des commentaires sur 60% des articles
        $articlesWithComments = $articles->random(max(1, (int)($articles->count() * 0.6)));

        $articlesWithComments->each(function ($article) use ($users) {
            $commentCount = rand(1, 5);

            for ($i = 0; $i < $commentCount; $i++) {
                $comment = ArticleComment::factory()->create([
                    'article_id' => $article->id,
                    'user_id' => $users->random()->id,
                ]);

                // 30% de chance d'avoir une réponse
                if (rand(1, 10) <= 3) {
                    ArticleComment::factory()->create([
                        'article_id' => $article->id,
                        'parent_id' => $comment->id,
                        'user_id' => $users->random()->id,
                        'content' => $this->getReplyContent(),
                    ]);
                }
            }
        });
    }

    private function getReplyContent()
    {
        $replies = [
            'Merci pour votre retour !',
            'Je suis d\'accord avec vous.',
            'Effectivement, c\'est un point important.',
            'Vous avez tout à fait raison.',
            'Merci de votre intérêt !',
            'N\'hésitez pas si vous avez des questions.',
            'Content que cela vous plaise !',
            'Nous restons à votre disposition.',
        ];

        return $replies[array_rand($replies)];
    }

    private function printSummary($users, $events, $articles)
    {
        $this->command->info("\n📊 RÉSUMÉ DU SEEDING:");
        $this->command->info("👥 Utilisateurs: " . $users->count());

        $usersByRole = $users->groupBy(function ($user) {
            return $user->roles->first()->name ?? 'Sans rôle';
        });

        foreach ($usersByRole as $role => $roleUsers) {
            $this->command->info("   - {$role}: " . $roleUsers->count());
        }

        $activeMembers = $users->filter(function ($user) {
            return $user->membership_status === 1;
        })->count();

        $this->command->info("   - Adhérents actifs: {$activeMembers} (" . round(($activeMembers / $users->count()) * 100, 1) . "%)");

        $this->command->info("📅 Événements: " . $events->count());
        $eventsByCategory = $events->groupBy('category');
        foreach ($eventsByCategory as $category => $categoryEvents) {
            $this->command->info("   - {$category}: " . $categoryEvents->count());
        }

        $this->command->info("📰 Articles: " . $articles->count());
        $publishedCount = $articles->where('status', 'published')->count();
        $postsCount = $articles->where('is_post', true)->count();
        $pinnedCount = $articles->where('is_pinned', true)->count();

        $this->command->info("   - Publiés: {$publishedCount}");
        $this->command->info("   - Posts courts: {$postsCount}");
        $this->command->info("   - Épinglés: {$pinnedCount}");

        $totalRegistrations = Registration::count();
        $this->command->info("📝 Inscriptions aux événements: {$totalRegistrations}");

        $totalComments = ArticleComment::count();
        $this->command->info("💬 Commentaires d'articles: {$totalComments}");

        $this->command->info("\n🔐 COMPTES DE TEST:");
        $this->command->info("Admin: admin@ascbp.fr / password");
        $this->command->info("Tous les comptes: password");
        $this->command->info("\n🎯 Base de données prête pour les tests !");
    }
}
