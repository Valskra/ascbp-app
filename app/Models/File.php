<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

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
        'path',
        'disk',
    ];

    protected $appends = ['url'];



    public function getUrlAttribute(): string
    {
        if ($this->disk === 's3') {
            return rtrim(env('AWS_URL'), '/') . '/' . ltrim($this->path, '/');
        }

        return asset("storage/{$this->path}");
    }



    public function fileable()
    {
        return $this->morphTo();
    }
}
