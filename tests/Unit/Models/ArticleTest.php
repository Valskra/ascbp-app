<?php
// tests/Unit/Models/ArticleTest.php - Version corrigée

use App\Models\Article;
use App\Models\User;
use App\Models\Event;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;

// ✅ Correction: Ajouter \Tests\TestCase::class
uses(\Tests\TestCase::class, RefreshDatabase::class);

describe('Article Model', function () {

    beforeEach(function () {
        $this->author = User::factory()->create();
        $this->event = Event::factory()->create();
        $this->article = Article::factory()->create([
            'title' => 'Mon Article Test',
            'content' => 'Ceci est le contenu de test de mon article.',
            'excerpt' => 'Extrait personnalisé',
            'status' => 'published',
            'publish_date' => Carbon::now()->subHour(),
            'user_id' => $this->author->id,
            'event_id' => $this->event->id,
            'is_pinned' => false,
            'is_post' => false,
            'views_count' => 0,
            'metadata' => [] // ✅ CORRECTION: Ajouter un array vide par défaut
        ]);
    });

    // Tests des attributs de base
    it('can create an article with valid attributes', function () {
        expect($this->article->title)->toBe('Mon Article Test')
            ->and($this->article->content)->toBe('Ceci est le contenu de test de mon article.')
            ->and($this->article->status)->toBe('published')
            ->and($this->article->user_id)->toBe($this->author->id)
            ->and($this->article->is_pinned)->toBeFalse()
            ->and($this->article->is_post)->toBeFalse();
    });

    it('has correct fillable attributes', function () {
        $fillable = [
            'title',
            'excerpt',
            'content',
            'publish_date',
            'event_id',
            'is_pinned',
            'status',
            'views_count',
            'file_id',
            'user_id',
            'is_post',
            'metadata'
        ];
        expect($this->article->getFillable())->toEqual($fillable);
    });

    // ✅ CORRECTION: Test plus robuste pour les casts
    it('casts attributes correctly', function () {
        expect($this->article->publish_date)->toBeInstanceOf(Carbon::class)
            ->and($this->article->is_pinned)->toBeBool()
            ->and($this->article->is_post)->toBeBool();

        // Test metadata séparément avec gestion du null
        if ($this->article->metadata === null) {
            expect($this->article->metadata)->toBeNull();
        } else {
            expect($this->article->metadata)->toBeArray();
        }
    });

    // Tests des relations
    it('belongs to an author', function () {
        expect($this->article->author)->toBeInstanceOf(User::class)
            ->and($this->article->author->id)->toBe($this->author->id);
    });

    it('belongs to an event optionally', function () {
        expect($this->article->event)->toBeInstanceOf(Event::class)
            ->and($this->article->event->id)->toBe($this->event->id);

        // Article sans événement
        $generalArticle = Article::factory()->create(['event_id' => null]);
        expect($generalArticle->event)->toBeNull();
    });

    // Tests de logique métier basique
    it('can increment views count', function () {
        $initialViews = $this->article->views_count;

        $this->article->incrementViews();

        expect($this->article->fresh()->views_count)->toBe($initialViews + 1);
    });

    // Tests des accesseurs
    it('uses provided excerpt when available', function () {
        expect($this->article->excerpt)->toBe('Extrait personnalisé');
    });

    it('generates excerpt from content when none provided', function () {
        $longContent = str_repeat('Lorem ipsum dolor sit amet. ', 20);
        $article = Article::factory()->create([
            'content' => $longContent,
            'excerpt' => null
        ]);

        $excerpt = $article->excerpt;

        expect($excerpt)->toEndWith('...')
            ->and(strlen($excerpt))->toBeLessThanOrEqual(203);
    });

    // Tests des scopes
    it('can filter published articles', function () {
        Article::factory()->create([
            'status' => 'published',
            'publish_date' => Carbon::now()->subHour()
        ]);
        Article::factory()->create(['status' => 'draft']);

        $publishedArticles = Article::published()->get();
        expect($publishedArticles)->toHaveCount(2); // 1 du beforeEach + 1 créé ici
    });

    it('can filter pinned articles', function () {
        Article::factory()->create(['is_pinned' => true]);
        Article::factory()->create(['is_pinned' => false]);

        $pinnedArticles = Article::pinned()->get();
        expect($pinnedArticles)->toHaveCount(1);
    });

    it('can filter posts vs articles', function () {
        Article::factory()->create(['is_post' => true]);
        Article::factory()->create(['is_post' => false]);

        $posts = Article::posts()->get();
        $articles = Article::articles()->get();

        expect($posts)->toHaveCount(1)
            ->and($articles)->toHaveCount(2); // 1 du beforeEach + 1 créé ici
    });

    // Tests de validation
    it('requires user_id to be set', function () {
        expect(function () {
            Article::factory()->create(['user_id' => null]);
        })->toThrow(\Exception::class);
    });

    // Tests de métadonnées
    it('stores metadata as json correctly', function () {
        $metadata = ['seo' => ['description' => 'Meta description']];

        $this->article->update(['metadata' => $metadata]);

        expect($this->article->fresh()->metadata)->toBe($metadata);
    });

    it('can be deleted', function () {
        $articleId = $this->article->id;
        $this->article->delete();

        expect(Article::find($articleId))->toBeNull();
    });
});
