<?php

namespace Database\Factories;

use App\Models\Author;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Author>
 */
class AuthorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'openlibrary_id' => 'OL'.fake()->unique()->numberBetween(1, 99_999_999).'A',
            'name' => fake()->name(),
        ];
    }
}
