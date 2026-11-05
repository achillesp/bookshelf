<?php

namespace Database\Factories;

use App\Models\Book;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'openlibrary_id' => 'OL'.fake()->unique()->numberBetween(1, 99_999_999).'W',
            'title' => fake()->sentence(3),
            'first_publish_year' => fake()->numberBetween(1800, 2025),
            'cover_id' => fake()->numberBetween(1, 15_000_000),
            'page_count_median' => fake()->numberBetween(80, 1200),
        ];
    }
}
