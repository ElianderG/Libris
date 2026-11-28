<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $prefix
 * @property string $key_hash
 * @property Carbon|null $last_used_at
 * @property Carbon|null $revoked_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 */
#[Hidden(['key_hash'])]
class ApiKey extends Model
{
    private const PREFIX_LENGTH = 12;

    private const RANDOM_LENGTH = 40;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maskedPrefix(): string
    {
        return $this->prefix.'…';
    }

    public static function issue(User $user): string
    {
        return DB::transaction(function () use ($user): string {
            static::query()
                ->where('user_id', $user->id)
                ->whereNull('revoked_at')
                ->lockForUpdate()
                ->update(['revoked_at' => now()]);

            $plain = 'libris_'.Str::random(self::RANDOM_LENGTH);

            $key = new self;
            $key->user_id = $user->id;
            $key->prefix = substr($plain, 0, self::PREFIX_LENGTH);
            $key->key_hash = hash('sha256', $plain);
            $key->save();

            return $plain;
        });
    }
}
