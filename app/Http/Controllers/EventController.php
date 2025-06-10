<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use App\Models\{User, File, Registration};

class EventController extends Controller
{
    /**
     * Afficher la liste des événements
     */
    public function index(Request $request)
    {
        $query = Event::with(['illustration', 'address', 'organizer'])
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc');

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

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
            ];
        });

        return Inertia::render('Events/Index', [
            'events' => $events,
            'filters' => [
                'category' => $request->get('category'),
                'sort' => $request->get('sort', 'date'),
            ]
        ]);
    }

    public function show(Request $request, Event $event)
    {
        $event->load(['illustration', 'address', 'organizer', 'registrations.user']);

        // Récupérer les 3 articles les plus récents
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
            'can_create_article' => $request->user() ?
                ($event->isUserRegistered($request->user()) || $request->user()->isAdmin()) : false,
        ];

        return Inertia::render('Events/Show', [
            'event' => $eventData
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

    private function canUserRegister(Event $event, $user = null): array
    {
        $now = now();
        $result = ['can_register' => false, 'reason' => null];

        if ($event->registration_open && $now < $event->registration_open) {
            $result['reason'] = 'registration_not_open';
            return $result;
        }

        if ($event->registration_close && $now > $event->registration_close) {
            $result['reason'] = 'registration_closed';
            return $result;
        }

        if ($event->start_date < $now) {
            $result['reason'] = 'event_started';
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
            $path = $uploaded->store('events', 'public');

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
            'registration_open'   => Carbon::parse($validated['registration_open']),
            'registration_close'  => Carbon::parse($validated['registration_close']),
            'max_participants'    => $validated['max_participants'] ?? null,
            'price'               => $validated['price'] ?? null,
            'description'         => $validated['description'] ?? null,
            'file_id'             => $fileId,
            'organizer_id'        => $request->user()->id,
        ]);

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
            $path = $uploaded->store('events', 'public');

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

        $event->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            'registration_open' => Carbon::parse($validated['registration_open']),
            'registration_close' => Carbon::parse($validated['registration_close']),
            'max_participants' => $validated['max_participants'] ?? null,
            'members_only' => $validated['members_only'] ?? false,
            'requires_medical_certificate' => $validated['requires_medical_certificate'] ?? false,
            'price' => $validated['price'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_id' => $fileId,
        ]);

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
}
