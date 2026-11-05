<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
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

test('stats rank the top 10 Authors by Completed Books, counting a co-written Book for each Author', function () {
    $reader = User::factory()->create();
    $herbert = Author::factory()->create(['name' => 'Frank Herbert']);
    $anderson = Author::factory()->create(['name' => 'Kevin J. Anderson']);
    $leGuin = Author::factory()->create(['name' => 'Ursula K. Le Guin']);
    foreach (range(1, 3) as $book) {
        LibraryEntry::factory()->for($reader)->completed()->for(Book::factory()->hasAttached($herbert))->create();
    }
    LibraryEntry::factory()->for($reader)->completed()
        ->for(Book::factory()->hasAttached([$herbert, $anderson]))->create();
    LibraryEntry::factory()->for($reader)->reading()->for(Book::factory()->hasAttached($leGuin))->create();
    LibraryEntry::factory()->completed()->for(Book::factory()->hasAttached($leGuin))->count(5)->create();
    $others = Author::factory()->count(10)->create();
    foreach ($others as $author) {
        LibraryEntry::factory()->for($reader)->completed()->for(Book::factory()->hasAttached($author))->create();
    }

    $response = $this->actingAs($reader)->get(route('stats'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('topAuthors', 10)
        ->where('topAuthors.0', ['id' => $herbert->id, 'name' => 'Frank Herbert', 'completed_books' => 4])
        ->where('topAuthors.1.completed_books', 1)
        ->where('topAuthors', fn ($authors) => collect($authors)->pluck('name')->doesntContain('Ursula K. Le Guin')),
    );
});
