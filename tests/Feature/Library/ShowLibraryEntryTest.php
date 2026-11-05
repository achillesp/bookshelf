<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $entry = LibraryEntry::factory()->create();

    $response = $this->get(route('library.show', $entry));

    $response->assertRedirect(route('login'));
});

test('the entry page shows the Book and the Reader record', function () {
    $reader = User::factory()->create();
    $book = Book::factory()->hasAttached(Author::factory()->state(['name' => 'Frank Herbert']))
        ->create(['title' => 'Dune', 'page_count_median' => 608]);
    $entry = LibraryEntry::factory()->for($reader)->for($book)
        ->completed('2026-10-15', '2026-10-01')
        ->create(['rating' => 5, 'notes' => 'Spice.']);

    $response = $this->actingAs($reader)->get(route('library.show', $entry));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('library/Show')
            ->where('entry.id', $entry->id)
            ->where('entry.status', 'completed')
            ->where('entry.rating', 5)
            ->where('entry.notes', 'Spice.')
            ->where('entry.started_on', '2026-10-01')
            ->where('entry.finished_on', '2026-10-15')
            ->where('entry.book.title', 'Dune')
            ->where('entry.book.page_count_median', 608)
            ->where('entry.book.authors.0.name', 'Frank Herbert'),
        );
});

test('another Reader entry page is not found', function () {
    $entry = LibraryEntry::factory()->create();
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('library.show', $entry));

    $response->assertNotFound();
});
