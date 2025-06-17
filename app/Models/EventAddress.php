<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'label',
        'house_number',
        'street_name',
        'city',
        'postal_code',
        'country',
        'additional_info'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Obtenir l'adresse complète formatée
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            trim($this->house_number . ' ' . $this->street_name),
            $this->city,
            $this->postal_code,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Obtenir l'adresse de rue complète
     */
    public function getStreetAddressAttribute(): string
    {
        return trim($this->house_number . ' ' . $this->street_name);
    }
}
