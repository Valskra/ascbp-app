<?php
// app/Http/Controllers/EventController.php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Response;
use App\Models\{User, File};
use Illuminate\Support\Facades\Redis;

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

        // Filtrage par catégorie si spécifié
        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        // Tri
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

        $events = $query->get()->map(function ($event, $request) {
            return [
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
                    'street_name' => $event->address->street_name,
                    'city' => $event->address->city,
                    'postal_code' => $event->address->postal_code,
                ] : null,
                'organizer' => [
                    'firstname' => $event->organizer->firstname,
                    'lastname' => $event->organizer->lastname,
                ],
                'participants_count' => $event->participants()->count(),
                'can_register' => $this->canUserRegister($event),
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
        $event->load(['illustration', 'address', 'organizer', 'participants']);

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
            'participants' => $event->participants->map(function ($participant) {
                return [
                    'id' => $participant->id,
                    'firstname' => $participant->firstname,
                    'lastname' => $participant->lastname,
                ];
            }),
            'participants_count' => $event->participants()->count(),
            'can_register' => $this->canUserRegister($event),
            'is_registered' => $event->participants->contains($request->user()->id),
        ];

        return Inertia::render('Events/Show', [
            'event' => $eventData
        ]);
    }

    public function register(Request $request, Event $event)
    {
        $user = $request->user();

        // Vérifications
        if (!$this->canUserRegister($event, $user)) {
            return back()->with('error', 'Impossible de s\'inscrire à cet événement.');
        }

        // Vérifier si déjà inscrit
        if ($event->participants->contains($user->id)) {
            return back()->with('error', 'Vous êtes déjà inscrit à cet événement.');
        }

        // Vérifier le nombre maximum de participants
        if ($event->max_participants && $event->participants()->count() >= $event->max_participants) {
            return back()->with('error', 'Cet événement est complet.');
        }

        // Inscription
        $event->participants()->attach($user->id, [
            'registration_date' => now(),
            'amount' => $this->getRegistrationAmount($event, $user),
        ]);

        return back()->with('success', 'Inscription réussie !');
    }

    public function unregister(Request $request, Event $event)
    {
        $user = $request->user();

        if (!$event->participants->contains($user->id)) {
            return back()->with('error', 'Vous n\'êtes pas inscrit à cet événement.');
        }

        $event->participants()->detach($user->id);

        return back()->with('success', 'Désinscription réussie.');
    }

    private function canUserRegister(Event $event): bool
    {
        $now = now();
        if ($event->registration_open && $now < $event->registration_open) {
            return false;
        }

        if ($event->registration_close && $now > $event->registration_close) {
            return false;
        }

        if ($event->start_date < $now) {
            return false;
        }

        return true;
    }

    /**
     * Get registration amount for user
     */
    private function getRegistrationAmount(Event $event, $user): float
    {
        // Ici vous pouvez implémenter une logique de tarification
        // Par exemple, tarif réduit pour les membres, etc.

        if (!$event->price) {
            return 0;
        }

        // Exemple : tarif réduit pour les membres actifs
        if ($user->hasMembership()) {
            return 0; // Gratuit pour les membres
        }

        // Sinon, extraire le montant du champ price (s'il contient un nombre)
        preg_match('/\d+/', $event->price, $matches);
        return isset($matches[0]) ? (float) $matches[0] : 0;
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
            'price'              => 'nullable|string|max:50',
            'description'        => 'nullable|string|max:2000',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address'            => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:10',
            'country'            => 'nullable|string|max:100',
        ]);

        // Gestion de l'image
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

        // Création de l'événement
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

        // Gestion de l'adresse
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
    public function manage(Request $request)
    {
        $user = $request->user();

        $query = Event::with(['illustration', 'address', 'participants'])
            ->where('organizer_id', $user->id)
            ->orderBy('start_date', 'desc');

        if ($user->isAdmin()) {
            // Les admins voient tous les événements
            $query = Event::with(['illustration', 'address', 'participants', 'organizer'])
                ->orderBy('start_date', 'desc');
        }

        $events = $query->get()->map(function ($event) use ($user) {
            return [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'participants_count' => $event->participants->count(),
                'max_participants' => $event->max_participants,
                'organizer' => $user->isAdmin() ? [
                    'firstname' => $event->organizer->firstname,
                    'lastname' => $event->organizer->lastname,
                ] : null,
                'can_edit' => $event->organizer_id === $user->id || $user->isAdmin(),
                'can_delete' => $event->organizer_id === $user->id || $user->isAdmin(),
            ];
        });

        return Inertia::render('Events/Manage', [
            'events' => $events
        ]);
    }

    public function update(Request $request, Event $event)
    {
        // Vérifier que l'utilisateur est l'organisateur ou admin
        if ($event->organizer_id !== auth()->id() && !auth()->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        // Validation (même que pour store)
        $validated = $request->validate([
            'title' => 'required|string|max:255|unique:events,title,' . $event->id,
            'category' => 'required|string|max:100',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date',
            'registration_start' => 'required|date|before_or_equal:registration_end',
            'registration_end' => 'required|date|after_or_equal:registration_start',
            'max_participants' => 'nullable|integer|min:1',
            'price' => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
        ]);

        // Gestion de l'image
        $fileId = $event->file_id;
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($event->illustration) {
                Storage::disk($event->illustration->disk)->delete($event->illustration->path);
                $event->illustration->delete();
            }

            // Upload nouvelle image
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

        // Mise à jour de l'événement
        $event->update([
            'title' => $validated['title'],
            'category' => $validated['category'],
            'start_date' => Carbon::parse($validated['start_date']),
            'end_date' => Carbon::parse($validated['end_date']),
            'registration_start' => Carbon::parse($validated['registration_start']),
            'registration_end' => Carbon::parse($validated['registration_end']),
            'max_participants' => $validated['max_participants'] ?? null,
            'price' => $validated['price'] ?? null,
            'description' => $validated['description'] ?? null,
            'file_id' => $fileId,
        ]);

        // Mise à jour de l'adresse
        if (
            !empty($validated['address']) ||
            !empty($validated['city']) ||
            !empty($validated['postal_code']) ||
            !empty($validated['country'])
        ) {
            $streetTypes = ['rue', 'avenue', 'boulevard', 'chemin', 'impasse', 'place', 'route', 'allée', 'quai', 'cours', 'passage', 'voie', 'square', 'faubourg'];
            $parts = preg_split('/\s+/', trim($validated['address']));
            $houseNumber = '';
            $streetName = '';

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

            $event->address()->updateOrCreate(
                ['label' => 'location'],
                [
                    'house_number' => trim($houseNumber),
                    'street_name' => trim($streetName),
                    'city' => $validated['city'] ?? null,
                    'postal_code' => $validated['postal_code'] ?? null,
                    'country' => $validated['country'] ?? null,
                ]
            );
        }

        return redirect()
            ->route('events.show', $event)
            ->with('success', 'Événement modifié avec succès.');
    }

    public function edit(Event $event, Request $request)
    {
        // Vérifier que l'utilisateur est l'organisateur ou admin
        if ($event->organizer_id !== $request->id() && !$request->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à modifier cet événement.');
        }

        $event->load(['illustration', 'address']);

        $eventData = [
            'id' => $event->id,
            'title' => $event->title,
            'category' => $event->category,
            'description' => $event->description,
            'start_date' => $event->start_date->format('Y-m-d'),
            'end_date' => $event->end_date->format('Y-m-d'),
            'registration_start' => $event->registration_start?->format('Y-m-d'),
            'registration_end' => $event->registration_end?->format('Y-m-d'),
            'max_participants' => $event->max_participants,
            'price' => $event->price,
            'illustration' => $event->illustration ? [
                'url' => $event->illustration->url
            ] : null,
            'address' => $event->address ? [
                'address' => trim($event->address->house_number . ' ' . $event->address->street_name),
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
     * Remove the specified event.
     */
    public function destroy(Event $event)
    {
        // Vérifier que l'utilisateur est l'organisateur ou admin
        if ($event->organizer_id !== auth()->id() && !$request->user()->isAdmin()) {
            abort(403, 'Vous n\'êtes pas autorisé à supprimer cet événement.');
        }

        // Supprimer l'image si elle existe
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
    public function participants(Event $event)
    {
        $participants = $event->participants()
            ->withPivot('certificate_medical')
            ->get();

        return response()->json($participants, Response::HTTP_OK);
    }
}
