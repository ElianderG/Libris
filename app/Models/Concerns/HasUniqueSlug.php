<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * @phpstan-require-extends Model
 */
trait HasUniqueSlug
{
    protected static function bootHasUniqueSlug(): void
    {
        static::creating(function (Model $model): void {
            if (filled($model->getAttribute('slug'))) {
                return;
            }

            $source = $model->getAttribute(static::slugSourceAttribute());

            $model->setAttribute('slug', static::nextSlug(is_string($source) ? $source : ''));
        });
    }

    protected static function slugSourceAttribute(): string
    {
        return 'name';
    }

    protected static function slugFallback(): string
    {
        return 'item';
    }

    protected static function nextSlug(string $source): string
    {
        $base = Str::slug($source);
        $base = mb_substr($base !== '' ? $base : static::slugFallback(), 0, 240);

        $slug = $base;
        $suffix = 2;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }
}
