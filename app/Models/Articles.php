<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * 
 *
 * @property int $id
 * @property string $publish_date
 * @property string $content
 * @property int $file_id
 * @property int $user_id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \App\Models\File> $files
 * @property-read int|null $files_count
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles whereFileId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles wherePublishDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Articles whereUserId($value)
 * @mixin \Eloquent
 */
class Articles extends Model
{
    public function files()
    {
        return $this->morphMany(File::class, 'fileable');
    }
}
