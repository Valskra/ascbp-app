<?php

// tests/Feature/Articles/ArticleLifecycleTest.php

use App\Models\{User, Article, Event, File, ArticleComment, ArticleLike};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

describe('Article Complete Lifecycle', function () {
    beforeEach(function () {
        Storage::fake('public');

        $this->author = User::factory()->create();
        $this->admin = User::factory()->create();
        $this->reader = User::factory()->create();

        // Donner les permissions admin
        $adminRole = \App\Models\Role::factory()->create([
            'name' => 'admin',
            'permissions' => ['admin_access' => 1]
        ]);
        $this->admin->roles()->attach($adminRole);

        $this->event = Event::factory()->create([
            'title' => 'Tournoi de Tennis',
            'organizer_id' => $this->author->id,
        ]);
    });

    it('completes full article creation workflow with image', function () {
        $image = UploadedFile::fake()->image('article.jpg', 800, 600);

        // 1. Création de l'article
        $response = $this->actingAs($this->author)
            ->post(route('articles.store'), [
                'title' => 'Mon super article sur le tennis',
                'excerpt' => 'Un résumé captivant',
                'content' => 'Contenu détaillé de l\'article avec beaucoup d\'informations.',
                'event_id' => $this->event->id,
                'status' => 'published',
                'publish_date' => now()->format('Y-m-d\TH:i'),
                'image' => $image,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Vérifier que l'article est créé
        $article = Article::where('title', 'Mon super article sur le tennis')->first();
        expect($article)->not->toBeNull();
        expect($article->user_id)->toBe($this->author->id);
        expect($article->event_id)->toBe($this->event->id);
        expect($article->status)->toBe('published');

        // Vérifier que l'image est uploadée
        expect($article->file_id)->not->toBeNull();
        Storage::disk('public')->assertExists('articles/' . basename($article->featuredImage->path));

        return $article;
    });

    it('handles article viewing and statistics', function () {
        $article = Article::factory()->create([
            'user_id' => $this->author->id,
            'event_id' => $this->event->id,
            'status' => 'published',
            'views_count' => 0,
        ]);

        // 1. Lecture de l'article (non connecté)
        $response = $this->get(route('articles.show', $article));
        $response->assertOk();

        // Vérifier incrément des vues
        expect($article->fresh()->views_count)->toBe(1);

        // 2. Lecture par utilisateur connecté
        $response = $this->actingAs($this->reader)
            ->get(route('articles.show', $article));
        $response->assertOk();
        expect($article->fresh()->views_count)->toBe(2);

        // 3. Vérifier les données retournées
        $response->assertInertia(
            fn($page) =>
            $page->component('Articles/Show')
                ->has('article.title')
                ->has('article.content')
                ->has('article.author')
                ->where('article.views_count', 2)
        );
    });

    it('processes article interactions (likes and comments)', function () {
        $article = Article::factory()->create([
            'user_id' => $this->author->id,
            'status' => 'published',
        ]);

        // 1. Ajouter un like
        $response = $this->actingAs($this->reader)
            ->post(route('articles.like', $article));

        $response->assertRedirect();
        expect($article->likes()->count())->toBe(1);
        expect($article->isLikedBy($this->reader))->toBeTrue();

        // 2. Ajouter un commentaire
        $response = $this->actingAs($this->reader)
            ->post(route('articles.comments.store', $article), [
                'content' => 'Excellent article ! Très informatif.',
            ]);

        $response->assertRedirect();
        expect($article->allComments()->count())->toBe(1);

        $comment = $article->allComments()->first();
        expect($comment->content)->toBe('Excellent article ! Très informatif.');
        expect($comment->user_id)->toBe($this->reader->id);

        // 3. Répondre au commentaire
        $response = $this->actingAs($this->author)
            ->post(route('articles.comments.store', $article), [
                'content' => 'Merci pour votre retour !',
                'parent_id' => $comment->id,
            ]);

        $response->assertRedirect();
        expect($article->allComments()->count())->toBe(2);

        $reply = ArticleComment::where('parent_id', $comment->id)->first();
        expect($reply->content)->toBe('Merci pour votre retour !');
        expect($reply->user_id)->toBe($this->author->id);

        // 4. Liker le commentaire
        $response = $this->actingAs($this->admin)
            ->post(route('articles.comments.like', $comment));

        $response->assertRedirect();
        expect($comment->likes()->count())->toBe(1);
    });

    it('handles article moderation workflow', function () {
        $article = Article::factory()->create([
            'user_id' => $this->reader->id, // Pas l'auteur
            'status' => 'published',
            'is_pinned' => false,
        ]);

        // 1. Admin épingle l'article
        $response = $this->actingAs($this->admin)
            ->post(route('articles.pin', $article));

        $response->assertRedirect();
        expect($article->fresh()->is_pinned)->toBeTrue();

        // 2. Admin modifie l'article
        $response = $this->actingAs($this->admin)
            ->put(route('articles.update', $article), [
                'title' => 'Titre modifié par admin',
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'status' => 'published',
                'publish_date' => $article->publish_date->format('Y-m-d\TH:i'),
            ]);

        $response->assertRedirect(route('articles.show', $article));
        expect($article->fresh()->title)->toBe('Titre modifié par admin');

        // 3. Admin modère un commentaire inapproprié
        $badComment = ArticleComment::factory()->create([
            'article_id' => $article->id,
            'user_id' => $this->reader->id,
            'content' => 'Commentaire inapproprié à supprimer',
        ]);

        $response = $this->actingAs($this->admin)
            ->delete(route('articles.comments.destroy', $badComment));

        $response->assertRedirect();
        $this->assertDatabaseMissing('article_comments', ['id' => $badComment->id]);
    });

    it('enforces permission restrictions correctly', function () {
        $userArticle = Article::factory()->create([
            'user_id' => $this->reader->id,
            'status' => 'published',
        ]);

        $otherUser = User::factory()->create();

        // 1. Utilisateur non-auteur ne peut pas modifier
        $response = $this->actingAs($otherUser)
            ->get(route('articles.edit', $userArticle));
        $response->assertForbidden();

        // 2. Utilisateur non-auteur ne peut pas supprimer
        $response = $this->actingAs($otherUser)
            ->delete(route('articles.destroy', $userArticle));
        $response->assertForbidden();

        // 3. Auteur peut modifier son article
        $response = $this->actingAs($this->reader)
            ->get(route('articles.edit', $userArticle));
        $response->assertOk();

        // 4. Admin peut tout faire
        $response = $this->actingAs($this->admin)
            ->get(route('articles.edit', $userArticle));
        $response->assertOk();
    });

    it('handles article deletion with cleanup', function () {
        $image = UploadedFile::fake()->image('to-delete.jpg');

        // Créer un article avec image et interactions
        $article = Article::factory()->create([
            'user_id' => $this->author->id,
            'status' => 'published',
        ]);

        // Ajouter image
        $this->actingAs($this->author)
            ->put(route('articles.update', $article), [
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'status' => 'published',
                'image' => $image,
            ]);

        $article = $article->fresh();
        $imagePath = $article->featuredImage->path;

        // Ajouter des likes et commentaires
        ArticleLike::factory()->create(['article_id' => $article->id, 'user_id' => $this->reader->id]);
        ArticleComment::factory()->create(['article_id' => $article->id, 'user_id' => $this->reader->id]);

        // Supprimer l'article
        $response = $this->actingAs($this->author)
            ->delete(route('articles.destroy', $article));

        $response->assertRedirect();

        // Vérifier nettoyage complet
        $this->assertDatabaseMissing('articles', ['id' => $article->id]);
        $this->assertDatabaseMissing('article_likes', ['article_id' => $article->id]);
        $this->assertDatabaseMissing('article_comments', ['article_id' => $article->id]);
        Storage::disk('public')->assertMissing($imagePath);
    });
});
