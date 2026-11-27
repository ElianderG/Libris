<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category_id' => Category::factory(),
            'title' => fake()->unique()->sentence(3),
            'author' => fake()->name(),
            'description' => fake()->paragraph(),
            'cover_path' => null,
            'price_cents' => fake()->numberBetween(100, 20000),
            'content' => fake()->paragraphs(3, true),
            'is_featured' => false,
            'is_published' => false,
        ];
    }
}
