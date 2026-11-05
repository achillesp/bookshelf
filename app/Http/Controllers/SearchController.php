<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
use App\Models\User;
use App\Services\OpenLibrary;
use App\Services\OpenLibraryException;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    /**
     * Search OpenLibrary for Books to add to the Reader's Library.
     */
    public function __invoke(SearchRequest $request, OpenLibrary $openLibrary): Response
    {
        $query = trim((string) $request->validated('q'));

        if ($query === '') {
            return $this->render($query, [], $request->user());
        }

        try {
            $results = $openLibrary->search($query, $request->user());
        } catch (OpenLibraryException $exception) {
            return $this->render($query, [], $request->user(), $exception->getMessage())
                ->flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return $this->render($query, $results, $request->user());
    }

    /**
     * @param  list<array{openlibrary_id: string}>  $results
     */
    private function render(string $query, array $results, User $reader, ?string $error = null): Response
    {
        $inLibrary = $results === [] ? [] : $reader->libraryEntries()
            ->join('books', 'books.id', '=', 'library_entries.book_id')
            ->whereIn('books.openlibrary_id', array_column($results, 'openlibrary_id'))
            ->pluck('books.openlibrary_id')
            ->all();

        return Inertia::render('search/Index', [
            'query' => $query,
            'results' => $results,
            'inLibrary' => $inLibrary,
            'error' => $error,
        ]);
    }
}
