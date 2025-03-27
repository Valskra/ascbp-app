<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Casts\PhoneCast;
use App\Models\File;
use Carbon\Carbon;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'firstname',
        'lastname',
        'birth_date',
        'phone',
        'phone_secondary',
        'email',
        'email_pro',
        'iban',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'birth_date' => 'date:Y-m-d',
            'password' => 'hashed',
            'phone' => PhoneCast::class,
            'phone_secondary' => PhoneCast::class,
        ];
    }


    //////////
    //
    // Profile
    //
    //////////

    public function homeAddress()
    {
        return $this->hasOne(Address::class)->where('label', 'home');
    }

    public function birthAddress()
    {
        return $this->hasOne(Address::class)->where('label', 'birth')->withDefault([
            'city' => '',
            'postal_code' => '',
            'country' => '',
        ]);
    }

    // Dans User.php
    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function profilePicture()
    {
        return $this->morphOne(File::class, 'fileable');
    }


    ////////////
    //
    // Adhésions
    //
    ////////////


    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }


    public function hasMembership(): bool
    {
        $latest = $this->memberships()
            ->orderByDesc('join_date')
            ->first();

        return $latest && Carbon::parse($latest->join_date)->greaterThanOrEqualTo(now()->subYear());
    }



    public function timeSinceJoin(): ?int
    {
        $first = $this->memberships()->orderBy('join_date')->first();
        return $first ? Carbon::parse($first->join_date)->diffInDays() : null;
    }

    public function timeLeft(): ?int
    {
        $latest = $this->memberships()->orderByDesc('join_date')->first();

        if (!$latest) return null;

        $endDate = Carbon::parse($latest->join_date)->addYear()->subDay();
        return $endDate->isPast() ? 0 : now()->diffInDays($endDate);
    }

    public function getTimeToScreen(?int $days): ?array
    {
        if (is_null($days)) return null;

        if ($days < 30) {
            return [$days, 'd'];
        } elseif ($days < 365) {
            return [floor($days / 30), 'm'];
        } else {
            return [floor($days / 365), 'y'];
        }
    }


    ////////
    //
    // Roles
    //
    ////////

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'user_roles');
    }

    // Vérifie si l'utilisateur a un rôle donné
    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    // Vérifie s'il a une permission précise (via les rôles)
    public function hasPermission(string $permission): bool
    {
        foreach ($this->roles as $role) {
            if ($role->permission && $role->permission->$permission === true) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('admin_access');
    }
}
