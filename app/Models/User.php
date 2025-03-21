<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use App\Casts\PhoneCast;
use App\Models\File;

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
}
