<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Http\Response;
use App\Models\{User, File};

class EventController extends Controller
{

    /**
     * Display a listing of upcoming events.
     */
    public function index()
    {
        $events = Event::where('date', '>=', now())
            ->orderBy('date', 'asc')
            ->get();

        return response()->json($events, Response::HTTP_OK);
    }



    public function create(Request $request)
    {
        return Inertia::render('EventCreation', [
            'today' => Carbon::today()->format('Y-m-d'),
            'weekLater' => Carbon::today()->addDays(7)->format('Y-m-d'),
        ]);
    }


    /**
     * Store a newly created event.
     */
    public function store(Request $request)
    {

        $validated = $request->validate([
            'title'              => 'required|string|max:255',
            'category'           => 'required|string|max:100',
            'start_date'         => 'required|date|after_or_equal:today',
            'end_date'           => 'required|date|after_or_equal:start_date',
            'registration_open' => 'required|date|before_or_equal:registration_close',
            'registration_close'   => 'required|date|after_or_equal:registration_open',
            'max_participants'   => 'nullable|integer|min:1',
            'price'              => 'nullable|string|max:50',
            'description'        => 'nullable|string',
            'image'              => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'address'            => 'nullable|string|max:255',
            'city'               => 'nullable|string|max:100',
            'postal_code'        => 'nullable|string|max:10',
            'country'            => 'nullable|string|max:100',
        ]);

        if ($request->hasFile('image')) {
            $uploaded = $request->file('image');
            $path = $uploaded->store('events', 'public');

            // Crée un File lié
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
        } else {
            $fileId = null;
        }

        // 3) Préparer les données de l'événement
        $data = [
            'title'               => $validated['title'],
            'category'            => $validated['category'],
            'start_date'          => Carbon::parse($validated['start_date']),
            'end_date'            => Carbon::parse($validated['end_date']),
            'registration_open'  => Carbon::parse($validated['registration_open']),
            'registration_close'    => Carbon::parse($validated['registration_close']),
            'max_participants'    => $validated['max_participants'] ?? null,
            'price'               => $validated['price'] ?? null,
            'description'         => $validated['description'] ?? null,
            'file_id'             => $fileId,
            'organizer_id'        => $request->user()->id,
        ];

        // 4) Création de l'événement
        $event = Event::create($data);

        // 5) Gestion de l’adresse, si renseignée
        if (
            !empty($validated['address']) ||
            !empty($validated['city']) ||
            !empty($validated['postal_code']) ||
            !empty($validated['country'])
        ) {
            // Splitting address
            $streetTypes = ['rue', 'avenue', 'boulevard', 'chemin', 'impasse', 'place', 'route', 'allée', 'quai', 'cours', 'passage', 'voie', 'square', 'faubourg'];
            $parts = preg_split('/\s+/', trim($validated['address']));
            $houseNumber = '';
            $streetName  = '';

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

            $event->address()->updateOrCreate(
                ['label' => 'location'],
                [
                    'house_number' => trim($houseNumber),
                    'street_name'  => trim($streetName),
                    'city'         => $validated['city'] ?? null,
                    'postal_code'  => $validated['postal_code'] ?? null,
                    'country'      => $validated['country'] ?? null,
                ]
            );
        }
        return redirect()
            ->route('events.create', $event)
            ->with('success', 'Événement et adresse enregistrés avec succès.');
    }


    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        return response()->json($event, Response::HTTP_OK);
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, Event $event)
    {
        $event->update($request->validated());

        return response()->json($event, Response::HTTP_OK);
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Event $event)
    {
        $event->delete();

        return response()->json(null, Response::HTTP_NO_CONTENT);
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
