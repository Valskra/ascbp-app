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
        'price',
        'file_id',
        'organizer_id',
    ];

    protected $casts = [
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'registration_open' => 'datetime',
        'registration_close' => 'datetime',
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
}
