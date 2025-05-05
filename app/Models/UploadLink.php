<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UploadLink extends Model
{
    // app/Models/UploadLink.php
    protected $fillable = ['user_id', 'token', 'title', 'expires_at', 'used_at'];
    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->used_at !== null;
    }
    public function markAsUsed(): void
    {
        $this->update(['used_at' => now()]);
    }
}
