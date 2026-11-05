<?php

namespace App\Http\Controllers;

use App\Actions\Library\AddBookToLibrary;
use App\Http\Requests\Library\StoreLibraryEntryRequest;
use App\Services\OpenLibraryException;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;

class LibraryEntryController extends Controller
{
    /**
     * Add a Book to the Reader's Library.
     */
    public function store(StoreLibraryEntryRequest $request, AddBookToLibrary $addBookToLibrary): RedirectResponse
    {
        try {
            $entry = $addBookToLibrary->handle(
                $request->user(),
                $request->string('openlibrary_id')->toString(),
                $request->status(),
            );
        } catch (OpenLibraryException $exception) {
            return Inertia::flash('toast', ['type' => 'error', 'message' => $exception->getMessage()])->back();
        }

        $title = $entry->book->title;

        return Inertia::flash('toast', $entry->wasRecentlyCreated
            ? ['type' => 'success', 'message' => __('Added “:title” to your Library.', ['title' => $title])]
            : ['type' => 'info', 'message' => __('“:title” is already in your Library.', ['title' => $title])],
        )->back();
    }
}
