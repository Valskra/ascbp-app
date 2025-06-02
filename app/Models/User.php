<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Carbon\Carbon;
use App\Models\File;
use App\Casts\PhoneCast;
use Illuminate\Support\Facades\Storage;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $with = ['homeAddress', 'birthAddress', 'contacts', 'profilePicture'];
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


    protected $appends = [
        'is_admin',
        'is_animator',
        'membership_status',
        'first_membership_date',
        'membership_time_left',
    ];

    protected static function booted()
    {
        static::deleting(function (User $user) {
            if ($user->profilePicture) {
                Storage::disk($user->profilePicture->disk)
                    ->delete($user->profilePicture->path);
                $user->profilePicture->delete();
            }
        });
    }

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

    public function contacts()
    {
        return $this->hasMany(Contact::class);
    }

    public function profilePicture()
    {
        return $this->morphOne(File::class, 'fileable');
    }


    ///////////
    //
    // Adhésions
    //
    ///////////

    /**
     * Relation avec les adhésions (memberships)
     */
    public function memberships()
    {
        return $this->hasMany(Membership::class);
    }

    public function hasMembership(): bool
    {
        return $this->membership_status === 1;
    }

    /**
     * 1. Déterminer le statut d'adhésion de l'utilisateur
     *
     * @return int
     * -1 = compte désactivé ou banni
     *  0 = jamais adhérent ou adhésion expirée depuis +1 an
     *  1 = adhérent actif (cotisation à jour)
     *  2 = ancien adhérent depuis moins d'un an
     */
    public function getMembershipStatusAttribute(): int
    {
        if ($this->status === 'disabled' || $this->status === 'banned') {
            return -1;
        }

        $latest = $this->memberships
            ->sortByDesc('contribution_date')
            ->first();

        if (!$latest) {
            return 0;
        }

        $expirationDate = Carbon::parse($latest->contribution_date)->addYear()->subDay();
        $now = now();

        if ($expirationDate >= $now) {
            return 1;
        }

        if ($expirationDate >= $now->subYear()) {
            return 2;
        }

        return 0;
    }

    /**
     * 2. Récupérer la date de première adhésion
     *
     * @return Carbon|null
     */
    public function getFirstMembershipDateAttribute(): ?Carbon
    {
        $first = $this->memberships
            ->sortBy('contribution_date')
            ->first();

        return $first ? Carbon::parse($first->contribution_date) : null;
    }

    /**
     * 3. Temps restant avant l'expiration de l'adhésion
     *
     * @return int|null
     * Nombre de jours restant, ou null si pas d'adhésion
     */
    public function getMembershipTimeLeftAttribute(): ?int
    {
        $latest = $this->memberships
            ->sortByDesc('contribution_date')
            ->first();

        if (!$latest) {
            return null;
        }

        $expirationDate = Carbon::parse($latest->contribution_date)->addYear()->subDay();
        $now = now();

        return $expirationDate->isPast() ? 0 : $now->diffInDays($expirationDate);
    }

    /**
     * 4. Formater le temps restant pour l'affichage
     *
     * @param int|null $days
     * @return array|null
     */
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

    public function timeSinceJoin(): ?int
    {
        $first = $this->memberships
            ->sortBy('contribution_date')
            ->first();

        return $first ? now()->diffInDays($first->contribution_date) : null;
    }

    public function timeLeft(): ?int
    {
        $latest = $this->memberships
            ->sortByDesc('contribution_date')
            ->first();

        if (!$latest) return null;

        $endDate = Carbon::parse($latest->contribution_date)->addYear()->subDay();

        return $endDate->isPast() ? 0 : now()->diffInDays($endDate);
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

    public function hasPermission(string $permissions): bool
    {
        foreach ($this->roles as $role) {
            if ($role->permissions && $role->permissions->{$permissions} == 1) {
                return true;
            }
        }
        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('admin_access');
    }

    public function getIsAdminAttribute(): bool
    {
        return $this->hasPermission('admin_access');
    }

    /**
     * Détermine si l'utilisateur est Animateur.
     * Condition : permission "admin_access", "manage_event" ou "create_events"
     */
    public function getIsAnimatorAttribute(): bool
    {
        return $this->hasPermission('admin_access')
            || $this->hasPermission('manage_event')
            || $this->hasPermission('create_events');
    }

    /**
     * Détermine si l'utilisateur est Super Animateur.
     * Condition : permission "admin_access" ou "manage_event"
     */
    public function getIsSuperAnimatorAttribute(): bool
    {
        return $this->hasPermission('admin_access')
            || $this->hasPermission('manage_event');
    }

    ///////////
    //
    // Files
    //
    ///////////

    public function uploadLinks()
    {
        return $this->hasMany(UploadLink::class);
    }

    public function latestUploadLink(): ?UploadLink
    {
        return $this->uploadLinks()
            ->orderByDesc('created_at')
            ->first();
    }


    /**
     * Récupère tous les liens générés par l’utilisateur, du plus récent au plus ancien.
     *
     * @return \Illuminate\Database\Eloquent\Collection<UploadLink>
     */
    public function getAllUploadLinks()
    {
        return $this->uploadLinks()
            ->orderByDesc('created_at')
            ->get();
    }
}
