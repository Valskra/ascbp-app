<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'contribution_date',
        'amount',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'contribution_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
