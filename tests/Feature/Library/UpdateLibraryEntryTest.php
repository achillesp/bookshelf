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

test('the dates and Notes can be edited', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->completed('2026-10-15', '2026-10-01')->create();

    $this->actingAs($reader)->patch(route('library.update', $entry), [
        'started_on' => '2019-02-01',
        'finished_on' => '2019-03-10',
        'notes' => 'Read it on holiday.',
    ]);

    $entry->refresh();
    expect($entry->started_on?->toDateString())->toBe('2019-02-01')
        ->and($entry->finished_on?->toDateString())->toBe('2019-03-10')
        ->and($entry->notes)->toBe('Read it on holiday.')
        ->and($entry->status)->toBe(ReadingStatus::Completed);
});

test('dates sent with a status change replace the dates the status rules set', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->create();

    $this->actingAs($reader)->patch(route('library.update', $entry), [
        'status' => ReadingStatus::Completed->value,
        'started_on' => '2019-02-01',
        'finished_on' => '2019-03-10',
    ]);

    $entry->refresh();
    expect($entry->started_on?->toDateString())->toBe('2019-02-01')
        ->and($entry->finished_on?->toDateString())->toBe('2019-03-10');
});

test('a status change alone sets the Finished Date to today', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->reading('2026-10-01')->create();

    $this->actingAs($reader)->patch(route('library.update', $entry), ['status' => ReadingStatus::Completed->value]);

    expect($entry->refresh()->finished_on?->toDateString())->toBe('2026-11-05');
});

test('the update rejects future dates', function (string $field, string $message) {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->reading('2026-10-01')->create();

    $response = $this->actingAs($reader)->patch(route('library.update', $entry), [$field => '2026-11-06']);

    $response->assertSessionHasErrors([$field => $message]);
    expect($entry->refresh()->started_on?->toDateString())->toBe('2026-10-01');
})->with([
    'Started Date' => ['started_on', 'The Started Date field must be a date before or equal to today.'],
    'Finished Date' => ['finished_on', 'The Finished Date field must be a date before or equal to today.'],
]);

test('the update rejects a Finished Date before the Started Date sent with it', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->completed('2026-10-15', '2026-10-01')->create();

    $response = $this->actingAs($reader)->patch(route('library.update', $entry), [
        'started_on' => '2026-09-10',
        'finished_on' => '2026-09-01',
    ]);

    $response->assertSessionHasErrors(['finished_on' => 'The Finished Date cannot be before the Started Date.']);
    expect($entry->refresh()->started_on?->toDateString())->toBe('2026-10-01');
});

test('the update rejects a Finished Date before the stored Started Date', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->completed('2026-10-15', '2026-10-01')->create();

    $response = $this->actingAs($reader)->patch(route('library.update', $entry), ['finished_on' => '2026-09-01']);

    $response->assertSessionHasErrors(['finished_on' => 'The Finished Date cannot be before the Started Date.']);
    expect($entry->refresh()->finished_on?->toDateString())->toBe('2026-10-15');
});

test('the update rejects Notes longer than 10,000 characters', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->create();

    $response = $this->actingAs($reader)->patch(route('library.update', $entry), ['notes' => str_repeat('a', 10_001)]);

    $response->assertSessionHasErrors(['notes' => 'The Notes field must not be greater than 10000 characters.']);
    expect($entry->refresh()->notes)->toBeNull();
});
