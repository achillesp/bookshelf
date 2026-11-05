<?php

use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;

function fakeOpenLibrarySearch(): void
{
    Http::preventStrayRequests();
    Http::fake([
        'openlibrary.org/search.json*' => openLibraryResponse('search-dune'),
    ]);
}

beforeEach(function () {
    config(['services.openlibrary.contact' => 'ops@example.test']);
});

test('guests are redirected to the login page', function () {
    $response = $this->get(route('search', ['q' => 'dune']));

    $response->assertRedirect(route('login'));
});

test('search page renders without calling OpenLibrary when no query is given', function () {
    Http::preventStrayRequests();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('search/Index')
            ->where('query', '')
            ->where('results', [])
            ->where('error', null),
        );
    Http::assertNothingSent();
});

test('search shows mapped OpenLibrary results', function () {
    fakeOpenLibrarySearch();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search', ['q' => 'dune']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('search/Index')
            ->where('query', 'dune')
            ->has('results', 2)
            ->where('results.0', [
                'openlibrary_id' => 'OL893414W',
                'title' => 'Dune',
                'authors' => [['openlibrary_id' => 'OL79034A', 'name' => 'Frank Herbert']],
                'first_publish_year' => 1965,
                'cover_id' => 11481354,
                'page_count_median' => 608,
            ])
            ->where('results.1', [
                'openlibrary_id' => 'OL27448W',
                'title' => 'Dune Notes',
                'authors' => [],
                'first_publish_year' => null,
                'cover_id' => null,
                'page_count_median' => null,
            ])
            ->where('error', null),
        );
    Http::assertSent(fn (Request $request) => $request->url() === 'https://openlibrary.org/search.json?q=dune'
            .'&fields=key%2Ctitle%2Cauthor_name%2Cauthor_key%2Cfirst_publish_year%2Ccover_i%2Cnumber_of_pages_median&limit=20'
        && $request->header('User-Agent') === ['BookShelf (ops@example.test)']);
});

test('repeating a search uses the cache and does not count toward the rate limit', function () {
    fakeOpenLibrarySearch();
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('search', ['q' => 'dune']));

    $response = $this->actingAs($user)->get(route('search', ['q' => '  DUNE ']));

    $response->assertInertia(fn (Assert $page) => $page->has('results', 2));
    Http::assertSentCount(1);
    expect(RateLimiter::attempts('openlibrary:'.$user->id))->toBe(1);
});

test('search shows an unavailable message and caches nothing when OpenLibrary cannot be reached', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => Http::failedConnection()]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search', ['q' => 'dune']));
    $this->actingAs($user)->get(route('search', ['q' => 'dune']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('query', 'dune')
            ->where('results', [])
            ->where('error', 'Book search is unavailable right now. Please try again later.')
            ->hasFlash('toast.type', 'error')
            ->hasFlash('toast.message', 'Book search is unavailable right now. Please try again later.'),
        );
    Http::assertSentCount(2);
});

test('search shows an unavailable message when OpenLibrary returns a server error', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => Http::response('', 503)]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search', ['q' => 'dune']));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('results', [])
        ->where('error', 'Book search is unavailable right now. Please try again later.'),
    );
});

test('the 21st uncached search in a minute is rejected without calling OpenLibrary', function () {
    fakeOpenLibrarySearch();
    $user = User::factory()->create();
    $this->freezeTime();
    foreach (range(1, 20) as $number) {
        $this->actingAs($user)->get(route('search', ['q' => "dune {$number}"]));
        $this->travel(1)->seconds();
    }

    $response = $this->actingAs($user)->get(route('search', ['q' => 'dune 21']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('query', 'dune 21')
            ->where('results', [])
            ->where('error', "You've reached the search limit. Please wait a minute and try again.")
            ->hasFlash('toast.type', 'error')
            ->hasFlash('toast.message', "You've reached the search limit. Please wait a minute and try again."),
        );
    Http::assertSentCount(20);
});

test('the search limit is counted per Reader', function () {
    fakeOpenLibrarySearch();
    $reader = User::factory()->create();
    $otherReader = User::factory()->create();
    $this->freezeTime();
    foreach (range(1, 20) as $number) {
        $this->actingAs($otherReader)->get(route('search', ['q' => "dune {$number}"]));
        $this->travel(1)->seconds();
    }

    $response = $this->actingAs($reader)->get(route('search', ['q' => 'dune 21']));

    $response->assertInertia(fn (Assert $page) => $page->has('results', 2)->where('error', null));
});

test('search rejects a query longer than 200 characters', function () {
    Http::preventStrayRequests();
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search', ['q' => str_repeat('a', 201)]));

    $response->assertSessionHasErrors(['q' => 'The search field must not be greater than 200 characters.']);
    Http::assertNothingSent();
});

test('search marks results already in the Reader Library', function () {
    fakeOpenLibrarySearch();
    $reader = User::factory()->create();
    $dune = Book::factory()->create(['openlibrary_id' => 'OL893414W']);
    $duneNotes = Book::factory()->create(['openlibrary_id' => 'OL27448W']);
    LibraryEntry::factory()->for($reader)->for($dune)->create();
    LibraryEntry::factory()->for($duneNotes)->create();

    $response = $this->actingAs($reader)->get(route('search', ['q' => 'dune']));

    $response->assertInertia(fn (Assert $page) => $page->where('inLibrary', ['OL893414W']));
});

test('a 4th uncached OpenLibrary call within one second is rejected as busy without costing the Reader', function () {
    fakeOpenLibrarySearch();
    $this->freezeTime();
    foreach (range(1, 3) as $number) {
        $this->actingAs(User::factory()->create())->get(route('search', ['q' => "dune {$number}"]));
    }
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('search', ['q' => 'dune 4']));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('results', [])
        ->where('error', 'Book search is busy right now. Please try again in a moment.')
        ->hasFlash('toast.type', 'error')
        ->hasFlash('toast.message', 'Book search is busy right now. Please try again in a moment.'),
    );
    Http::assertSentCount(3);
    expect(RateLimiter::attempts('openlibrary:'.$reader->id))->toBe(0);
});

test('OpenLibrary calls are allowed again once the busy second has passed', function () {
    fakeOpenLibrarySearch();
    $this->freezeTime();
    $reader = User::factory()->create();
    foreach (range(1, 3) as $number) {
        $this->actingAs($reader)->get(route('search', ['q' => "dune {$number}"]));
    }
    $this->travel(1)->seconds();

    $response = $this->actingAs($reader)->get(route('search', ['q' => 'dune 4']));

    $response->assertInertia(fn (Assert $page) => $page->has('results', 2)->where('error', null));
    Http::assertSentCount(4);
});

test('search skips a result whose work key is longer than its column', function () {
    Http::preventStrayRequests();
    Http::fake(['openlibrary.org/search.json*' => openLibraryResponse('work-long-key')]);
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('search', ['q' => 'long key']));

    $response->assertInertia(fn (Assert $page) => $page->where('results', [])->where('error', null));
});
