<?php

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use App\Models\User;

beforeEach(function () {
    $this->travelTo('2026-11-05 10:00:00');
});

test('changing the status applies the date rules', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->reading('2026-10-01')->create();

    $response = $this->actingAs($reader)
        ->from(route('library.index'))
        ->patch(route('library.update', $entry), ['status' => ReadingStatus::Completed->value]);

    $response->assertRedirect(route('library.index'));
    $entry->refresh();
    expect($entry->status)->toBe(ReadingStatus::Completed)
        ->and($entry->started_on?->toDateString())->toBe('2026-10-01')
        ->and($entry->finished_on?->toDateString())->toBe('2026-11-05');
});

test('changing only the Rating leaves the status and dates unchanged', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->completed('2026-10-15', '2026-10-01')->create();

    $this->actingAs($reader)->patch(route('library.update', $entry), ['rating' => 4]);

    $entry->refresh();
    expect($entry->rating)->toBe(4)
        ->and($entry->status)->toBe(ReadingStatus::Completed)
        ->and($entry->started_on?->toDateString())->toBe('2026-10-01')
        ->and($entry->finished_on?->toDateString())->toBe('2026-10-15');
});

test('the Rating can be cleared', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->create(['rating' => 3]);

    $this->actingAs($reader)->patch(route('library.update', $entry), ['rating' => null]);

    expect($entry->refresh()->rating)->toBeNull();
});

test('the update rejects an invalid status or Rating', function (array $payload, string $field, string $message) {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->create(['rating' => 3]);

    $response = $this->actingAs($reader)->patch(route('library.update', $entry), $payload);

    $response->assertSessionHasErrors([$field => $message]);
    expect($entry->refresh()->rating)->toBe(3);
})->with([
    'unknown status' => [['status' => 'abandoned'], 'status', 'The selected status is invalid.'],
    'rating below 1' => [['rating' => 0], 'rating', 'The rating field must be between 1 and 5.'],
    'rating above 5' => [['rating' => 6], 'rating', 'The rating field must be between 1 and 5.'],
    'half star' => [['rating' => 3.5], 'rating', 'The rating field must be an integer.'],
]);

test('another Reader entry cannot be updated', function () {
    $entry = LibraryEntry::factory()->create(['rating' => 3]);
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->patch(route('library.update', $entry), ['rating' => 1]);

    $response->assertNotFound();
    expect($entry->refresh()->rating)->toBe(3);
});
