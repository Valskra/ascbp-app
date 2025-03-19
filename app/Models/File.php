<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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

    public function fileable()
    {
        return $this->morphTo();
    }
}
