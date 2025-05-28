<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * 
 *
 * @property int $id
 * @property int $user_id
 * @property string $token
 * @property string|null $title
 * @property \Illuminate\Support\Carbon $expires_at
 * @property \Illuminate\Support\Carbon|null $used_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereUsedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UploadLink whereUserId($value)
 * @mixin \Eloquent
 */
class UploadLink extends Model
{
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
