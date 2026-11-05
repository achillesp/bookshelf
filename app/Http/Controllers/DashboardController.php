<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Http\Requests\DashboardRequest;
use App\Services\ReadingStats;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the Reader's currently Reading books and reading statistics.
     */
    public function __invoke(DashboardRequest $request, ReadingStats $stats): Response
    {
        $reader = $request->user();
        $year = $request->year();

        return Inertia::render('Dashboard', [
            'reading' => $reader->libraryEntries()
                ->where('status', ReadingStatus::Reading)
                ->with('book.authors')
                ->orderByDesc('started_on')
                ->orderByDesc('id')
                ->get(),
            'statusCounts' => $stats->statusCounts($reader),
            'year' => $year,
            ...$stats->completedByYear($reader, $year),
            'topAuthors' => $stats->topAuthors($reader),
        ]);
    }
}
