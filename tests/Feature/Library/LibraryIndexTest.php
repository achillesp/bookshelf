<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('library.index'));

    $response->assertRedirect(route('login'));
});

test('the Library lists only the Reader entries with their Book and Authors', function () {
    $reader = User::factory()->create();
    $author = Author::factory()->create(['name' => 'Frank Herbert']);
    $book = Book::factory()->hasAttached($author)->create(['title' => 'Dune', 'page_count_median' => 608]);
    $entry = LibraryEntry::factory()->for($reader)->for($book)->completed('2026-10-15', '2026-10-01')->create(['rating' => 5]);
    LibraryEntry::factory()->create();

    $response = $this->actingAs($reader)->get(route('library.index'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('library/Index')
            ->has('entries.data', 1)
            ->where('entries.data.0.id', $entry->id)
            ->where('entries.data.0.status', 'completed')
            ->where('entries.data.0.rating', 5)
            ->where('entries.data.0.started_on', '2026-10-01')
            ->where('entries.data.0.finished_on', '2026-10-15')
            ->where('entries.data.0.book.title', 'Dune')
            ->where('entries.data.0.book.page_count_median', 608)
            ->where('entries.data.0.book.authors.0.name', 'Frank Herbert')
            ->missing('entries.data.0.book.authors.0.pivot')
            ->where('filters', ['status' => null, 'sort' => 'updated']),
        );
});

test('the Library filters by Reading Status', function () {
    $reader = User::factory()->create();
    $reading = LibraryEntry::factory()->for($reader)->reading()->create();
    LibraryEntry::factory()->for($reader)->completed()->create();
    LibraryEntry::factory()->for($reader)->create();

    $response = $this->actingAs($reader)->get(route('library.index', ['status' => 'reading']));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('entries.data', 1)
        ->where('entries.data.0.id', $reading->id)
        ->where('filters.status', 'reading'),
    );
});

test('the Library sorts by recently updated by default', function () {
    $reader = User::factory()->create();
    $older = LibraryEntry::factory()->for($reader)->create(['updated_at' => '2026-10-01 10:00:00']);
    $newer = LibraryEntry::factory()->for($reader)->create(['updated_at' => '2026-11-01 10:00:00']);

    $response = $this->actingAs($reader)->get(route('library.index'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('entries.data.0.id', $newer->id)
        ->where('entries.data.1.id', $older->id),
    );
});

test('the Library sorts by title', function () {
    $reader = User::factory()->create();
    $zebra = LibraryEntry::factory()->for($reader)->for(Book::factory()->state(['title' => 'Zebra']))->create();
    $apple = LibraryEntry::factory()->for($reader)->for(Book::factory()->state(['title' => 'Apple']))->create();

    $response = $this->actingAs($reader)->get(route('library.index', ['sort' => 'title']));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('entries.data.0.id', $apple->id)
        ->where('entries.data.1.id', $zebra->id)
        ->where('filters.sort', 'title'),
    );
});

test('the Library sorts by Finished Date, latest first and unfinished last', function () {
    $reader = User::factory()->create();
    $unfinished = LibraryEntry::factory()->for($reader)->reading()->create();
    $earlier = LibraryEntry::factory()->for($reader)->completed('2026-03-01')->create();
    $later = LibraryEntry::factory()->for($reader)->completed('2026-09-01')->create();

    $response = $this->actingAs($reader)->get(route('library.index', ['sort' => 'finished']));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('entries.data.0.id', $later->id)
        ->where('entries.data.1.id', $earlier->id)
        ->where('entries.data.2.id', $unfinished->id),
    );
});

test('the Library paginates 20 entries a page and keeps the filters in the page links', function () {
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->reading()->count(21)->create();

    $response = $this->actingAs($reader)->get(route('library.index', ['status' => 'reading', 'sort' => 'title']));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('entries.data', 20)
        ->where('entries.next_page_url', route('library.index', ['status' => 'reading', 'sort' => 'title', 'page' => 2])),
    );
});

test('the Library rejects an unknown filter or sort', function (array $query, string $field) {
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('library.index', $query));

    $response->assertSessionHasErrors($field);
})->with([
    'unknown status' => [['status' => 'abandoned'], 'status'],
    'sort by an arbitrary column' => [['sort' => 'user_id'], 'sort'],
]);
