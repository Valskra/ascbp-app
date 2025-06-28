<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\{User, File, Registration, Article, ArticleComment, CommentLike};

class EventController extends Controller
{
    public function show(Request $request, Event $event)
    {
        $event->load(['illustration', 'address', 'organizer', 'registrations.user']);

        // Vérifier si l'événement est en cours
        $isOngoing = $this->getEventStatus($event) === 'ongoing';

        // Récupérer les 3 articles les plus récents pour l'aperçu
        $recentArticles = $event->articles()
            ->published()
            ->with(['author', 'featuredImage'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('is_pinned')
            ->orderByDesc('publish_date')
            ->limit(3)
            ->get()
            ->map(function ($article) use ($request) {
                return [
                    'id' => $article->id,
                    'title' => $article->title,
                    'excerpt' => $article->excerpt,
                    'publish_date' => $article->publish_date,
                    'is_pinned' => $article->is_pinned,
                    'author' => [
                        'firstname' => $article->author->firstname,
                        'lastname' => $article->author->lastname,
                    ],
                    'featured_image' => $article->featuredImage ? [
                        'url' => $article->featuredImage->url
                    ] : null,
                    'likes_count' => $article->likes_count,
                    'comments_count' => $article->all_comments_count,
                    'is_liked' => $request->user() ? $article->isLikedBy($request->user()) : false,
                ];
            });

        // Déterminer si l'utilisateur peut créer des articles/posts
        $canCreateArticle = $request->user();

        // NOUVEAU: Déterminer si l'utilisateur peut commenter (tous les utilisateurs authentifiés)
        $canComment = $request->user() ? true : false;

        $eventData = [
            'id' => $event->id,
            'title' => $event->title,
            'category' => $event->category,
            'description' => $event->description,
            'start_date' => $event->start_date,
            'end_date' => $event->end_date,
            'registration_open' => $event->registration_open,
            'registration_close' => $event->registration_close,
            'max_participants' => $event->max_participants,
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
                'firstname' => $event->organizer->firstname,
                'lastname' => $event->organizer->lastname,
            ],
            'participants' => $event->registrations->map(function ($registration) {
                return [
                    'id' => $registration->user->id,
                    'firstname' => $registration->user->firstname,
                    'lastname' => $registration->user->lastname,
                ];
            }),
            'participants_count' => $event->registrations()->count(),
            'can_register' => $this->canUserRegister($event, $request->user()),
            'is_registered' => $request->user() ? $event->isUserRegistered($request->user()) : false,
            'recent_articles' => $recentArticles,
            'articles_count' => $event->articles()->published()->count(),
            'can_create_article' => $canCreateArticle,
            'can_comment' => $canComment, // NOUVEAU - Ajout important
            'status' => $this->getEventStatus($event),
            'is_ongoing' => $isOngoing,
        ];

        // Utiliser une vue différente selon si l'événement est en cours ou non
        $viewName = $isOngoing ? 'Events/ShowOngoing' : 'Events/Show';

        return Inertia::render($viewName, [
            'event' => $eventData
        ]);
    }

    /**
     * Nouvelle méthode pour gérer les posts d'événements en cours
     */
    public function storeLivePost(Request $request, Event $event)
    {
        // Vérifier que l'événement est en cours
        if ($this->getEventStatus($event) !== 'ongoing') {
            return response()->json([
                'success' => false,
                'message' => 'Cette action n\'est disponible que pendant l\'événement.'
            ], 403);
        }

        // Vérifier que l'utilisateur est inscrit ou admin
        if (!$request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'Vous devez être identifié pour poster.'
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|max:1000',
            'images' => 'nullable|array|max:4',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,webp|max:5120',
            'video' => 'nullable|mimes:mp4,mov,avi,wmv,webm|max:51200', // 50MB
        ], [
            'content.required' => 'Le contenu est requis.',
            'content.max' => 'Le contenu ne peut pas dépasser 1000 caractères.',
            'images.max' => 'Vous ne pouvez pas télécharger plus de 4 images.',
            'images.*.image' => 'Le fichier doit être une image.',
            'images.*.mimes' => 'Les images doivent être au format JPEG, PNG, JPG, GIF ou WebP.',
            'images.*.max' => 'Chaque image ne doit pas dépasser 5 MB.',
            'video.mimes' => 'La vidéo doit être au format MP4, MOV, AVI, WMV ou WebM.',
            'video.max' => 'La vidéo ne doit pas dépasser 50 MB.',
        ]);

        try {
            // Traitement des fichiers
            $mediaFiles = [];

            // Images
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    if ($index >= 4) break; // Sécurité supplémentaire

                    $path = $image->store('event-posts/images', 's3');
                    $file = File::create([
                        'fileable_id' => null,
                        'fileable_type' => null,
                        'name' => pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME),
                        'extension' => $image->getClientOriginalExtension(),
                        'mimetype' => $image->getMimeType(),
                        'size' => $image->getSize(),
                        'path' => $path,
                        'disk' => 's3',
                        'hash' => hash_file('sha256', $image->getRealPath()),
                    ]);
                    $mediaFiles[] = ['type' => 'image', 'file_id' => $file->id];
                }
            }

            // Vidéo
            if ($request->hasFile('video')) {
                $video = $request->file('video');
                $path = $video->store('event-posts/videos', 's3');  // au lieu de 'public'
                $file = File::create([
                    'fileable_id' => null,
                    'fileable_type' => null,
                    'name' => pathinfo($video->getClientOriginalName(), PATHINFO_FILENAME),
                    'extension' => $video->getClientOriginalExtension(),
                    'mimetype' => $video->getMimeType(),
                    'size' => $video->getSize(),
                    'path' => $path,
                    'disk' => 's3',
                    'hash' => hash_file('sha256', $video->getRealPath()),
                ]);
                $mediaFiles[] = ['type' => 'video', 'file_id' => $file->id];
            }

            // Créer l'article avec type "post"
            $article = Article::create([
                'title' => substr($validated['content'], 0, 50) . (strlen($validated['content']) > 50 ? '...' : ''),
                'content' => $validated['content'],
                'event_id' => $event->id,
                'status' => 'published',
                'publish_date' => now(),
                'user_id' => $request->user()->id,
                'is_post' => true,
                'metadata' => [
                    'media_files' => $mediaFiles,
                    'post_type' => 'live',
                    'created_from' => 'event_live_feed'
                ]
            ]);

            // Charger les médias pour la réponse
            $formattedMediaFiles = [];
            foreach ($mediaFiles as $media) {
                $file = File::find($media['file_id']);
                if ($file) {
                    $formattedMediaFiles[] = [
                        'type' => $media['type'],
                        'url' => $file->url,
                        'name' => $file->name . '.' . $file->extension,
                    ];
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Post publié avec succès !',
                'post' => [
                    'id' => $article->id,
                    'content' => $article->content,
                    'created_at' => $article->created_at,
                    'author' => [
                        'id' => $request->user()->id,
                        'firstname' => $request->user()->firstname,
                        'lastname' => $request->user()->lastname,
                    ],
                    'media_files' => $formattedMediaFiles,
                    'likes_count' => 0,
                    'comments_count' => 0,
                    'is_liked' => false,
                ]
            ]);
        } catch (\Exception $e) {
            // En cas d'erreur, nettoyer les fichiers déjà uploadés
            foreach ($mediaFiles as $media) {
                $file = File::find($media['file_id']);
                if ($file) {
                    Storage::disk($file->disk)->delete($file->path);
                    $file->delete();
                }
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la publication : ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Récupérer les posts live d'un événement - CORRIGÉE
     */
    public function getLivePosts(Request $request, Event $event)
    {
        if ($this->getEventStatus($event) !== 'ongoing') {
            return response()->json([
                'success' => false,
                'message' => 'Cette action n\'est disponible que pendant l\'événement.'
            ], 403);
        }

        $posts = $event->articles()
            ->where('is_post', true)
            ->with([
                'author',
                'likes',
                'allComments' => function ($query) {
                    $query->with(['author', 'likes'])
                        ->withCount('likes')
                        ->orderBy('created_at', 'asc');
                }
            ])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('created_at')
            ->paginate(10);

        return response()->json([
            'posts' => $posts->through(function ($post) use ($request) {
                $mediaFiles = [];
                if ($post->metadata && isset($post->metadata['media_files'])) {
                    foreach ($post->metadata['media_files'] as $media) {
                        $file = File::find($media['file_id']);
                        if ($file) {
                            $mediaFiles[] = [
                                'type' => $media['type'],
                                'url' => $file->url,
                                'name' => $file->name,
                            ];
                        }
                    }
                }

                // Formater les commentaires
                $comments = $post->allComments->whereNull('parent_id')->map(function ($comment) use ($request) {
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
                        'can_delete' => $request->user() ? $comment->canBeDeletedBy($request->user()) : false,
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
                                'can_delete' => $request->user() ? $reply->canBeDeletedBy($request->user()) : false,
                            ];
                        }),
                    ];
                });

                return [
                    'id' => $post->id,
                    'content' => $post->content,
                    'created_at' => $post->created_at,
                    'author' => [
                        'id' => $post->author->id,
                        'firstname' => $post->author->firstname,
                        'lastname' => $post->author->lastname,
                    ],
                    'media_files' => $mediaFiles,
                    'likes_count' => $post->likes_count,
                    'comments_count' => $post->all_comments_count,
                    'is_liked' => $request->user() ? $post->isLikedBy($request->user()) : false,
                    'can_edit' => $request->user() ? $post->canBeEditedBy($request->user()) : false,
                    'comments' => $comments,
                    'can_comment' => $request->user() ? true : false, // Tous les utilisateurs authentifiés peuvent commenter
                ];
            }),
            'pagination' => [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'has_more_pages' => $posts->hasMorePages(),
            ]
        ]);
    }



    /**
     * Récupérer les médias d'un événement - CORRIGÉE
     */
    public function getEventMedia(Request $request, Event $event)
    {
        if ($this->getEventStatus($event) !== 'ongoing') {
            return response()->json([
                'success' => false,
                'message' => 'Cette action n\'est disponible que pendant l\'événement.'
            ], 403);
        }

        $mediaItems = collect();

        // Récupérer tous les médias des posts
        $posts = $event->articles()
            ->where('is_post', true)
            ->whereNotNull('metadata')
            ->with('author')
            ->get();

        foreach ($posts as $post) {
            if (isset($post->metadata['media_files'])) {
                foreach ($post->metadata['media_files'] as $media) {
                    $file = File::find($media['file_id']);
                    if ($file) {
                        $mediaItems->push([
                            'id' => $file->id,
                            'type' => $media['type'],
                            'url' => $file->url,
                            'name' => $file->name,
                            'created_at' => $post->created_at,
                            'author' => [
                                'firstname' => $post->author->firstname,
                                'lastname' => $post->author->lastname,
                            ],
                            'post_id' => $post->id,
                        ]);
                    }
                }
            }
        }

        // Trier par date de création
        $mediaItems = $mediaItems->sortByDesc('created_at')->values();

        return response()->json([
            'media' => $mediaItems->slice(0, 20)->values(),
            'total' => $mediaItems->count(),
        ]);
    }


    /**
     * Afficher la liste des événements
     */
    public function index(Request $request)
    {
        $query = Event::with(['illustration', 'address', 'organizer']);

        // Filtrage par statut d'événement
        $status = $request->get('status', 'upcoming'); // upcoming, ongoing, past, all

        switch ($status) {
            case 'upcoming':
                // Événements à venir (pas encore commencés)
                $query->where('start_date', '>', now());
                break;
            case 'ongoing':
                // Événements en cours (commencés mais pas terminés)
                $query->where('start_date', '<=', now())
                    ->where('end_date', '>=', now());
                break;
            case 'past':
                // Événements passés (terminés)
                $query->where('end_date', '<', now());
                break;
            case 'all':
                // Tous les événements (pas de filtre par date)
                break;
            default:
                // Par défaut : événements à venir et en cours
                $query->where('end_date', '>=', now());
        }

        // Filtrage par catégorie
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Tri des événements
        $sortBy = $request->get('sort', 'date');
        switch ($sortBy) {
            case 'date_desc':
                $query->orderBy('start_date', 'desc');
                break;
            case 'title':
                $query->orderBy('title', 'asc');
                break;
            default:
                $query->orderBy('start_date', 'asc');
        }

        $events = $query->get()->map(function ($event) use ($request) {
            $user = $request->user();
            $registrationStatus = $this->canUserRegister($event, $user);

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
                'registration_status' => $registrationStatus,
                'is_registered' => $user ? $event->isUserRegistered($user) : false,
                // Ajout du statut pour l'affichage
                'status' => $this->getEventStatus($event),
            ];
        });

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => [
                'category' => $request->get('category'),
                'sort' => $request->get('sort', 'date'),
                'status' => $request->get('status', 'upcoming'),
            ]
        ]);
    }

    /**
     * Afficher la page d'inscription à un événement
     */
    public function showRegistration(Request $request, Event $event)
    {
        $user = $request->user();
        $registrationStatus = $this->canUserRegister($event, $user);

        if (!$registrationStatus['can_register']) {
            $errorMessages = [
                'registration_not_open' => 'Les inscriptions ne sont pas encore ouvertes.',
                'registration_closed' => 'Les inscriptions sont fermées.',
                'event_started' => 'L\'événement a déjà commencé.',
                'not_authenticated' => 'Vous devez être connecté pour vous inscrire.',
                'already_registered' => 'Vous êtes déjà inscrit à cet événement.',
                'event_full' => 'Cet événement est complet.',
                'members_only' => 'Cet événement est réservé aux adhérents.',
            ];

            if ($registrationStatus['reason'] === 'members_only') {
                return redirect()->route('membership.create')
                    ->with('error', $errorMessages[$registrationStatus['reason']]);
            }

            return redirect()->route('events.show', $event)
                ->with('error', $errorMessages[$registrationStatus['reason']] ?? 'Inscription impossible.');
        }

        $medicalCertificates = [];
        if ($event->requires_medical_certificate) {
            $medicalCertificates = $user->documents()
                ->with('file')
                ->where('title', 'LIKE', '%certificat%medical%')
                ->where(function ($query) {
                    $query->whereNull('expiration_date')
                        ->orWhere('expiration_date', '>', now());
                })
                ->get()
                ->map(function ($doc) {
                    return [
                        'id' => $doc->id,
                        'title' => $doc->title,
                        'expiration_date' => $doc->expiration_date,
                        'url' => $doc->file->url,
                    ];
                });
        }

        return Inertia::render('Events/Registration', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'requires_medical_certificate' => $event->requires_medical_certificate,
                'price' => $event->price,
                'illustration' => $event->illustration ? ['url' => $event->illustration->url] : null,
                'address' => $event->address ? [
                    'house_number' => $event->address->house_number,
                    'street_name' => $event->address->street_name,
                    'city' => $event->address->city,
                    'postal_code' => $event->address->postal_code,
                    'country' => $event->address->country,
                ] : null,
            ],
            'user' => [
                'id' => $user->id,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'phone' => $user->phone,
                'birth_date' => $user->birth_date,
                'address' => $user->homeAddress ? [
                    'house_number' => $user->homeAddress->house_number,
                    'street_name' => $user->homeAddress->street_name,
                    'city' => $user->homeAddress->city,
                    'postal_code' => $user->homeAddress->postal_code,
                    'country' => $user->homeAddress->country,
                ] : null,
            ],
            'medical_certificates' => $medicalCertificates,
        ]);
    }

    public function register(Request $request, Event $event)
    {
        $user = $request->user();
        $registrationStatus = $this->canUserRegister($event, $user);

        if (!$registrationStatus['can_register']) {
            return back()->with('error', 'Impossible de s\'inscrire à cet événement.');
        }

        $validationRules = [
            'firstname' => 'required|string|max:50',
            'lastname' => 'required|string|max:50',
            'email' => 'required|email|max:100',
            'phone' => 'required|string|max:15',
            'birth_date' => 'required|date|before:today',
        ];

        if ($event->requires_medical_certificate) {
            $validationRules['medical_certificate_id'] = 'required|exists:documents,id';
        }

        $validated = $request->validate($validationRules);

        if ($event->isPaidFor($user)) {
            return $this->handleStripePayment($event, $user, $validated);
        }

        $this->createRegistration($event, $user, $validated);

        return redirect()->route('events.show', $event)
            ->with('success', 'Inscription réussie !');
    }

    public function unregister(Request $request, Event $event)
    {
        $user = $request->user();

        $registration = $event->registrations()->where('user_id', $user->id)->first();

        if (!$registration) {
            return back()->with('error', 'Vous n\'êtes pas inscrit à cet événement.');
        }

        $registration->delete();

        return back()->with('success', 'Désinscription réussie.');
    }

    private function userHasValidMedicalCertificate($user): bool
    {
        return $user->documents()
            ->whereHas('file')
            ->where('title', 'LIKE', '%certificat%medical%')
            ->where(function ($query) {
                $query->whereNull('expiration_date')
                    ->orWhere('expiration_date', '>', now());
            })
            ->exists();
    }


    /**
     * API pour les événements à venir
     */
    public function apiIndex()
    {
        $events = Event::where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    }

    public function create(Request $request)
    {
        return Inertia::render('Events/Create', [
            'today' => Carbon::today()->format('Y-m-d'),
            'weekLater' => Carbon::today()->addDays(7)->format('Y-m-d'),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title'              => 'required|string|max:255|unique:' . Event::class,
            'category'           => 'required|string|max:100',
            'start_date'         => 'required|date|after_or_equal:today',
            'end_date'           => 'required|date|after_or_equal:start_date',
            'registration_open'  => 'required|date|before_or_equal:registration_close',
            'registration_close' => 'required|date|after_or_equal:registration_open|before_or_equal:start_date',
            'max_participants'   => 'nullable|integer|min:1',
            'price'              => 'nullable|integer|max:500',
            'description'        => 'nullable|string|max:2000',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240',
            'address'            => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:10',
            'country'            => 'nullable|string|max:100',
        ]);

        $fileId = null;
        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $path = $uploaded->store('events', 's3');

            $file = File::create([
                'fileable_id'   => null,
                'fileable_type' => null,
                'name'          => pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
                'extension'     => $uploaded->getClientOriginalExtension(),
                'mimetype'      => $uploaded->getMimeType(),
                'size'          => $uploaded->getSize(),
                'path'          => $path,
                'disk'          => 'public',
                'hash'          => hash_file('sha256', $uploaded->getRealPath()),
            ]);
            $fileId = $file->id;
        }

        $event = Event::create([
            'title'               => $validated['title'],
            'category'            => $validated['category'],
            'start_date'          => Carbon::parse($validated['start_date']),
            'end_date'            => Carbon::parse($validated['end_date']),
            // Ouverture des inscriptions : 00:00 du jour sélectionné
            'registration_open'   => Carbon::parse($validated['registration_open'])->startOfDay(),
            // Fermeture des inscriptions : 23:59 du jour sélectionné
            'registration_close'  => Carbon::parse($validated['registration_close'])->endOfDay(),
            'max_participants'    => $validated['max_participants'] ?? null,
            'price'               => $validated['price'] ?? null,
            'description'         => $validated['description'] ?? null,
            'file_id'             => $fileId,
            'organizer_id'        => $request->user()->id,
        ]);

        // Gestion de l'adresse (identique à l'original)
        if (
            !empty($validated['address']) ||
            !empty($validated['city']) ||
            !empty($validated['postal_code']) ||
            !empty($validated['country'])
        ) {
            $streetTypes = ['rue', 'avenue', 'boulevard', 'chemin', 'impasse', 'place', 'route', 'allée', 'quai', 'cours', 'passage', 'voie', 'square', 'faubourg'];
            $parts = preg_split('/\s+/', trim($validated['address'] ?? ''));
            $houseNumber = '';
            $streetName  = '';

            if (!empty($validated['address'])) {
                foreach ($parts as $i => $part) {
                    if (in_array(strtolower($part), $streetTypes, true)) {
                        $houseNumber = implode(' ', array_slice($parts, 0, $i));
                        $streetName  = implode(' ', array_slice($parts, $i));
                        break;
                    }
                }
                if ($streetName === '') {
                    $streetName  = $validated['address'];
                    $houseNumber = '';
                }
            }

            $event->address()->create([
                'label'        => 'location',
                'house_number' => trim($houseNumber),
                'street_name'  => trim($streetName),
                'city'         => $validated['city'] ?? null,
                'postal_code'  => $validated['postal_code'] ?? null,
                'country'      => $validated['country'] ?? null,
            ]);
        }

        return redirect()
            ->route('events.index')
            ->with('success', 'Événement créé avec succès !');
    }



    /**
     * Show events for management (pour les organisateurs)
     */
    public function edit(Event $event, Request $request)
    {
        if ($event->organizer_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        $event->load(['illustration', 'address', 'registrations']);

        $currentParticipantsCount = $event->registrations->count();

        $eventData = [
            'id' => $event->id,
            'title' => $event->title,
            'category' => $event->category,
            'description' => $event->description,
            'start_date' => $event->start_date->format('Y-m-d'),
            'end_date' => $event->end_date->format('Y-m-d'),
            'registration_open' => $event->registration_open?->format('Y-m-d'),
            'registration_close' => $event->registration_close?->format('Y-m-d'),
            'max_participants' => $event->max_participants,
            'current_participants_count' => $currentParticipantsCount,
            'members_only' => (bool) $event->members_only,
            'requires_medical_certificate' => (bool) $event->requires_medical_certificate,
            'price' => $event->price,
            'illustration' => $event->illustration ? [
                'url' => $event->illustration->url
            ] : null,
            'address' => $event->address ? [
                'address' => trim(($event->address->house_number ?? '') . ' ' . ($event->address->street_name ?? '')),
                'city' => $event->address->city,
                'postal_code' => $event->address->postal_code,
                'country' => $event->address->country,
            ] : null,
        ];


        return Inertia::render('Events/Edit', [
            'event' => $eventData,
            'today' => Carbon::today()->format('Y-m-d'),
        ]);
    }


    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        if ($event->organizer_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        $currentParticipantsCount = $event->registrations->count();

        $validationRules = [
            'title' => 'required|string|max:255|unique:events,title,' . $event->id,
            'category' => 'required|string|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_open' => 'required|date|before_or_equal:registration_close',
            'registration_close' => 'required|date|after_or_equal:registration_open|before_or_equal:start_date',
            'members_only' => 'boolean',
            'requires_medical_certificate' => 'boolean',
            'price' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:2000',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:16384',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
        ];

        if ($currentParticipantsCount > 0) {
            $validationRules['max_participants'] = 'nullable|integer|min:' . $currentParticipantsCount;
        } else {
            $validationRules['max_participants'] = 'nullable|integer|min:1';
        }

        $customMessages = [
            'max_participants.min' => $currentParticipantsCount > 0
                ? "Le nombre maximum de participants ne peut pas être inférieur au nombre actuel d'inscrits ({$currentParticipantsCount})."
                : 'Le nombre maximum de participants doit être d\'au moins 1.',
        ];

        $validated = $request->validate($validationRules, $customMessages);

        $fileId = $event->file_id;
        if ($request->hasFile('image')) {
            if ($event->illustration) {
                Storage::disk($event->illustration->disk)->delete($event->illustration->path);
                $event->illustration->delete();
            }

            $uploaded = $request->file('image');
            $path = $uploaded->store('events', 's3');

            $file = File::create([
                'fileable_id' => null,
                'fileable_type' => null,
                'name' => pathinfo($uploaded->getClientOriginalName(), PATHINFO_FILENAME),
                'extension' => $uploaded->getClientOriginalExtension(),
                'mimetype' => $uploaded->getMimeType(),
                'size' => $uploaded->getSize(),
                'path' => $path,
                'disk' => 's3',
                'hash' => hash_file('sha256', $uploaded->getRealPath()),
            ]);
            $fileId = $file->id;
        }

        $event->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            // Ouverture des inscriptions : 00:00 du jour sélectionné
            'registration_open' => Carbon::parse($validated['registration_open'])->startOfDay(),
            // Fermeture des inscriptions : 23:59 du jour sélectionné
            'registration_close' => Carbon::parse($validated['registration_close'])->endOfDay(),
            'max_participants' => $validated['max_participants'] ?? null,
            'members_only' => $validated['members_only'] ?? false,
            'requires_medical_certificate' => $validated['requires_medical_certificate'] ?? false,
            'price' => $validated['price'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_id' => $fileId,
        ]);

        // Gestion de l'adresse (identique à l'original)
        if (
            !empty($validated['address']) ||
            !empty($validated['city']) ||
            !empty($validated['postal_code']) ||
            !empty($validated['country'])
        ) {
            $streetTypes = ['rue', 'avenue', 'boulevard', 'chemin', 'impasse', 'place', 'route', 'allée', 'quai', 'cours', 'passage', 'voie', 'square', 'faubourg'];
            $parts = preg_split('/\s+/', trim($validated['address'] ?? ''));
            $houseNumber = '';
            $streetName = '';

            if (!empty($validated['address'])) {
                foreach ($parts as $i => $part) {
                    if (in_array(strtolower($part), $streetTypes, true)) {
                        $houseNumber = implode(' ', array_slice($parts, 0, $i));
                        $streetName = implode(' ', array_slice($parts, $i));
                        break;
                    }
                }
                if ($streetName === '') {
                    $streetName = $validated['address'];
                    $houseNumber = '';
                }
            }

            $event->address()->updateOrCreate(
                [],
                [
                    'house_number' => trim($houseNumber),
                    'street_name' => trim($streetName),
                    'city' => $validated['city'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'country' => $validated['country'] ?? null,
                ]
            );
        } else {
            $event->address()?->delete();
        }

        return redirect()
            ->route('events.index', $event)
            ->with('success', 'Événement modifié avec succès.');
    }

    /**
     * Show events for management (pour les organisateurs)
     */
    public function manage(Request $request)
    {
        $user = $request->user();

        $query = Event::with(['illustration', 'address', 'registrations'])
            ->where('organizer_id', $user->id)
            ->orderBy('start_date', 'desc');

        if ($user->isAdmin()) {
            $query = Event::with(['illustration', 'address', 'registrations', 'organizer'])
                ->orderBy('start_date', 'desc');
        }

        $events = $query->get()->map(function ($event) use ($user) {
            $now = new \DateTime();
            $eventStart = new \DateTime($event->start_date);
            $hasParticipants = $event->registrations->count() > 0;

            return [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'participants_count' => $event->registrations->count(),
                'max_participants' => $event->max_participants,
                'organizer' => $user->isAdmin() ? [
                    'firstname' => $event->organizer->firstname,
                    'lastname' => $event->organizer->lastname,
                ] : null,
                'can_edit' => ($event->organizer_id === $user->id || $user->isAdmin()) && $eventStart > $now,
                'can_delete' => ($event->organizer_id === $user->id || $user->isAdmin()) && $eventStart > $now && !$hasParticipants,
            ];
        });

        return Inertia::render('Events/Manage', [
            'events' => $events
        ]);
    }


    /**
     * Remove the specified event.
     */
    public function destroy(Event $event, Request $request)
    {
        if ($event->organizer_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer cet événement.');
        }

        if ($event->illustration) {
            Storage::disk($event->illustration->disk)->delete($event->illustration->path);
            $event->illustration->delete();
        }

        $event->delete();

        return redirect()->route('events.index')->with('success', 'Événement supprimé avec succès.');
    }

    /**
     * List participants of the specified event, including medical certificates.
     */
    public function participants(Event $event, Request $request)
    {
        // Vérifier que l'utilisateur est l'organisateur ou admin
        if ($event->organizer_id !== $request->user()->id && !$request->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à voir les participants de cet événement.');
        }

        // Charger les participants avec leurs certificats médicaux via registrations
        $registrations = $event->registrations()
            ->with(['user.documents' => function ($query) {
                $query->whereHas('file')
                    ->where('title', 'LIKE', '%certificat%medical%')
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('expiration_date')
                            ->orWhere('expiration_date', '>', now());
                    });
            }])
            ->get();

        // Transformer les données
        $participantsData = $registrations->map(function ($registration) {
            return [
                'id' => $registration->user->id,
                'firstname' => $registration->user->firstname,
                'lastname' => $registration->user->lastname,
                'email' => $registration->user->email,
                'registration_date' => $registration->registration_date,
                'amount' => $registration->amount ?? 0,
                'has_valid_medical_certificate' => $registration->user->documents->isNotEmpty(),
                'medical_certificate_expires_at' => $registration->user->documents->first()?->expiration_date,
            ];
        });

        return response()->json($participantsData, Response::HTTP_OK);
    }

    /**
     * Créer l'inscription
     */
    private function createRegistration(Event $event, User $user, array $data, float $amount = 0)
    {
        Registration::create([
            'event_id' => $event->id,
            'user_id' => $user->id,
            'registration_date' => now(),
            'amount' => $amount,
            'metadata' => [
                'registration_info' => [
                    'firstname' => $data['firstname'],
                    'lastname' => $data['lastname'],
                    'email' => $data['email'],
                    'phone' => $data['phone'],
                    'birth_date' => $data['birth_date'],
                ],
                'medical_certificate_id' => $data['medical_certificate_id'] ?? null,
            ]
        ]);
    }

    /**
     * Gérer le paiement Stripe
     */
    private function handleStripePayment(Event $event, User $user, array $registrationData)
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $amount = $event->price * 100;
            $session = \Stripe\Checkout\Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => "Inscription - {$event->title}",
                            'description' => "Événement du " . $event->start_date->format('d/m/Y'),
                        ],
                        'unit_amount' => $amount,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('events.registration.success', [
                    'event' => $event->id,
                    'session_id' => '{CHECKOUT_SESSION_ID}'
                ]),
                'cancel_url' => route('events.registration', $event->id),
                'metadata' => [
                    'event_id' => $event->id,
                    'user_id' => $user->id,
                    'registration_data' => json_encode($registrationData),
                ],
            ]);

            return redirect($session->url);
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la création du paiement : ' . $e->getMessage());
        }
    }


    public function handlePaymentSuccess(Request $request, Event $event)
    {
        try {
            \Stripe\Stripe::setApiKey(config('services.stripe.secret'));

            $session = \Stripe\Checkout\Session::retrieve($request->session_id);

            if ($session->payment_status === 'paid') {
                $registrationData = json_decode($session->metadata->registration_data, true);
                $user = User::find($session->metadata->user_id);

                $this->createRegistration($event, $user, $registrationData, $session->amount_total / 100);

                return redirect()->route('events.show', $event)
                    ->with('success', 'Paiement réussi ! Votre inscription a été confirmée.');
            }
        } catch (\Exception $e) {
            return redirect()->route('events.show', $event)
                ->with('error', 'Erreur lors de la vérification du paiement.');
        }

        return redirect()->route('events.show', $event)
            ->with('error', 'Le paiement n\'a pas pu être confirmé.');
    }

    public function articles(Request $request, Event $event)
    {
        // Articles épinglés en premier
        $pinnedArticles = $event->articles()
            ->published()
            ->pinned()
            ->with(['author', 'featuredImage'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('publish_date')
            ->get();

        // Articles normaux
        $regularQuery = $event->articles()
            ->published()
            ->where('is_pinned', false)
            ->with(['author', 'featuredImage'])
            ->withCount(['likes', 'allComments'])
            ->orderByDesc('publish_date');

        $regularArticles = $regularQuery->paginate(10)->through(function ($article) use ($request) {
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
                'is_pinned' => false,
            ];
        });

        $pinnedFormatted = $pinnedArticles->map(function ($article) use ($request) {
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
                'is_pinned' => true,
            ];
        });

        return Inertia::render('Events/Articles', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'description' => $event->description,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'category' => $event->category,
            ],
            'pinnedArticles' => $pinnedFormatted,
            'articles' => $regularArticles,
            'canCreateArticle' => $request->user() ?
                ($event->isUserRegistered($request->user()) || $request->user()->isAdmin()) : false,
            'canManageArticles' => $request->user() ? $request->user()->isAdmin() : false,
        ]);
    }

    private function getEventStatus(Event $event): string
    {
        $now = now();

        if ($event->start_date > $now) {
            return 'upcoming';
        } elseif ($event->start_date <= $now && $event->end_date >= $now) {
            return 'ongoing';
        } else {
            return 'past';
        }
    }

    /**
     * Méthode mise à jour pour vérifier les inscriptions avec gestion des heures
     */
    private function canUserRegister(Event $event, $user = null): array
    {
        $now = now();
        $result = ['can_register' => false, 'reason' => null];

        // Vérification de l'ouverture des inscriptions (00:00 du jour)
        if ($event->registration_open) {
            $registrationOpenDate = Carbon::parse($event->registration_open)->startOfDay();
            if ($now < $registrationOpenDate) {
                $result['reason'] = 'registration_not_open';
                return $result;
            }
        }

        // Vérification de la fermeture des inscriptions (23:59 du jour)
        if ($event->registration_close) {
            $registrationCloseDate = Carbon::parse($event->registration_close)->endOfDay();
            if ($now > $registrationCloseDate) {
                $result['reason'] = 'registration_closed';
                return $result;
            }
        }

        // On permet l'inscription même si l'événement a commencé (tant qu'il n'est pas terminé)
        if ($event->end_date < $now) {
            $result['reason'] = 'event_finished';
            return $result;
        }

        if (!$user) {
            $result['reason'] = 'not_authenticated';
            return $result;
        }

        if ($event->isUserRegistered($user)) {
            $result['reason'] = 'already_registered';
            return $result;
        }

        if ($event->max_participants && $event->registrations()->count() >= $event->max_participants) {
            $result['reason'] = 'event_full';
            return $result;
        }

        if ($event->members_only && !$user->hasMembership()) {
            $result['reason'] = 'members_only';
            return $result;
        }

        if ($event->requires_medical_certificate && !$this->userHasValidMedicalCertificate($user)) {
            $result['reason'] = 'requires_medical_certificate';
            return $result;
        }

        $result['can_register'] = true;
        return $result;
    }
}
