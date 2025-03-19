<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Articles extends Model
{
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
