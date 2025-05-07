<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    protected $fillable = [
        'title',
        'category',
        'description',
        'address',
        'city',
        'postal_code',
        'start_date',
        'end_date',
        'registration_start',
        'registration_end',
        'max_participants',
        'price',
        'file_id',        // référence au File
        'organizer_id',
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
        return $this->belongsToMany(User::class, 'registrations')
            ->withPivot('certificate_medical')
            ->withTimestamps();
    }

    /**
     * L'adresse détaillée de l'événement
     */
    public function address()
    {
        return $this->hasOne(EventAddress::class);
    }
}
