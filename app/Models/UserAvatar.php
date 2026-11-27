<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $user_id
 * @property string $path
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Fillable(['path'])]
class UserAvatar extends Model
{
    public const MAX_PER_USER = 8;

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function url(): string
    {
        if (preg_match('#^avatars/[1-9][0-9]*/[0-9a-f-]{36}\.jpg$#', $this->path) !== 1) {
            return '';
        }

        return '/'.$this->path;
    }

    protected static function booted(): void
    {
        static::deleting(function (UserAvatar $avatar): void {
            $prefix = 'avatars/'.(int) $avatar->user_id.'/';

            if (! str_starts_with($avatar->path, $prefix) || str_contains($avatar->path, '..')) {
                return;
            }

            Storage::disk('avatars')->delete($avatar->path);
        });
    }
}
