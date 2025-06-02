<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string|null $label
 * @property string|null $house_number
 * @property string|null $street_name
 * @property string|null $postal_code
 * @property string|null $city
 * @property string|null $country
 * @property string|null $additional_info
 * @property int $event_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Event $event
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereAdditionalInfo($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereCity($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereCountry($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereEventId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereHouseNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress wherePostalCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereStreetName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|EventAddress whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
        'country'
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
