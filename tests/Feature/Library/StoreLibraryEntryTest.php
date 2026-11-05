<?php

use App\Enums\ReadingStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;

beforeEach(function () {
    config(['services.openlibrary.contact' => 'ops@example.test']);
    $this->travelTo('2026-11-05 10:00:00');
});

test('guests are redirected to the login page', function () {
    $response = $this->post(route('library.store'), ['openlibrary_id' => 'OL893414W']);

    $response->assertRedirect(route('login'));
});

test('adding a search result creates the Book, its Authors and a Want to Read entry without another OpenLibrary call', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => openLibraryResponse('search-dune')]);
    $reader = User::factory()->create();
    $this->actingAs($reader)->get(route('search', ['q' => 'dune']));

    $response = $this->actingAs($reader)
        ->from(route('search', ['q' => 'dune']))
        ->post(route('library.store'), ['openlibrary_id' => 'OL893414W']);

    $response->assertRedirect(route('search', ['q' => 'dune']))
        ->assertInertiaFlash('toast.type', 'success')
        ->assertInertiaFlash('toast.message', 'Added “Dune” to your Library.');
    Http::assertSentCount(1);
    $book = Book::sole();
    expect($book->only(['openlibrary_id', 'title', 'first_publish_year', 'cover_id', 'page_count_median']))->toBe([
        'openlibrary_id' => 'OL893414W',
        'title' => 'Dune',
        'first_publish_year' => 1965,
        'cover_id' => 11481354,
        'page_count_median' => 608,
    ]);
    expect($book->authors->map->only(['openlibrary_id', 'name'])->all())
        ->toBe([['openlibrary_id' => 'OL79034A', 'name' => 'Frank Herbert']]);
    $entry = LibraryEntry::sole();
    expect($entry->user_id)->toBe($reader->id)
        ->and($entry->book_id)->toBe($book->id)
        ->and($entry->status)->toBe(ReadingStatus::WantToRead)
        ->and($entry->started_on)->toBeNull()
        ->and($entry->finished_on)->toBeNull();
});

test('adding a Book as Completed fills in its Started and Finished dates', function () {
    Http::preventStrayRequests();
    $book = Book::factory()->create();
    $reader = User::factory()->create();

    $this->actingAs($reader)->post(route('library.store'), [
        'openlibrary_id' => $book->openlibrary_id,
        'status' => ReadingStatus::Completed->value,
    ]);

    $entry = LibraryEntry::sole();
    expect($entry->status)->toBe(ReadingStatus::Completed)
        ->and($entry->started_on?->toDateString())->toBe('2026-11-05')
        ->and($entry->finished_on?->toDateString())->toBe('2026-11-05');
});

test('adding a Book already in the catalogue reuses it without calling OpenLibrary', function () {
    Http::preventStrayRequests();
    $author = Author::factory()->create();
    $book = Book::factory()->hasAttached($author)->create();
    LibraryEntry::factory()->for($book)->create();
    $reader = User::factory()->create();

    $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => $book->openlibrary_id]);

    Http::assertNothingSent();
    expect(Book::count())->toBe(1)
        ->and(Author::count())->toBe(1)
        ->and($reader->libraryEntries()->sole()->book_id)->toBe($book->id);
});

test('adding an uncached new Book looks up its work on OpenLibrary and counts toward the Reader limit', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => openLibraryResponse('work-dune')]);
    $reader = User::factory()->create();

    $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => 'OL893414W']);

    Http::assertSent(fn (Request $request) => $request->url() === 'https://openlibrary.org/search.json?q=key%3A%2Fworks%2FOL893414W'
        .'&fields=key%2Ctitle%2Cauthor_name%2Cauthor_key%2Cfirst_publish_year%2Ccover_i%2Cnumber_of_pages_median&limit=1');
    expect(Book::sole()->title)->toBe('Dune')
        ->and(LibraryEntry::count())->toBe(1)
        ->and(RateLimiter::attempts('openlibrary:'.$reader->id))->toBe(1);
});

test('adding a Book already in the Library is rejected', function () {
    Http::preventStrayRequests();
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->completed()->create();

    $response = $this->actingAs($reader)->post(route('library.store'), [
        'openlibrary_id' => $entry->book->openlibrary_id,
        'status' => ReadingStatus::Reading->value,
    ]);

    $response->assertInertiaFlash('toast.type', 'info')
        ->assertInertiaFlash('toast.message', "“{$entry->book->title}” is already in your Library.");
    expect(LibraryEntry::sole()->status)->toBe(ReadingStatus::Completed);
});

test('adding a new Book while OpenLibrary is down shows an error and creates nothing', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => Http::failedConnection()]);
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => 'OL893414W']);

    $response->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', 'Book search is unavailable right now. Please try again later.');
    expect(Book::count())->toBe(0)
        ->and(Author::count())->toBe(0)
        ->and(LibraryEntry::count())->toBe(0);
});

test('adding a new Book is rejected when the Reader limit is reached', function () {
    Http::preventStrayRequests();
    $reader = User::factory()->create();
    foreach (range(1, 20) as $attempt) {
        RateLimiter::hit('openlibrary:'.$reader->id, 60);
    }

    $response = $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => 'OL893414W']);

    $response->assertInertiaFlash('toast.type', 'error')
        ->assertInertiaFlash('toast.message', "You've reached the search limit. Please wait a minute and try again.");
    expect(LibraryEntry::count())->toBe(0);
});

test('adding a work OpenLibrary does not return is rejected', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => openLibraryResponse('work-dune')]);
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => 'OL1W']);

    $response->assertSessionHasErrors(['openlibrary_id' => 'This book could not be found on OpenLibrary.']);
    expect(Book::count())->toBe(0)
        ->and(LibraryEntry::count())->toBe(0);
});

test('adding a work with an over-long title and Author name stores them cut to their column lengths', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => openLibraryResponse('work-long-title')]);
    $reader = User::factory()->create();

    $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => 'OL45804W']);

    $book = Book::sole();
    expect(mb_strlen($book->title))->toBeGreaterThan(1000)->toBeLessThanOrEqual(1024)
        ->and($book->title)->toStartWith('The Complete Works, Comprising Letters')
        ->and(mb_strlen($book->authors->sole()->name))->toBeGreaterThan(500)->toBeLessThanOrEqual(512)
        ->and($book->authors->sole()->openlibrary_id)->toBe('OL4327048A')
        ->and(LibraryEntry::count())->toBe(1);
});

test('adding a work whose key is longer than its column is rejected', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => openLibraryResponse('work-long-key')]);
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->post(route('library.store'), ['openlibrary_id' => 'OL'.str_repeat('1', 300).'W']);

    $response->assertSessionHasErrors(['openlibrary_id' => 'This book could not be found on OpenLibrary.']);
    expect(Book::count())->toBe(0)
        ->and(LibraryEntry::count())->toBe(0);
});

test('adding validates the work key and status', function (array $payload, string $field, string $message) {
    Http::preventStrayRequests();
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->post(route('library.store'), $payload);

    $response->assertSessionHasErrors([$field => $message]);
    Http::assertNothingSent();
})->with([
    'missing work key' => [[], 'openlibrary_id', 'The book field is required.'],
    'edition key instead of a work key' => [['openlibrary_id' => 'OL123M'], 'openlibrary_id', 'The book field format is invalid.'],
    'unknown status' => [['openlibrary_id' => 'OL893414W', 'status' => 'abandoned'], 'status', 'The selected status is invalid.'],
]);
