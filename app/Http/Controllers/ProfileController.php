<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $request->user()->load('homeAddress', 'birthAddress'),
        ]);
    }

    public function profile(Request $request): Response
    {
        return Inertia::render('Profile/Profile', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
            'user' => $request->user()->load('homeAddress', 'birthAddress'),

        ]);
    }
    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    // Mise à jour des contacts d'urgence
    public function updateContacts(Request $request)
    {
        $validated = $request->validate([
            'contacts' => 'required|array|max:2',
            'contacts.*.firstname' => 'required|string|max:50',
            'contacts.*.lastname' => 'required|string|max:50',
            'contacts.*.phone' => 'required|string|max:15',
            'contacts.*.email' => 'nullable|email|max:100',
            'contacts.*.relation' => 'required|string|max:50',
            'contacts.*.priority' => 'required|integer|min:1|max:5',
        ]);

        $user = $request->user();

        // Nettoyage et mise à jour optimisée
        $user->contacts()->delete();
        $user->contacts()->createMany($validated['contacts']);

        return Redirect::route('profile.edit')->with('status', 'contacts-updated');
    }

    public function updateAddress(Request $request)
    {
        $validated = $request->validate([
            'address' => 'required|string|max:255',
            'city' => 'required|string|max:100',
            'postal_code' => 'required|string|max:5',
            'country' => 'required|string|max:100',

        ]);

        $user = $request->user();

        // Liste des types de voies en français
        $street_types = ['rue', 'avenue', 'boulevard', 'chemin', 'impasse', 'place', 'route', 'allée', 'quai', 'cours', 'passage', 'voie', 'square', 'faubourg'];

        // Diviser l'adresse en mots
        $address_parts = explode(' ', trim($validated['address']));
        $house_number = '';
        $street_name = '';

        // Recherche du premier mot correspondant à un type de voie
        foreach ($address_parts as $index => $part) {
            if (in_array(strtolower($part), $street_types)) {
                // Tout ce qui est avant le type de voie est le numéro de maison
                $house_number = implode(' ', array_slice($address_parts, 0, $index));
                // Tout ce qui est après est le nom de rue
                $street_name = implode(' ', array_slice($address_parts, $index));
                break;
            }
        }

        // Si aucun type de voie trouvé, tout est considéré comme `street_name`
        if (empty($street_name)) {
            $street_name = $validated['address'];
            $house_number = ''; // Pas de numéro détecté
        }

        // Enregistrer dans la base de données
        $user->homeAddress()->updateOrCreate(
            ['label' => 'home'],
            [
                'house_number' => trim($house_number),
                'street_name' => trim($street_name),
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country']
            ]
        );

        return back()->with('status', 'home-address-updated');
    }


    public function updateEmail(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|email|max:100',
            'email_pro' => 'nullable|email|max:100',
        ]);

        $user = $request->user();

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }
        if ($request->user()->isDirty('email_pro')) {
            $request->user()->email_pro_verified_at = null;
        }

        $user->email = $validated['email'];
        $user->email_pro = $validated['email_pro'] ?? null;

        $user->save();

        return Redirect::route('profile.edit');
    }

    public function updatePhone(Request $request)
    {
        $validated = $request->validate([
            'phone' => 'required|string|max:100',
            'phone_secondary' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        $user->phone = $validated['phone'];
        $user->phone_secondary = $validated['phone_secondary'] ?? null;

        $user->save();

        return Redirect::route('profile.edit');
    }


    public function updateBirth(Request $request)
    {
        $validated = $request->validate([
            'birth_date' => 'required|date|before:today',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:5',
            'country' => 'nullable|string|max:100',
        ]);

        $user = $request->user();

        // 1️⃣ Mettre à jour la date de naissance dans `users`
        $user->birth_date = $validated['birth_date'];
        $user->save();

        // 2️⃣ Mettre à jour ou créer l'adresse de naissance dans `addresses`
        $user->birthAddress()->updateOrCreate(
            ['label' => 'birth'],
            [
                'city' => $validated['city'],
                'postal_code' => $validated['postal_code'],
                'country' => $validated['country'],
            ]
        );

        return back()->with('status', 'birth-updated');
    }



    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
