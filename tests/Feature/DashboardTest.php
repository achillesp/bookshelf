<?php

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

test('the dashboard summarises Completed Books per month of the current year', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();
    LibraryEntry::factory()->for($reader)->completed('2026-01-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2026-03-01')->create();
    LibraryEntry::factory()->for($reader)->completed('2026-03-31')->create();
    LibraryEntry::factory()->for($reader)->completed('2025-03-15')->create();
    LibraryEntry::factory()->completed('2026-03-15')->create();

    $response = $this->actingAs($reader)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('year', 2026)
        ->where('completedPerMonth', [1, 0, 2, 0, 0, 0, 0, 0, 0, 0, 0, 0])
        ->missing('years')
        ->missing('topAuthors'),
    );
});

test('old dashboard links with a year still load and show the current year', function () {
    $this->travelTo('2026-11-05 10:00:00');
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->get(route('dashboard', ['year' => 2020]));

    $response->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('year', 2026));
});

test('the dashboard shows empty stats for a new Reader', function () {
    $reader = User::factory()->create();
    LibraryEntry::factory()->completed()->create();

    $response = $this->actingAs($reader)->get(route('dashboard'));

    $response->assertInertia(fn (Assert $page) => $page
        ->where('reading', [])
        ->where('statusCounts', ['want_to_read' => 0, 'reading' => 0, 'completed' => 0])
        ->where('completedPerMonth', array_fill(0, 12, 0)),
    );
});
