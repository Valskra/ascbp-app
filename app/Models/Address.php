<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Address extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'label', 'house_number', 'street_name', 'city', 'postal_code', 'country'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
