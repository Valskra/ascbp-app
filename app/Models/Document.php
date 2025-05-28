<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property string $title
 * @property string|null $expiration_date
 * @property string|null $metadata
 * @property int $file_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\File $file
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereExpirationDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Document whereUserId($value)
 * @mixin \Eloquent
 */

class Document extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'expiration_date',
        'metadata',
        'file_id',
        'user_id',
    ];

    protected $appends = ['public_url'];

    protected static function booted()
    {
        static::deleting(function (Document $document) {
            if ($document->file) {
                $document->file->delete();
            }
        });
    }

    /* relations */
    public function file()
    {
        return $this->belongsTo(File::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * URL publique avec token complexe
     */
    public function getPublicUrlAttribute(): ?string
    {
        return $this->file?->url;
    }

    /**
     * URL protégée pour les admins
     */
    public function getAdminUrlAttribute(): ?string
    {
        return $this->file?->admin_url;
    }
}
