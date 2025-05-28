<?php
// app/Models/File.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * 
 *
 * @property int $id
 * @property int|null $fileable_id
 * @property string|null $fileable_type
 * @property string $name
 * @property string $extension
 * @property string|null $mimetype
 * @property int|null $size
 * @property string $hash
 * @property string $path
 * @property string $disk
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Model|\Eloquent|null $fileable
 * @property-read string $url
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereDisk($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereExtension($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFileableId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereFileableType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereHash($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereMimetype($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File wherePath($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereSize($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|File whereUpdatedAt($value)
 * @mixin \Eloquent
 */


class File extends Model
{
    use HasFactory;

    protected $fillable = [
        'fileable_id',
        'fileable_type',
        'name',
        'extension',
        'mimetype',
        'size',
        'hash',
        'path',
        'disk',
    ];

    protected $appends = ['url'];

    protected static function booted()
    {
        static::deleting(function (File $file) {
            Storage::disk($file->disk)->delete($file->path);
        });
    }

    /**
     * URL publique avec token complexe
     */
    public function getUrlAttribute(): string
    {
        if ($this->disk === 's3' && str_contains($this->path, 'certificate/')) {
            // Extraire le token du chemin
            $pathParts = explode('/', $this->path);
            $filename = end($pathParts);
            $userId = $pathParts[1] ?? null;

            if ($userId && $filename) {
                // Extraire le token du nom de fichier
                $parts = explode('_', pathinfo($filename, PATHINFO_FILENAME));
                $token = $parts[0] ?? null;

                if ($token) {
                    return route('certificate.public', [
                        'userId' => $userId,
                        'token' => $filename
                    ]);
                }
            }
        }

        // Fallback vers l'URL S3 directe
        if ($this->disk === 's3') {
            return rtrim(env('AWS_URL'), '/') . '/' . ltrim($this->path, '/');
        }

        return asset("storage/{$this->path}");
    }

    public function fileable()
    {
        return $this->morphTo();
    }

    /**
     * Relation avec les documents
     */
    public function documents()
    {
        return $this->hasMany(Document::class);
    }
}
