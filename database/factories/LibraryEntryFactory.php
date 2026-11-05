<?php

namespace Database\Factories;

use App\Enums\ReadingStatus;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LibraryEntry>
 */
class LibraryEntryFactory extends Factory
{
    /**
     * Define the model's default state: a Book the Reader wants to read.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'book_id' => Book::factory(),
            'status' => ReadingStatus::WantToRead,
            'rating' => null,
            'notes' => null,
            'started_on' => null,
            'finished_on' => null,
        ];
    }

    /**
     * A Book the Reader is reading, started on the given day.
     */
    public function reading(string $startedOn = '2026-10-01'): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReadingStatus::Reading,
            'started_on' => $startedOn,
            'finished_on' => null,
        ]);
    }

    /**
     * A Book the Reader completed on the given day.
     */
    public function completed(string $finishedOn = '2026-10-15', ?string $startedOn = null): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => ReadingStatus::Completed,
            'started_on' => $startedOn ?? $finishedOn,
            'finished_on' => $finishedOn,
        ]);
    }
}
