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
        'country'
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
