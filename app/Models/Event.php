<?php

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
     * L'adresse de l'événement
     * Relation avec la table event_addresses
     */
    public function address()
    {
        return $this->hasOne(EventAddress::class);
    }

    /**
     * Les participants à l'événement via la table registrations
     */
    public function participants()
    {
        return $this->belongsToMany(User::class, 'registrations', 'event_id', 'user_id')
            ->withPivot('registration_date', 'amount')
            ->withTimestamps();
    }

    /**
     * Les inscriptions à l'événement
     * Relation avec la table registrations
     */
    public function registrations()
    {
        return $this->hasMany(Registration::class);
    }



    /**
     * Vérifie si l'utilisateur peut s'inscrire à cet événement
     */
    public function canUserRegister($user = null): array
    {
        $now = now();
        $result = ['can_register' => false, 'reason' => null];

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

        if (!$user) {
            $result['reason'] = 'not_authenticated';
            return $result;
        }

        if ($this->participants->contains($user->id)) {
            $result['reason'] = 'already_registered';
            return $result;
        }

        if ($this->max_participants && $this->participants()->count() >= $this->max_participants) {
            $result['reason'] = 'event_full';
            return $result;
        }

        if ($this->members_only && !$user->hasMembership()) {
            $result['reason'] = 'members_only';
            return $result;
        }

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


    /**
     * Vérifier si l'événement est payant pour un utilisateur donné
     */
    public function isPaidFor(User $user = null): bool
    {
        if (empty($this->price)) {
            return false;
        }

        $priceMatch = preg_match('/\d+/', $this->price, $matches);
        $numericPrice = $priceMatch ? (float) $matches[0] : 0;

        return $numericPrice > 0;
    }

    /**
     * Obtenir le prix numérique
     */
    public function getNumericPriceAttribute(): float
    {
        if (empty($this->price)) {
            return 0;
        }

        $priceMatch = preg_match('/\d+/', $this->price, $matches);
        return $priceMatch ? (float) $matches[0] : 0;
    }

    /**
     * Scope pour les événements à venir
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_date', '>=', now());
    }

    /**
     * Scope pour les événements d'une catégorie
     */
    public function scopeOfCategory($query, $category)
    {
        return $query->where('category', $category);
    }

    /**
     * Vérifier si l'événement est complet
     */
    public function isFull(): bool
    {
        if (!$this->max_participants) {
            return false;
        }

        return $this->registrations()->count() >= $this->max_participants;
    }

    /**
     * Vérifier si les inscriptions sont ouvertes
     */
    public function isRegistrationOpen(): bool
    {
        $now = now();

        if ($this->registration_open && $now < $this->registration_open) {
            return false;
        }

        if ($this->registration_close && $now > $this->registration_close) {
            return false;
        }

        return $this->start_date > $now;
    }

    /**
     * Vérifier si on peut modifier le nombre maximum de participants
     */
    public function canChangeMaxParticipants(int $newMax = null): bool
    {
        if ($newMax === null) {
            return true; // Illimité autorisé
        }

        $currentCount = $this->registrations()->count();
        return $newMax >= $currentCount;
    }

    /**
     * Obtenir le nombre minimum autorisé pour max_participants
     */
    public function getMinAllowedParticipants(): int
    {
        return max(1, $this->registrations()->count());
    }

    /**
     * Vérifier si l'événement peut être modifié sans risque
     */
    public function canBeSafelyModified(): array
    {
        $now = now();
        $currentParticipants = $this->registrations()->count();

        $restrictions = [
            'has_participants' => $currentParticipants > 0,
            'event_started' => $this->start_date <= $now,
            'registration_open' => $this->isRegistrationOpen(),
            'participants_count' => $currentParticipants,
            'min_participants_allowed' => $this->getMinAllowedParticipants(),
        ];

        $restrictions['can_modify_safely'] = !$restrictions['event_started'];

        return $restrictions;
    }

    /**
     * Obtenir des suggestions pour la modification
     */
    public function getModificationSuggestions(): array
    {
        $info = $this->canBeSafelyModified();
        $suggestions = [];

        if ($info['has_participants']) {
            $suggestions[] = "Cet événement a {$info['participants_count']} participant(s) inscrit(s)";
            $suggestions[] = "Le nombre maximum ne peut pas être inférieur à {$info['min_participants_allowed']}";
        }

        if ($info['event_started']) {
            $suggestions[] = "L'événement a déjà commencé - modifications limitées";
        }

        if ($info['registration_open']) {
            $suggestions[] = "Les inscriptions sont ouvertes - soyez prudent avec les modifications";
        }

        return $suggestions;
    }

    /**
     * Obtenir le nombre de participants inscrits
     */
    public function getParticipantsCountAttribute(): int
    {
        return $this->registrations()->count();
    }

    /**
     * Vérifier si un utilisateur est inscrit
     */
    public function isUserRegistered(User $user): bool
    {
        return $this->registrations()->where('user_id', $user->id)->exists();
    }

    /**
     * Les articles liés à cet événement
     */
    public function articles()
    {
        return $this->hasMany(Article::class);
    }

    /**
     * Les articles épinglés de cet événement
     */
    public function pinnedArticles()
    {
        return $this->hasMany(Article::class)->where('is_pinned', true);
    }

    /**
     * Obtenir le nombre d'articles publiés pour cet événement
     */
    public function getPublishedArticlesCountAttribute(): int
    {
        return $this->articles()->where('status', 'published')->count();
    }
}
