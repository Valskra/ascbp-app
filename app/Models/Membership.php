<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * 
 *
 * @property int $id
 * @property int $year
 * @property \Illuminate\Support\Carbon $contribution_date
 * @property string $amount
 * @property array<array-key, mixed>|null $metadata
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereContributionDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Membership whereYear($value)
 * @mixin \Eloquent
 */
class Membership extends Model
{
    use HasFactory;

    protected $fillable = [
        'year',
        'contribution_date',
        'amount',
        'metadata',
        'user_id',
    ];

    protected $casts = [
        'metadata' => 'array',
        'contribution_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
