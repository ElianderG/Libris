<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Laravel\Fortify\Contracts\PasskeyUser;
use Laravel\Fortify\PasskeyAuthenticatable;
use Laravel\Fortify\TwoFactorAuthenticatable;

/**
 * @property int $id
 * @property string $name
 * @property string $email
 * @property string $affiliate_code
 * @property Carbon|null $email_verified_at
 * @property string $password
 * @property bool $is_admin
 * @property int|null $avatar_id
 * @property string|null $avatar
 * @property string|null $two_factor_secret
 * @property string|null $two_factor_recovery_codes
 * @property Carbon|null $two_factor_confirmed_at
 * @property string|null $remember_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['name', 'email', 'password'])]
#[Hidden(['password', 'two_factor_secret', 'two_factor_recovery_codes', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmail, PasskeyUser
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable, PasskeyAuthenticatable, TwoFactorAuthenticatable;

    /**
     * @var list<string>
     */
    protected $appends = [
        'avatar',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user): void {
            if (filled($user->affiliate_code)) {
                return;
            }

            $user->affiliate_code = static::newAffiliateCode();
        });

        static::deleting(function (User $user): void {
            if ($user->avatar_id !== null) {
                static::query()->whereKey($user->id)->update(['avatar_id' => null]);
            }

            $user->avatars()->get()->each(static function (UserAvatar $avatar): void {
                $avatar->delete();
            });
        });
    }

    public static function newAffiliateCode(): string
    {
        $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $length = strlen($alphabet) - 1;

        do {
            $code = '';

            for ($i = 0; $i < 8; $i++) {
                $code .= $alphabet[random_int(0, $length)];
            }
        } while (static::query()->where('affiliate_code', $code)->exists());

        return $code;
    }

    /**
     * @return HasMany<UserAvatar, $this>
     */
    public function avatars(): HasMany
    {
        return $this->hasMany(UserAvatar::class);
    }

    /**
     * @return BelongsTo<UserAvatar, $this>
     */
    public function currentAvatar(): BelongsTo
    {
        return $this->belongsTo(UserAvatar::class, 'avatar_id');
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function avatar(): Attribute
    {
        return Attribute::get(function (): ?string {
            $this->loadMissing('currentAvatar');

            return $this->currentAvatar?->url();
        });
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'two_factor_confirmed_at' => 'datetime',
        ];
    }
}
