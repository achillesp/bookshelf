<?php

namespace App\Http\Controllers;

use App\Actions\Library\AddBookToLibrary;
use App\Enums\ReadingStatus;
use App\Http\Requests\Library\LibraryIndexRequest;
use App\Http\Requests\Library\StoreLibraryEntryRequest;
use App\Http\Requests\Library\UpdateLibraryEntryRequest;
use App\Models\LibraryEntry;
use App\Services\OpenLibraryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LibraryEntryController extends Controller
{
    private const int PER_PAGE = 20;

    /**
     * Show the Reader's Library, filtered by Reading Status and sorted.
     */
    public function index(LibraryIndexRequest $request): Response
    {
        $sort = LibraryIndexRequest::SORTS[$request->sort()];

        $entries = $request->user()->libraryEntries()
            ->select('library_entries.*')
            ->join('books', 'books.id', '=', 'library_entries.book_id')
            ->with('book.authors')
            ->when($request->status(), fn ($query, ReadingStatus $status) => $query->where('status', $status))
            ->orderBy($sort['column'], $sort['direction'])
            ->orderByDesc('library_entries.id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        return Inertia::render('library/Index', [
            'entries' => $entries,
            'filters' => [
                'status' => $request->status()?->value,
                'sort' => $request->sort(),
            ],
        ]);
    }

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

    /**
     * Update a Library Entry's Reading Status or Rating.
     */
    public function update(UpdateLibraryEntryRequest $request, int $entry): RedirectResponse
    {
        $libraryEntry = $this->findEntry($request, $entry);

        if ($request->has('status')) {
            $libraryEntry->changeStatus($request->enum('status', ReadingStatus::class));
        }

        $libraryEntry->fill($request->safe()->only(['rating']))->save();

        return back();
    }

    /**
     * Remove a Library Entry; the shared Book stays in the catalogue.
     */
    public function destroy(Request $request, int $entry): RedirectResponse
    {
        $libraryEntry = $this->findEntry($request, $entry);
        $libraryEntry->delete();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Removed “:title” from your Library.', ['title' => $libraryEntry->book->title]),
        ]);

        return to_route('library.index');
    }

    /**
     * Find one of the Reader's own entries; another Reader's entry is a 404.
     */
    private function findEntry(Request $request, int $id): LibraryEntry
    {
        return $request->user()->libraryEntries()->with('book')->findOrFail($id);
    }
}
