<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

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
}
