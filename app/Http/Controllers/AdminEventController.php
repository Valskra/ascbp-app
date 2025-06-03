<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Registration;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Carbon\Carbon;

class AdminEventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::with(['organizer', 'registrations']);

        // Filtres de recherche
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%$search%")
                    ->orWhere('category', 'like', "%$search%")
                    ->orWhereHas('organizer', function ($orgQuery) use ($search) {
                        $orgQuery->where('firstname', 'like', "%$search%")
                            ->orWhere('lastname', 'like', "%$search%");
                    });
            });
        }

        // Filtre par catégorie
        if ($category = $request->input('category')) {
            $query->where('category', $category);
        }

        // Filtre par statut temporel
        if ($status = $request->input('status')) {
            $now = now();
            switch ($status) {
                case 'upcoming':
                    $query->where('start_date', '>', $now);
                    break;
                case 'ongoing':
                    $query->where('start_date', '<=', $now)
                        ->where('end_date', '>=', $now);
                    break;
                case 'past':
                    $query->where('end_date', '<', $now);
                    break;
                case 'registration_open':
                    $query->where(function ($q) use ($now) {
                        $q->where(function ($subQ) use ($now) {
                            $subQ->whereNull('registration_open')
                                ->orWhere('registration_open', '<=', $now);
                        })
                            ->where(function ($subQ) use ($now) {
                                $subQ->whereNull('registration_close')
                                    ->orWhere('registration_close', '>=', $now);
                            })
                            ->where('start_date', '>', $now);
                    });
                    break;
                case 'registration_closed':
                    $query->where(function ($q) use ($now) {
                        $q->where('registration_close', '<', $now)
                            ->orWhere('start_date', '<=', $now);
                    });
                    break;
            }
        }

        // Filtre par nombre de participants
        if ($participants = $request->input('participants')) {
            switch ($participants) {
                case 'empty':
                    $query->doesntHave('registrations');
                    break;
                case 'partial':
                    $query->whereHas('registrations')
                        ->where(function ($q) {
                            $q->whereNull('max_participants')
                                ->orWhereRaw('(SELECT COUNT(*) FROM registrations WHERE event_id = events.id) < max_participants');
                        });
                    break;
                case 'full':
                    $query->whereNotNull('max_participants')
                        ->whereRaw('(SELECT COUNT(*) FROM registrations WHERE event_id = events.id) >= max_participants');
                    break;
            }
        }

        // Tri
        $sortBy = $request->get('sort', 'start_date');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortBy) {
            case 'title':
                $query->orderBy('title', $sortOrder);
                break;
            case 'category':
                $query->orderBy('category', $sortOrder);
                break;
            case 'organizer':
                $query->join('users', 'events.organizer_id', '=', 'users.id')
                    ->orderBy('users.lastname', $sortOrder)
                    ->orderBy('users.firstname', $sortOrder)
                    ->select('events.*');
                break;
            case 'registration_open':
                $query->orderBy('registration_open', $sortOrder);
                break;
            case 'registration_close':
                $query->orderBy('registration_close', $sortOrder);
                break;
            case 'participants_count':
                $query->withCount('registrations')
                    ->orderBy('registrations_count', $sortOrder);
                break;
            case 'created_at':
                $query->orderBy('created_at', $sortOrder);
                break;
            default: // start_date
                $query->orderBy('start_date', $sortOrder);
        }

        $events = $query
            ->paginate(25)
            ->through(fn($event) => [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'registration_open' => $event->registration_open,
                'registration_close' => $event->registration_close,
                'max_participants' => $event->max_participants,
                'members_only' => $event->members_only,
                'requires_medical_certificate' => $event->requires_medical_certificate,
                'price' => $event->price,
                'organizer' => [
                    'id' => $event->organizer->id,
                    'firstname' => $event->organizer->firstname,
                    'lastname' => $event->organizer->lastname,
                ],
                'participants_count' => $event->registrations->count(),
                'created_at' => $event->created_at,
            ])
            ->withQueryString();

        // Statistiques globales
        $allEvents = Event::with('registrations')->get();
        $stats = [
            'total' => $allEvents->count(),
            'upcoming' => 0,
            'ongoing' => 0,
            'past' => 0,
            'total_participants' => 0,
            'competitions' => 0,
            'entrainements' => 0,
            'manifestations' => 0,
        ];

        $now = now();
        foreach ($allEvents as $event) {
            $stats['total_participants'] += $event->registrations->count();

            // Statistiques par catégorie
            switch ($event->category) {
                case 'competition':
                    $stats['competitions']++;
                    break;
                case 'entrainement':
                    $stats['entrainements']++;
                    break;
                case 'manifestation':
                    $stats['manifestations']++;
                    break;
            }

            // Statistiques temporelles
            if ($event->end_date < $now) {
                $stats['past']++;
            } elseif ($event->start_date <= $now && $event->end_date >= $now) {
                $stats['ongoing']++;
            } else {
                $stats['upcoming']++;
            }
        }

        return Inertia::render('Admin/ManageEvents', [
            'events' => $events,
            'stats' => $stats,
            'filters' => $request->only(['search', 'category', 'status', 'participants', 'sort', 'order']),
        ]);
    }

    public function participants(Request $request, Event $event)
    {
        $query = $event->registrations()->with(['user']);

        // Recherche dans les participants
        if ($search = $request->input('search')) {
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('firstname', 'like', "%$search%")
                    ->orWhere('lastname', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%");
            });
        }

        // Tri des participants
        $sortBy = $request->get('sort', 'registration_date');
        $sortOrder = $request->get('order', 'desc');

        switch ($sortBy) {
            case 'name':
                $query->join('users', 'registrations.user_id', '=', 'users.id')
                    ->orderBy('users.lastname', $sortOrder)
                    ->orderBy('users.firstname', $sortOrder)
                    ->select('registrations.*');
                break;
            case 'email':
                $query->join('users', 'registrations.user_id', '=', 'users.id')
                    ->orderBy('users.email', $sortOrder)
                    ->select('registrations.*');
                break;
            case 'amount':
                $query->orderBy('amount', $sortOrder);
                break;
            default: // registration_date
                $query->orderBy('registration_date', $sortOrder);
        }

        $registrations = $query
            ->paginate(25)
            ->through(fn($registration) => [
                'id' => $registration->id,
                'user' => [
                    'id' => $registration->user->id,
                    'firstname' => $registration->user->firstname,
                    'lastname' => $registration->user->lastname,
                    'email' => $registration->user->email,
                    'phone' => $registration->user->phone,
                    'membership_status' => $registration->user->membership_status,
                ],
                'registration_date' => $registration->registration_date,
                'amount' => $registration->amount,
                'metadata' => $registration->metadata,
                'can_unregister' => $this->canUnregisterUser($event, $registration),
            ])
            ->withQueryString();

        return Inertia::render('Admin/EventParticipants', [
            'event' => [
                'id' => $event->id,
                'title' => $event->title,
                'category' => $event->category,
                'start_date' => $event->start_date,
                'end_date' => $event->end_date,
                'max_participants' => $event->max_participants,
                'price' => $event->price,
            ],
            'registrations' => $registrations,
            'filters' => $request->only(['search', 'sort', 'order']),
            'stats' => [
                'total_participants' => $event->registrations()->count(),
                'paid_participants' => $event->registrations()->where('amount', '>', 0)->count(),
                'free_participants' => $event->registrations()->where('amount', '<=', 0)->count(),
                'total_revenue' => $event->registrations()->sum('amount'),
            ],
        ]);
    }

    public function unregisterUser(Request $request, Event $event, Registration $registration)
    {
        if (!$this->canUnregisterUser($event, $registration)) {
            return back()->with('error', 'Impossible de désinscrire cet utilisateur.');
        }

        $userName = $registration->user->firstname . ' ' . $registration->user->lastname;

        $registration->delete();

        return back()->with('success', "L'utilisateur {$userName} a été désinscrit avec succès.");
    }

    public function export(Request $request)
    {
        abort_unless(Gate::allows('admin_access'), 403);

        $fileName = 'events_export_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Titre',
                'Catégorie',
                'Date début',
                'Date fin',
                'Ouverture inscriptions',
                'Fermeture inscriptions',
                'Max participants',
                'Participants inscrits',
                'Prix',
                'Adhérents uniquement',
                'Certificat médical requis',
                'Organisateur',
                'Statut',
                'Date création'
            ]);

            $events = Event::with(['organizer', 'registrations'])->orderBy('start_date', 'desc')->get();
            $now = now();

            foreach ($events as $event) {
                // Déterminer le statut
                if ($event->end_date < $now) {
                    $status = 'Terminé';
                } elseif ($event->start_date <= $now && $event->end_date >= $now) {
                    $status = 'En cours';
                } else {
                    $status = 'À venir';
                }

                fputcsv($handle, [
                    $event->id,
                    $event->title,
                    $event->category,
                    $event->start_date->format('d/m/Y H:i'),
                    $event->end_date ? $event->end_date->format('d/m/Y H:i') : '',
                    $event->registration_open ? $event->registration_open->format('d/m/Y H:i') : '',
                    $event->registration_close ? $event->registration_close->format('d/m/Y H:i') : '',
                    $event->max_participants ?? 'Illimité',
                    $event->registrations->count(),
                    $event->price ?? 'Gratuit',
                    $event->members_only ? 'Oui' : 'Non',
                    $event->requires_medical_certificate ? 'Oui' : 'Non',
                    $event->organizer->firstname . ' ' . $event->organizer->lastname,
                    $status,
                    $event->created_at->format('d/m/Y H:i')
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    public function exportParticipants(Request $request, Event $event)
    {
        abort_unless(Gate::allows('admin_access'), 403);

        $fileName = 'participants_' . str_replace(' ', '_', $event->title) . '_' . now()->format('Ymd_His') . '.csv';

        $response = new StreamedResponse(function () use ($event) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, [
                'ID',
                'Prénom',
                'Nom',
                'Email',
                'Téléphone',
                'Date inscription',
                'Montant payé',
                'Statut adhésion',
            ]);

            $registrations = $event->registrations()->with('user')->orderBy('registration_date')->get();

            foreach ($registrations as $registration) {
                $membershipStatus = match ($registration->user->membership_status) {
                    -1 => 'Désactivé',
                    0 => 'Ancien > 1 an',
                    1 => 'Adhérent actif',
                    2 => 'Ancien < 1 an',
                    default => 'Inconnu',
                };

                fputcsv($handle, [
                    $registration->user->id,
                    $registration->user->firstname,
                    $registration->user->lastname,
                    $registration->user->email,
                    $registration->user->phone,
                    $registration->registration_date->format('d/m/Y'),
                    $registration->amount ? number_format($registration->amount, 2) . '€' : 'Gratuit',
                    $membershipStatus,
                ]);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $fileName . '"');

        return $response;
    }

    private function canUnregisterUser(Event $event, Registration $registration): bool
    {
        // Ne pas permettre la désinscription si l'événement a commencé
        if ($event->start_date <= now()) {
            return false;
        }

        // Ne pas permettre la désinscription si un paiement a été effectué
        if ($registration->amount > 0) {
            return false;
        }

        return true;
    }
}
