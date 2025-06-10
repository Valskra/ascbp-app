<?php

namespace App\Http\Controllers;

use App\Models\{Article, Event, File, ArticleComment, ArticleLike};
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ArticleController extends Controller
{
    /**
     * Afficher tous les articles (page principale)
     */
    public function index(Request $request)
    {
        $query = Article::published()
            ->general()
            ->with(['author', 'featuredImage', 'likes', 'allComments'])
            ->withCount(['likes', 'allComments']);

        // Filtres
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->search . '%')
                    ->orWhere('content', 'LIKE', '%' . $request->search . '%');
            });
        }

        // Tri
        $sortBy = $request->get('sort', 'recent');
        switch ($sortBy) {
            case 'popular':
                $query->orderByDesc('likes_count')->orderByDesc('publish_date');
                break;
            case 'commented':
                $query->orderByDesc('all_comments_count')->orderByDesc('publish_date');
                break;
            case 'views':
                $query->orderByDesc('views_count');
                break;
            default:
                $query->orderByDesc('publish_date');
        }

        $articles = $query->paginate(12)->through(function ($article) use ($request) {
            return [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'publish_date' => $article->publish_date,
                'views_count' => $article->views_count,
                'author' => [
                    'id' => $article->author->id,
                    'firstname' => $article->author->firstname,
                    'lastname' => $article->author->lastname,
                ],
                'featured_image' => $article->featuredImage ? [
                    'url' => $article->featuredImage->url
                ] : null,
                'likes_count' => $article->likes_count,
                'comments_count' => $article->all_comments_count,
                'is_liked' => $request->user() ? $article->isLikedBy($request->user()) : false,
                'can_edit' => $request->user() ? $article->canBeEditedBy($request->user()) : false,
            ];
        });

        return Inertia::render('Articles/Index', [
            'articles' => $articles,
            'filters' => [
                'search' => $request->get('search'),
                'sort' => $request->get('sort', 'recent'),
            ]
        ]);
    }

    /**
     * Afficher les articles d'un événement
     */
    public function eventArticles(Request $request, Event $event)
    {
        // Articles épinglés en premier
        $pinnedArticles = Article::published()
            ->forEvent($event->id)
            ->pinned()
            ->with(['author', 'featuredImage', 'likes', 'allComments'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('publish_date')
            ->get();

        // Articles normaux
        $regularQuery = Article::published()
            ->forEvent($event->id)
            ->where('is_pinned', false)
            ->with(['author', 'featuredImage', 'likes', 'allComments'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('publish_date');

        $regularArticles = $regularQuery->paginate(10)->through(function ($article) use ($request) {
            return $this->formatArticleData($article, $request->user());
        });

        $pinnedFormatted = $pinnedArticles->map(function ($article) use ($request) {
            return $this->formatArticleData($article, $request->user());
        });

        return Inertia::render('Articles/EventArticles', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
            ],
            'pinnedArticles' => $pinnedFormatted,
            'articles' => $regularArticles,
            'canCreateArticle' => $request->user() ? $event->isUserRegistered($request->user()) || $request->user()->isAdmin() : false,
        ]);
    }

    /**
     * Afficher un article spécifique
     */
    public function show(Request $request, Article $article)
    {
        $article->incrementViews();

        $article->load([
            'author',
            'featuredImage',
            'event',
            'comments.author',
            'comments.replies.author',
            'likes'
        ]);

        $comments = $article->comments()
            ->with(['author', 'replies.author', 'likes'])
            ->withCount(['likes'])
            ->orderBy('created_at')
            ->get()
            ->map(function ($comment) use ($request) {
                return [
                    'id' => $comment->id,
                    'content' => $comment->content,
                    'created_at' => $comment->created_at,
                    'author' => [
                        'id' => $comment->author->id,
                        'firstname' => $comment->author->firstname,
                        'lastname' => $comment->author->lastname,
                    ],
                    'likes_count' => $comment->likes_count,
                    'is_liked' => $request->user() ? $comment->isLikedBy($request->user()) : false,
                    'can_edit' => $request->user() ? $comment->canBeEditedBy($request->user()) : false,
                    'replies' => $comment->replies->map(function ($reply) use ($request) {
                        return [
                            'id' => $reply->id,
                            'content' => $reply->content,
                            'created_at' => $reply->created_at,
                            'author' => [
                                'id' => $reply->author->id,
                                'firstname' => $reply->author->firstname,
                                'lastname' => $reply->author->lastname,
                            ],
                            'likes_count' => $reply->likes()->count(),
                            'is_liked' => $request->user() ? $reply->isLikedBy($request->user()) : false,
                            'can_edit' => $request->user() ? $reply->canBeEditedBy($request->user()) : false,
                        ];
                    }),
                ];
            });

        return Inertia::render('Articles/Show', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'content' => $article->content,
                'publish_date' => $article->publish_date,
                'views_count' => $article->views_count,
                'is_pinned' => $article->is_pinned,
                'author' => [
                    'id' => $article->author->id,
                    'firstname' => $article->author->firstname,
                    'lastname' => $article->author->lastname,
                ],
                'event' => $article->event ? [
                    'id' => $article->event->id,
                    'title' => $article->event->title,
                ] : null,
                'featured_image' => $article->featuredImage ? [
                    'url' => $article->featuredImage->url
                ] : null,
                'likes_count' => $article->likes()->count(),
                'is_liked' => $request->user() ? $article->isLikedBy($request->user()) : false,
                'can_edit' => $request->user() ? $article->canBeEditedBy($request->user()) : false,
                'can_pin' => $request->user() ? $request->user()->isAdmin() : false,
            ],
            'comments' => $comments,
        ]);
    }

    /**
     * Formulaire de création d'article
     */
    public function create(Request $request)
    {
        $eventId = $request->get('event_id');
        $event = null;

        if ($eventId) {
            $event = Event::findOrFail($eventId);

            // Vérifier que l'utilisateur peut poster sur cet événement
            if (!$event->isUserRegistered($request->user()) && !$request->user()->isAdmin()) {
                abort(403, 'Vous devez être inscrit à cet événement pour publier un article.');
            }
        }

        return Inertia::render('Articles/Create', [
            'event' => $event ? [
                'id' => $event->id,
                'title' => $event->title,
            ] : null,
        ]);
    }

    /**
     * Sauvegarder un nouvel article
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'event_id' => 'nullable|exists:events,id',
            'status' => 'required|in:draft,published',
            'publish_date' => 'nullable|date|after_or_equal:now',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        // Vérifier les permissions pour l'événement
        if ($validated['event_id']) {
            $event = Event::findOrFail($validated['event_id']);
            if (!$event->isUserRegistered($request->user()) && !$request->user()->isAdmin()) {
                abort(403, 'Vous devez être inscrit à cet événement pour publier un article.');
            }
        }

        $fileId = null;
        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $path = $uploaded->store('articles', 'public');

            $file = File::create([
                'fileable_id' => null,
                'fileable_type' => null,
                'name' => pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
                'extension' => $uploaded->getClientOriginalExtension(),
                'mimetype' => $uploaded->getMimeType(),
                'size' => $uploaded->getSize(),
                'path' => $path,
                'disk' => 'public',
                'hash' => hash_file('sha256', $uploaded->getRealPath()),
            ]);
            $fileId = $file->id;
        }

        $article = Article::create([
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'event_id' => $validated['event_id'],
            'status' => $validated['status'],
            'publish_date' => $validated['publish_date'] ?? now(),
            'file_id' => $fileId,
            'user_id' => $request->user()->id,
        ]);

        $redirectRoute = $validated['event_id']
            ? 'events.articles'
            : 'articles.index';

        $redirectParams = $validated['event_id']
            ? ['event' => $validated['event_id']]
            : [];

        return redirect()->route($redirectRoute, $redirectParams)
            ->with('success', 'Article publié avec succès !');
    }

    /**
     * Formulaire d'édition d'article
     */
    public function edit(Request $request, Article $article)
    {
        if (!$article->canBeEditedBy($request->user())) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet article.');
        }

        return Inertia::render('Articles/Edit', [
            'article' => [
                'id' => $article->id,
                'title' => $article->title,
                'excerpt' => $article->excerpt,
                'content' => $article->content,
                'status' => $article->status,
                'publish_date' => $article->publish_date->format('Y-m-d\TH:i'),
                'event_id' => $article->event_id,
                'featured_image' => $article->featuredImage ? [
                    'url' => $article->featuredImage->url
                ] : null,
            ],
            'event' => $article->event ? [
                'id' => $article->event->id,
                'title' => $article->event->title,
            ] : null,
        ]);
    }

    /**
     * Mettre à jour un article
     */
    public function update(Request $request, Article $article)
    {
        if (!$article->canBeEditedBy($request->user())) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet article.');
        }

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'excerpt' => 'nullable|string|max:500',
            'content' => 'required|string',
            'status' => 'required|in:draft,published,archived',
            'publish_date' => 'nullable|date',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
        ]);

        $fileId = $article->file_id;
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($article->featuredImage) {
                Storage::disk($article->featuredImage->disk)->delete($article->featuredImage->path);
                $article->featuredImage->delete();
            }

            $uploaded = $request->file('image');
            $path = $uploaded->store('articles', 'public');

            $file = File::create([
                'fileable_id' => null,
                'fileable_type' => null,
                'name' => pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
                'extension' => $uploaded->getClientOriginalExtension(),
                'mimetype' => $uploaded->getMimeType(),
                'size' => $uploaded->getSize(),
                'path' => $path,
                'disk' => 'public',
                'hash' => hash_file('sha256', $uploaded->getRealPath()),
            ]);
            $fileId = $file->id;
        }

        $article->update([
            'title' => $validated['title'],
            'excerpt' => $validated['excerpt'],
            'content' => $validated['content'],
            'status' => $validated['status'],
            'publish_date' => $validated['publish_date'] ?? $article->publish_date,
            'file_id' => $fileId,
        ]);

        return redirect()->route('articles.show', $article)
            ->with('success', 'Article modifié avec succès !');
    }

    /**
     * Supprimer un article
     */
    public function destroy(Request $request, Article $article)
    {
        if (!$article->canBeDeletedBy($request->user())) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer cet article.');
        }

        if ($article->featuredImage) {
            Storage::disk($article->featuredImage->disk)->delete($article->featuredImage->path);
            $article->featuredImage->delete();
        }

        $eventId = $article->event_id;
        $article->delete();

        if ($eventId) {
            return redirect()->route('events.articles', ['event' => $eventId])
                ->with('success', 'Article supprimé avec succès.');
        }

        return redirect()->route('articles.index')
            ->with('success', 'Article supprimé avec succès.');
    }

    /**
     * Épingler/désépingler un article (admin seulement)
     */
    public function togglePin(Request $request, Article $article)
    {
        if (!$request->user()->isAdmin()) {
            abort(403, 'Seuls les administrateurs peuvent épingler des articles.');
        }

        $article->update(['is_pinned' => !$article->is_pinned]);

        return back()->with('success', $article->is_pinned ? 'Article épinglé.' : 'Article désépinglé.');
    }

    /**
     * Liker/déliker un article
     */
    public function toggleLike(Request $request, Article $article)
    {
        $user = $request->user();
        $existingLike = ArticleLike::where('article_id', $article->id)
            ->where('user_id', $user->id)
            ->first();

        if ($existingLike) {
            $existingLike->delete();
            $liked = false;
        } else {
            ArticleLike::create([
                'article_id' => $article->id,
                'user_id' => $user->id,
            ]);
            $liked = true;
        }

        return response()->json([
            'liked' => $liked,
            'likes_count' => $article->likes()->count(),
        ]);
    }

    /**
     * Formater les données d'un article pour l'API
     */
    private function formatArticleData($article, $user = null)
    {
        return [
            'id' => $article->id,
            'title' => $article->title,
            'excerpt' => $article->excerpt,
            'publish_date' => $article->publish_date,
            'views_count' => $article->views_count,
            'is_pinned' => $article->is_pinned,
            'author' => [
                'id' => $article->author->id,
                'firstname' => $article->author->firstname,
                'lastname' => $article->author->lastname,
            ],
            'featured_image' => $article->featuredImage ? [
                'url' => $article->featuredImage->url
            ] : null,
            'likes_count' => $article->likes_count,
            'comments_count' => $article->all_comments_count,
            'is_liked' => $user ? $article->isLikedBy($user) : false,
            'can_edit' => $user ? $article->canBeEditedBy($user) : false,
        ];
    }
}
