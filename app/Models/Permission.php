<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property int $manage_admin
 * @property int $admin_access
 * @property int $manage_events
 * @property int $create_events
 * @property int $manage_members
 * @property int $manage_articles
 * @property int $create_articles
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\Role> $roles
 * @property-read int|null $roles_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereAdminAccess($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreateArticles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreateEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereManageAdmin($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereManageArticles($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereManageEvents($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereManageMembers($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Permission whereUpdatedAt($value)
 * @mixin \Eloquent
 */
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
