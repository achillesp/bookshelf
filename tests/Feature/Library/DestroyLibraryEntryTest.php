<?php

use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;

test('removing an entry deletes it but keeps the shared Book', function () {
    $reader = User::factory()->create();
    $entry = LibraryEntry::factory()->for($reader)->for(Book::factory()->state(['title' => 'Dune']))->create();

    $response = $this->actingAs($reader)->delete(route('library.destroy', $entry));

    $response->assertRedirect(route('library.index'))
        ->assertInertiaFlash('toast.message', '“Dune” has left your Library.');
    $this->assertModelMissing($entry);
    $this->assertModelExists($entry->book);
});

test('another Reader entry cannot be removed', function () {
    $entry = LibraryEntry::factory()->create();
    $reader = User::factory()->create();

    $response = $this->actingAs($reader)->delete(route('library.destroy', $entry));

    $response->assertNotFound();
    $this->assertModelExists($entry);
});
