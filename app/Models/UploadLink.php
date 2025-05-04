<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadLink extends Model
{
    protected $fillable = ['user_id', 'token', 'title', 'expires_at'];
    protected $dates    = ['expires_at', 'used_at'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->used_at !== null;
    }
}
