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
        return Storage::disk('public')->url($this->path);
    }

    protected static function booted(): void
    {
        static::deleting(function (UserAvatar $avatar): void {
            $prefix = 'avatars/'.$avatar->user_id.'/';

            if (! str_starts_with($avatar->path, $prefix) || str_contains($avatar->path, '..')) {
                return;
            }

            Storage::disk('public')->delete($avatar->path);
        });
    }
}
