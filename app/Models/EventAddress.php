<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventAddress extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_id',
        'street',
        'city',
        'postal_code',
        'country',
        'latitude',
        'longitude',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class);
    }
}
