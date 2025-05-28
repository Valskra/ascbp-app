<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Casts\PhoneCast;

/**
 * 
 *
 * @property int $id
 * @property string|null $label
 * @property string $firstname
 * @property string|null $lastname
 * @property $phone
 * @property string|null $email
 * @property string|null $relation
 * @property int $priority
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereEmail($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereFirstname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereLabel($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereLastname($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact wherePhone($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact wherePriority($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereRelation($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Contact whereUserId($value)
 * @mixin \Eloquent
 */
class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'firstname',
        'lastname',
        'phone',
        'email',
        'relation',
        'priority',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'phone' => PhoneCast::class
        ];
    }
}
