<?php

namespace App\Http\Controllers;

use App\Http\Requests\SearchRequest;
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
            return Inertia::render('search/Index', ['query' => '', 'results' => [], 'error' => null]);
        }

        try {
            $results = $openLibrary->search($query, $request->user());
        } catch (OpenLibraryException $exception) {
            return Inertia::render('search/Index', [
                'query' => $query,
                'results' => [],
                'error' => $exception->getMessage(),
            ])->flash('toast', ['type' => 'error', 'message' => $exception->getMessage()]);
        }

        return Inertia::render('search/Index', [
            'query' => $query,
            'results' => $results,
            'error' => null,
        ]);
    }
}
