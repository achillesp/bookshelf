<?php

use App\Enums\ReadingStatus;
use App\Models\LibraryEntry;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    $this->travelTo('2026-11-05 10:00:00');
});

/**
 * @param  array<string, mixed>  $attributes
 */
function libraryEntry(array $attributes): LibraryEntry
{
    $entry = new LibraryEntry;
    $entry->forceFill($attributes);
    $entry->syncOriginal();

    return $entry;
}

function datesOf(LibraryEntry $entry): array
{
    return [$entry->started_on?->toDateString(), $entry->finished_on?->toDateString()];
}

test('moving to Reading starts today when no Started Date is set and clears the Finished Date', function () {
    $entry = libraryEntry(['status' => ReadingStatus::Completed, 'started_on' => null, 'finished_on' => '2026-10-15']);

    $entry->changeStatus(ReadingStatus::Reading);

    expect($entry->status)->toBe(ReadingStatus::Reading)
        ->and(datesOf($entry))->toBe(['2026-11-05', null]);
});

test('moving to Reading keeps an existing Started Date', function () {
    $entry = libraryEntry(['status' => ReadingStatus::Completed, 'started_on' => '2026-09-01', 'finished_on' => '2026-10-15']);

    $entry->changeStatus(ReadingStatus::Reading);

    expect(datesOf($entry))->toBe(['2026-09-01', null]);
});

test('moving to Completed finishes today and starts today when no Started Date is set', function () {
    $entry = libraryEntry(['status' => ReadingStatus::WantToRead, 'started_on' => null, 'finished_on' => null]);

    $entry->changeStatus(ReadingStatus::Completed);

    expect($entry->status)->toBe(ReadingStatus::Completed)
        ->and(datesOf($entry))->toBe(['2026-11-05', '2026-11-05']);
});

test('moving to Completed keeps an existing Started Date', function () {
    $entry = libraryEntry(['status' => ReadingStatus::Reading, 'started_on' => '2026-10-01', 'finished_on' => null]);

    $entry->changeStatus(ReadingStatus::Completed);

    expect(datesOf($entry))->toBe(['2026-10-01', '2026-11-05']);
});

test('moving to Want to Read clears both dates', function () {
    $entry = libraryEntry(['status' => ReadingStatus::Completed, 'started_on' => '2026-10-01', 'finished_on' => '2026-10-15']);

    $entry->changeStatus(ReadingStatus::WantToRead);

    expect($entry->status)->toBe(ReadingStatus::WantToRead)
        ->and(datesOf($entry))->toBe([null, null]);
});

test('keeping the same status leaves the dates unchanged', function () {
    $entry = libraryEntry(['status' => ReadingStatus::Completed, 'started_on' => '2026-10-01', 'finished_on' => '2026-10-15']);

    $entry->changeStatus(ReadingStatus::Completed);

    expect(datesOf($entry))->toBe(['2026-10-01', '2026-10-15']);
});

test('a new entry gets the dates of its first status', function () {
    $entry = new LibraryEntry;

    $entry->changeStatus(ReadingStatus::Reading);

    expect(datesOf($entry))->toBe(['2026-11-05', null]);
});
