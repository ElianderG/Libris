<?php

namespace App\Models;

use App\Models\Concerns\HasUniqueSlug;
use App\Support\ProductRules;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $category_id
 * @property string $title
 * @property string $slug
 * @property string $author
 * @property string $description
 * @property string|null $cover_path
 * @property int $price_cents
 * @property string $content
 * @property bool $is_featured
 * @property bool $is_published
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Category $category
 */
#[Fillable(['category_id', 'title', 'author', 'description', 'content', 'cover_path'])]
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory, HasUniqueSlug;

    protected $attributes = [
        'is_featured' => false,
        'is_published' => false,
    ];

    /**
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    protected static function slugSourceAttribute(): string
    {
        return 'title';
    }

    protected static function slugFallback(): string
    {
        return 'livro';
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_featured' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return Attribute<int, int>
     */
    protected function priceCents(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): int => (int) $value,
            set: fn (mixed $value): int => ProductRules::parseCents($value),
        );
    }

    /**
     * @return Attribute<string|null, string|null>
     */
    protected function coverPath(): Attribute
    {
        return Attribute::make(
            get: fn (mixed $value): ?string => is_string($value) && $value !== '' ? $value : null,
            set: fn (mixed $value): ?string => ProductRules::normalizeCoverPath($value),
        );
    }
}
