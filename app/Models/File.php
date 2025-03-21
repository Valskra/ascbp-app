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



    public function getUrlAttribute()
    {
        // Cas S3 (OVH)
        if ($this->disk === 's3') {
            // Construit l'URL complète : endpoint + bucket + path
            return env('AWS_ENDPOINT') . '/' . env('AWS_BUCKET') . '/' . $this->path;
        }

        // Cas local
        return asset("storage/{$this->path}");
    }


    public function fileable()
    {
        return $this->morphTo();
    }
}
