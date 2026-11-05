<?php

use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertOk();
});

test('the dashboard shows the Reader currently Reading books and status counts', function () {
    $reader = User::factory()->create();
    $reading = LibraryEntry::factory()->for($reader)->reading()->for(Book::factory()->state(['title' => 'Dune']))->create();
    LibraryEntry::factory()->for($reader)->completed()->count(2)->create();
    LibraryEntry::factory()->for($reader)->count(3)->create();
    LibraryEntry::factory()->reading()->create();

    $response = $this->actingAs($reader)->get(route('dashboard'));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->has('reading', 1)
            ->where('reading.0.id', $reading->id)
            ->where('reading.0.book.title', 'Dune')
            ->where('statusCounts', ['want_to_read' => 3, 'reading' => 1, 'completed' => 2]),
        );
});

test('the dashboard counts Completed Books per month for the chosen year', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2025-01-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-01')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-12-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2024-06-15')->create();
    LibraryEntry::factory()->for($reader)->completed('2026-02-01')->create();
    LibraryEntry::factory()->completed('2025-03-15')->create();

    $response = $this->actingAs($reader)->get(route('dashboard', ['year' => 2025]));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('year', 2025)
        ->where('years', [2026, 2025, 2024])
        ->where('completedPerMonth', [1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 1]),
    );
});

test('the dashboard defaults to the current year and lists it even without Completed Books', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2024-06-15')->create();

    $response = $this->actingAs($reader)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('year', 2026)
        ->where('years', [2026, 2024])
        ->where('completedPerMonth', array_fill(0, 12, 0)),
    );
});

test('the dashboard falls back to the current year when year is empty', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('dashboard', ['year' => '']));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('year', 2026));
});

test('the dashboard ranks the top 10 Authors by Completed Books, counting a co-written Book for each Author', function () {
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

    $response = $this->actingAs($reader)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->has('topAuthors', 10)
        ->where('topAuthors.0', ['id' => $herbert->id, 'name' => 'Frank Herbert', 'completed_books' => 4])
        ->where('topAuthors.1.completed_books', 1)
        ->where('topAuthors', fn ($authors) => collect($authors)->pluck('name')->doesntContain('Ursula K. Le Guin')),
    );
});

test('the dashboard shows empty stats for a new Reader', function () {
    $reader = User::factory()->create();
    LibraryEntry::factory()->completed()->create();

    $response = $this->actingAs($reader)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('reading', [])
        ->where('statusCounts', ['want_to_read' => 0, 'reading' => 0, 'completed' => 0])
        ->where('completedPerMonth', array_fill(0, 12, 0))
        ->where('topAuthors', []),
    );
});

test('the dashboard rejects a year outside the supported range', function (mixed $year) {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('dashboard', ['year' => $year]));

    $response->assertSessionHasErrors('year');
})->with([
    'before 1900' => [1899],
    'in the future' => [2027],
    'not a number' => ['last'],
]);
