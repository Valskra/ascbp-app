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
        'postal_code',
        'city',
        'country',
        'additional_info',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    /**
     * Accesseur pour obtenir l'adresse complète
     */
    public function getFullAddressAttribute(): string
    {
        $parts = array_filter([
            $this->house_number,
            $this->street_name,
            $this->postal_code,
            $this->city,
            $this->country
        ]);

        return implode(', ', $parts);
    }

    /**
     * Accesseur pour obtenir la rue complète (numéro + nom)
     */
    public function getStreetAttribute(): string
    {
        return trim($this->house_number . ' ' . $this->street_name);
    }
}
