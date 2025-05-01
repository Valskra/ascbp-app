<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = [
        'manage_admin',
        'admin_access',
        'manage_events',
        'create_events',
        'manage_members',
        'manage_articles',
        'create_articles',
    ];

    public function roles()
    {
        return $this->hasMany(Role::class);
    }
}
