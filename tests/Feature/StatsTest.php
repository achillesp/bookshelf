<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get('/stats');

    $response->assertRedirect(route('login'));
});

test('stats count the Reader Library Entries in each Reading Status', function () {
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->reading()->create();
    LibraryEntry::factory()->for($reader)->completed()->count(2)->create();
    LibraryEntry::factory()->for($reader)->count(3)->create();
    LibraryEntry::factory()->reading()->create();

    $response = $this->actingAs($reader)->get(route('stats'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('stats/Index')
            ->where('statusCounts', ['want_to_read' => 3, 'reading' => 1, 'completed' => 2]),
        );
});

test('stats count Completed Books per month for the chosen year', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2025-01-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-01')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-12-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2024-06-15')->create();
    LibraryEntry::factory()->for($reader)->completed('2026-02-01')->create();
    LibraryEntry::factory()->completed('2025-03-15')->create();

    $response = $this->actingAs($reader)->get(route('stats', ['year' => 2025]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('year', 2025)
        ->where('years', [2026, 2025, 2024])
        ->where('completedPerMonth', [1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 1]),
    );
});

test('stats add up the approximate pages of Completed Books per month for the chosen year', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2025-01-31')->for(Book::factory()->state(['page_count_median' => 412]))->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-01')->for(Book::factory()->state(['page_count_median' => 300]))->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-31')->for(Book::factory()->state(['page_count_median' => 180]))->create();
    LibraryEntry::factory()->for($reader)->completed('2025-12-31')->for(Book::factory()->state(['page_count_median' => null]))->create();
    LibraryEntry::factory()->for($reader)->completed('2024-06-15')->for(Book::factory()->state(['page_count_median' => 500]))->create();
    LibraryEntry::factory()->for($reader)->reading()->for(Book::factory()->state(['page_count_median' => 250]))->create();
    LibraryEntry::factory()->completed('2025-03-15')->for(Book::factory()->state(['page_count_median' => 999]))->create();

    $response = $this->actingAs($reader)->get(route('stats', ['year' => 2025]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('completedPerMonth', [1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 1])
        ->where('pagesPerMonth', [412, 0, 480, 0, 0, 0, 0, 0, 0, 0, 0, 0]),
    );
});

test('changing the year reloads and computes only the chart props for that year', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-01')->for(Book::factory()->state(['page_count_median' => 300]))->create();
    LibraryEntry::factory()->for($reader)->completed('2026-02-01')->create();
    $response = $this->actingAs($reader)->get(route('stats', ['year' => 2025]));
    $libraryEntryQueries = 0;
    DB::listen(function (QueryExecuted $query) use (&$libraryEntryQueries) {
        if (str_contains($query->sql, 'library_entries')) {
            $libraryEntryQueries++;
        }
    });

    $response->assertInertia(fn (Assert $page) => $page
        ->reloadOnly(['year', 'completedPerMonth', 'pagesPerMonth'], fn (Assert $reload) => $reload
            ->where('year', 2025)
            ->where('completedPerMonth', [0, 0, 1, 0, 0, 0, 0, 0, 0, 0, 0, 0])
            ->where('pagesPerMonth', [0, 0, 300, 0, 0, 0, 0, 0, 0, 0, 0, 0])
            ->missing('statusCounts')
            ->missing('years')
            ->missing('topAuthors'),
        ),
    );
    expect($libraryEntryQueries)->toBe(1);
});

test('stats default to the current year and list it even without Completed Books', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2024-06-15')->create();

    $response = $this->actingAs($reader)->get(route('stats'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('year', 2026)
        ->where('years', [2026, 2024])
        ->where('completedPerMonth', array_fill(0, 12, 0)),
    );
});

test('stats fall back to the current year when year is empty', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('stats', ['year' => '']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('year', 2026));
});

test('stats reject a year outside the supported range', function (mixed $year) {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('stats', ['year' => $year]));

    $response->assertSessionHasErrors('year');
})->with([
    'before 1900' => [1899],
    'in the future' => [2027],
    'not a number' => ['last'],
]);

test('stats rank the top 10 Authors by total Books with counts per Reading Status and average Rating', function () {
    $reader = User::factory()->create();
    $herbert = Author::factory()->create(['name' => 'Frank Herbert']);
    $anderson = Author::factory()->create(['name' => 'Kevin J. Anderson']);
    $leGuin = Author::factory()->create(['name' => 'Ursula K. Le Guin']);
    $banks = Author::factory()->create(['name' => 'Iain M. Banks']);
    $asimov = Author::factory()->create(['name' => 'Isaac Asimov']);
    foreach ([5, 4, null] as $rating) {
        LibraryEntry::factory()->for($reader)->completed()->state(['rating' => $rating])
            ->for(Book::factory()->hasAttached($herbert))->create();
    }
    LibraryEntry::factory()->for($reader)->completed()->state(['rating' => 4])
        ->for(Book::factory()->hasAttached([$herbert, $anderson]))->create();
    LibraryEntry::factory()->for($reader)->for(Book::factory()->hasAttached($herbert))->create();
    foreach (range(1, 2) as $book) {
        LibraryEntry::factory()->for($reader)->reading()->for(Book::factory()->hasAttached($leGuin))->create();
        LibraryEntry::factory()->for($reader)->completed()->for(Book::factory()->hasAttached($asimov))->create();
    }
    LibraryEntry::factory()->for($reader)->for(Book::factory()->hasAttached($leGuin))->create();
    LibraryEntry::factory()->completed()->state(['rating' => 1])->for(Book::factory()->hasAttached($leGuin))->count(5)->create();
    LibraryEntry::factory()->for($reader)->completed()->for(Book::factory()->hasAttached($banks))->create();
    LibraryEntry::factory()->for($reader)->reading()->for(Book::factory()->hasAttached($banks))->create();
    foreach (Author::factory()->count(10)->create() as $author) {
        LibraryEntry::factory()->for($reader)->for(Book::factory()->hasAttached($author))->create();
    }

    $response = $this->actingAs($reader)->get(route('stats'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('topAuthors', 10)
        ->where('topAuthors.0', [
            'id' => $herbert->id,
            'name' => 'Frank Herbert',
            'total_books' => 5,
            'want_to_read' => 1,
            'reading' => 0,
            'completed' => 4,
            'average_rating' => 4.3,
        ])
        ->where('topAuthors.1', [
            'id' => $leGuin->id,
            'name' => 'Ursula K. Le Guin',
            'total_books' => 3,
            'want_to_read' => 1,
            'reading' => 2,
            'completed' => 0,
            'average_rating' => null,
        ])
        ->where('topAuthors.2.name', 'Isaac Asimov')
        ->where('topAuthors.3.name', 'Iain M. Banks')
        ->where('topAuthors.4', [
            'id' => $anderson->id,
            'name' => 'Kevin J. Anderson',
            'total_books' => 1,
            'want_to_read' => 0,
            'reading' => 0,
            'completed' => 1,
            'average_rating' => 4,
        ]),
    );
});
