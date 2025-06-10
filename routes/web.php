<?php

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Http\Middleware\{IsAdmin, IsAnimator};
use App\Models\{Article, Event, Registration};
use App\Http\Controllers\{
    MembershipController,
    ProfileController,
    AdminUserController,
    AdminEventController,
    UploadLinkController,
    FileController,
    EventController,
    AIAssistantController,
    CertificateController,
    ArticleController,
    ArticleCommentController,
    DashboardController,
};

Route::post(
    '/files/user-profile-picture',
    [FileController::class, 'storeUserProfilePicture']
)->name('files.store.user.profile-picture');

Route::put(
    '/profile/photo',
    [ProfileController::class, 'updatePhoto']
)->name('profile.update.photo');

Route::get('/certificats/upload/{token}', [UploadLinkController::class, 'showForm'])
    ->name('upload-link.show');

Route::get("/u/{token}",     [UploadLinkController::class, 'showForm'])
    ->name('upload-link.form');
Route::post("/u/{token}",     [UploadLinkController::class, 'upload'])
    ->name('upload-link.upload');

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'profile'])->name('profile.profile');
    Route::get('/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/edit', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/edit/contacts', [ProfileController::class, 'updateContacts'])->name('profile.updateContacts');
    Route::patch('/edit/password', [ProfileController::class, 'updatePassword'])->name('profile.updatePassword');
    Route::delete('/edit', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::patch('/profile/address', [ProfileController::class, 'updateAddress'])->name('profile.updateAddress');
    Route::patch('/profile/birth', [ProfileController::class, 'updateBirth'])->name('profile.updateBirth');
    Route::patch('/profile/phone', [ProfileController::class, 'updatePhone'])->name('profile.updatePhone');
    Route::patch('/profile/email', [ProfileController::class, 'updateEmail'])->name('profile.updateEmail');
    Route::patch('/profile/name', [ProfileController::class, 'updateName'])->name('profile.updateName');
    Route::put('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.updatePhoto');
    Route::delete('/profile/photo', [ProfileController::class, 'destroyPhoto'])->name('profile.destroyPhoto');
    Route::get('/profile/photo', [ProfileController::class, 'showPhoto'])->name('profile.showPhoto');
    Route::get('/membership', [MembershipController::class, 'create'])->name('membership.create');
    Route::post('/membership', [MembershipController::class, 'store'])->name('membership.store');
    Route::get('/certificats', [FileController::class, 'listUserCertificates'])->name('certificats');
    Route::post('/certificats/store', [FileController::class, 'storeCertificate'])->name('certificats.store');
    Route::delete('/certificats/{document}', [FileController::class, 'destroyCertificate'])->name('certificats.destroy');
    Route::post('/certificats/upload-link', [UploadLinkController::class, 'store'])
        ->name('upload-link.store');

    Route::get('/certificats/upload-link/latest', [
        UploadLinkController::class,
        'latest'
    ])->middleware('auth')
        ->name('upload-link.latest');
});

// Accès aux certificats
Route::get('/certificate/{userId}/{token}', [CertificateController::class, 'showPublic'])
    ->name('certificate.public');
////

// Gestion admin des utilisateurs
Route::middleware(['auth', IsAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/users', [AdminUserController::class, 'index'])->name('users');
    Route::get('/users/export', [AdminUserController::class, 'export'])->name('export_users');

    Route::get('/events', [AdminEventController::class, 'index'])->name('events');
    Route::get('/events/export', [AdminEventController::class, 'export'])->name('events.export');

    // Gestion des participants
    Route::get('/events/{event}/participants', [AdminEventController::class, 'participants'])->name('events.participants');
    Route::get('/events/{event}/participants/export', [AdminEventController::class, 'exportParticipants'])->name('events.participants.export');
    Route::delete('/events/{event}/participants/{registration}', [AdminEventController::class, 'unregisterUser'])->name('events.participants.unregister');



    Route::get('/', function () {
        return Inertia::render('AdminDashboard');
    })->middleware(['auth', 'verified'])->name('dashboard');
});
////

// Gestion des événements
Route::middleware(['auth', IsAnimator::class])->prefix('events')->name('events.')->group(function () {
    Route::get('/create', [EventController::class, 'create'])->name('create');
    Route::post('/store', [EventController::class, 'store'])->name('store');
    Route::get('/manage', [EventController::class, 'manage'])->name('manage');

    Route::get('/{event}/edit', [EventController::class, 'edit'])->name('edit');
    Route::put('/{event}', [EventController::class, 'update'])->name('update');
    Route::delete('/{event}', [EventController::class, 'destroy'])->name('destroy');
    Route::get('/{event}/participants', [EventController::class, 'participants'])->name('participants');
});

