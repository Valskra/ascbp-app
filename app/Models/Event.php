<?php
// app/Models/Event.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Registration;

class Event extends Model
{
    protected $fillable = [
        'title',
        'category',
        'description',
        'start_date',
        'end_date',
        'registration_open',
        'registration_close',
        'max_participants',
        'members_only',
        'requires_medical_certificate',
        'price',
        'file_id',
        'organizer_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_open' => 'datetime',
        'registration_close' => 'datetime',
        'members_only' => 'boolean',
        'requires_medical_certificate' => 'boolean',
    ];

    /**
     * L'utilisateur qui a créé l'événement (organisateur)
     */
    public function organizer()
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    /**
     * Le fichier d'illustration lié à l'événement
     */
    public function illustration()
    {
        return $this->belongsTo(File::class, 'file_id');
    }

    /**
     * Les participants à l'événement
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'registrations', 'event_id', 'user_id')
            ->withPivot('registration_date', 'amount')
            ->withTimestamps();
    }

    // Relation pour les inscriptions
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }

    /**
     * L'adresse détaillée de l'événement
     */
    public function address()
    {
        return $this->hasOne(EventAddress::class);
    }

    /**
     * Vérifie si l'utilisateur peut s'inscrire à cet événement
     */
    public function canUserRegister($user = null): array
    {
        $now = now();
        $result = ['can_register' => false, 'reason' => null];

        // Vérifications temporelles
        if ($this->registration_open && $now < $this->registration_open) {
            $result['reason'] = 'registration_not_open';
            return $result;
        }

        if ($this->registration_close && $now > $this->registration_close) {
            $result['reason'] = 'registration_closed';
            return $result;
        }

        if ($this->start_date < $now) {
            $result['reason'] = 'event_started';
            return $result;
        }

        // Vérification de l'utilisateur connecté
        if (!$user) {
            $result['reason'] = 'not_authenticated';
            return $result;
        }

        // Vérification si déjà inscrit
        if ($this->participants->contains($user->id)) {
            $result['reason'] = 'already_registered';
            return $result;
        }

        // Vérification du nombre maximum de participants
        if ($this->max_participants && $this->participants()->count() >= $this->max_participants) {
            $result['reason'] = 'event_full';
            return $result;
        }

        // Vérification si réservé aux adhérents
        if ($this->members_only && !$user->hasMembership()) {
            $result['reason'] = 'members_only';
            return $result;
        }

        // Vérification du certificat médical si requis
        if ($this->requires_medical_certificate && !$this->userHasValidMedicalCertificate($user)) {
            $result['reason'] = 'requires_medical_certificate';
            return $result;
        }

        $result['can_register'] = true;
        return $result;
    }

    /**
     * Vérifie si l'utilisateur a un certificat médical valide
     */
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
}
