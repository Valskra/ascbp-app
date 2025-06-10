<?php

namespace App\Http\Controllers;

use App\Models\{User, Event, Article, Registration};
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Afficher le dashboard
     */
    public function index(Request $request)
    {
        $user = $request->user();

        $query = Event::with(['illustration', 'address', 'organizer'])
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc');

        $events = $query->get()->map(function ($event) use ($request) {
            $user = $request->user();

            return [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'description' => $event->description,
                'start_date' => $event->start_date->toISOString(),
                'end_date' => $event->end_date ? $event->end_date->toISOString() : null,
                'registration_open' => $event->registration_open ? $event->registration_open->toISOString() : null,
                'registration_close' => $event->registration_close ? $event->registration_close->toISOString() : null,
                'max_participants' => $event->max_participants,
                'members_only' => (bool) $event->members_only,
                'requires_medical_certificate' => (bool) $event->requires_medical_certificate,
                'price' => $event->price,
                'illustration' => $event->illustration ? [
                    'url' => $event->illustration->url
                ] : null,
                'address' => $event->address ? [
                    'house_number' => $event->address->house_number,
                    'street_name' => $event->address->street_name,
                    'city' => $event->address->city,
                    'postal_code' => $event->address->postal_code,
                    'country' => $event->address->country,
                ] : null,
                'organizer' => [
                    'id' => $event->organizer->id,
                    'firstname' => $event->organizer->firstname,
                    'lastname' => $event->organizer->lastname,
                ],
                'participants_count' => $event->registrations()->count(),
                'is_registered' => $user ? $event->isUserRegistered($user) : false,
            ];
        });

        return Inertia::render('Dashboard', [
            'upcoming_events' => $events,
            'upcoming_events_count' => $this->getUserUpcomingEventsCount($user),
            'published_articles_count' => $this->getUserPublishedArticlesCount($user),
            'total_likes' => $this->getUserTotalLikes($user),
            'total_comments' => $this->getUserTotalComments($user),
        ]);
    }

    /**
     * API pour les articles récents de l'utilisateur
     */
    public function userRecentArticles(Request $request)
    {
        $articles = $request->user()
            ->articles()
            ->where('status', 'published')
            ->with(['likes', 'allComments'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('publish_date')
            ->limit(5)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'publish_date' => $article->publish_date,
                    'views_count' => $article->views_count,
                    'likes_count' => $article->likes_count,
                    'comments_count' => $article->all_comments_count,
                ];
            });

        return response()->json(['articles' => $articles]);
    }

    /**
     * API pour les événements à venir de l'utilisateur
     */
    public function userUpcomingEvents(Request $request)
    {
        $user = $request->user();

        $events = Event::whereHas('registrations', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('start_date', '>=', now())
            ->with(['registrations', 'articles'])
            ->withCount(['registrations as participants_count', 'articles'])
            ->orderBy('start_date')
            ->limit(5)
            ->get()
            ->map(function ($event) {
                return [
                    'id' => $event->id,
                    'title' => $event->title,
                    'start_date' => $event->start_date,
                    'participants_count' => $event->participants_count,
                    'articles_count' => $event->articles_count,
                ];
            });

        return response()->json(['events' => $events]);
    }

    /**
     * API pour les articles populaires
     */
    public function popularArticles(Request $request)
    {
        $articles = Article::published()
            ->general()
            ->with(['author', 'featuredImage'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('likes_count')
            ->orderByDesc('views_count')
            ->limit(6)
            ->get()
            ->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'excerpt' => $article->excerpt,
                    'author' => [
                        'firstname' => $article->author->firstname,
                        'lastname' => $article->author->lastname,
                    ],
                    'featured_image' => $article->featuredImage ? [
                        'url' => $article->featuredImage->url
                    ] : null,
                    'likes_count' => $article->likes_count,
                    'comments_count' => $article->all_comments_count,
                ];
            });

        return response()->json(['articles' => $articles]);
    }

    /**
     * Obtenir le nombre d'événements à venir pour l'utilisateur
     */
    private function getUserUpcomingEventsCount(User $user): int
    {
        return Event::whereHas('registrations', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('start_date', '>=', now())
            ->count();
    }

    /**
     * Obtenir les événements à venir pour l'utilisateur
     */
    private function getUserUpcomingEvents(User $user)
    {
        return Event::whereHas('registrations', function ($query) use ($user) {
            $query->where('user_id', $user->id);
        })
            ->where('start_date', '>=', now());
    }


    /**
     * Obtenir le nombre d'articles publiés par l'utilisateur
     */
    private function getUserPublishedArticlesCount(User $user): int
    {
        return $user->articles()
            ->where('status', 'published')
            ->count();
    }

    /**
     * Obtenir le nombre total de likes reçus par l'utilisateur
     */
    private function getUserTotalLikes(User $user): int
    {
        return $user->articles()
            ->where('status', 'published')
            ->withCount('likes')
            ->get()
            ->sum('likes_count');
    }

    /**
     * Obtenir le nombre total de commentaires reçus par l'utilisateur
     */
    private function getUserTotalComments(User $user): int
    {
        return $user->articles()
            ->where('status', 'published')
            ->withCount('allComments')
            ->get()
            ->sum('all_comments_count');
    }
}