Route::middleware('auth')->prefix('events')->name('events.')->group(function () {
    Route::get('/', [EventController::class, 'index'])->name('index');
    Route::get('/{event}', [EventController::class, 'show'])->name('show');

    Route::get('/{event}/register', [EventController::class, 'showRegistration'])->name('registration');
    Route::post('/{event}/register', [EventController::class, 'register'])->name('register');
    Route::get('/{event}/registration/success', [EventController::class, 'handlePaymentSuccess'])->name('registration.success');

    Route::delete('/{event}/unregister', [EventController::class, 'unregister'])->name('unregister');
});

Route::get('/api/events', [EventController::class, 'apiIndex'])->name('api.events.index');

////

// Utilisation de l'assistant IA
Route::prefix('ai-assistant')->group(function () {
    Route::post('/correct-chatgpt', [AIAssistantController::class, 'correctWithChatGPT']);
    Route::post('/improve-chatgpt', [AIAssistantController::class, 'improveWithChatGPT']);
    Route::post('/improve-claude', [AIAssistantController::class, 'improveWithClaude']);
});
//// 


// Articles publics (lecture)
Route::prefix('articles')->name('articles.')->group(function () {
    Route::get('/', [ArticleController::class, 'index'])->name('index');
    Route::get('/{article}', [ArticleController::class, 'show'])->name('show');
});

// Articles des événements
Route::prefix('events')->name('events.')->group(function () {
    Route::get('/{event}/articles', [EventController::class, 'articles'])->name('articles');
});

// Articles - actions authentifiées
Route::middleware('auth')->prefix('articles')->name('articles.')->group(function () {
    Route::get('/create', [ArticleController::class, 'create'])->name('create');
    Route::post('/', [ArticleController::class, 'store'])->name('store');

    Route::get('/{article}/edit', [ArticleController::class, 'edit'])->name('edit');
    Route::put('/{article}', [ArticleController::class, 'update'])->name('update');
    Route::delete('/{article}', [ArticleController::class, 'destroy'])->name('destroy');

    // Actions AJAX
    Route::post('/{article}/like', [ArticleController::class, 'toggleLike'])->name('like');
    Route::post('/{article}/pin', [ArticleController::class, 'togglePin'])->name('pin');

    // Commentaires
    Route::post('/{article}/comments', [ArticleCommentController::class, 'store'])->name('comments.store');
    Route::put('/comments/{comment}', [ArticleCommentController::class, 'update'])->name('comments.update');
    Route::delete('/comments/{comment}', [ArticleCommentController::class, 'destroy'])->name('comments.destroy');
    Route::post('/comments/{comment}/like', [ArticleCommentController::class, 'toggleLike'])->name('comments.like');
});

// API pour les articles (pour le dashboard)
Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    Route::get('/user/recent-articles', function (Request $request) {
        return response()->json([
            'articles' => $request->user()->recent_articles->map(function ($article) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'publish_date' => $article->publish_date,
                    'views_count' => $article->views_count,
                    'likes_count' => $article->likes()->count(),
                    'comments_count' => $article->allComments()->count(),
                ];
            })
        ]);
    })->name('user.recent-articles');
});

Route::middleware('auth')->prefix('api')->name('api.')->group(function () {
    // Articles de l'utilisateur
    Route::get('/user/recent-articles', [DashboardController::class, 'userRecentArticles'])
        ->name('user.recent-articles');

    // Événements de l'utilisateur
    Route::get('/user/upcoming-events', [DashboardController::class, 'userUpcomingEvents'])
        ->name('user.upcoming-events');

    // Articles populaires
    Route::get('/popular-articles', [DashboardController::class, 'popularArticles'])
        ->name('popular-articles');

    // Statistiques générales (pour admin)
    Route::get('/stats/general', function (Request $request) {
        if (!$request->user()->isAdmin()) {
            abort(403);
        }

        return response()->json([
            'total_users' => User::count(),
            'total_events' => Event::count(),
            'total_articles' => Article::where('status', 'published')->count(),
            'total_registrations' => Registration::count(),
            'recent_articles' => Article::published()
                ->with(['author'])
                ->orderByDesc('publish_date')
                ->limit(5)
                ->get()
                ->map(function ($article) {
                    return [
                        'id' => $article->id,
                        'title' => $article->title,
                        'author' => $article->author->firstname . ' ' . $article->author->lastname,
                        'publish_date' => $article->publish_date,
                    ];
                }),
        ]);
    })->name('stats.general');
});

// Mettre à jour la route dashboard pour utiliser le contrôleur
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
});


require __DIR__ . '/auth.php';
