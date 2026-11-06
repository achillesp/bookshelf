<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Services\ReadingStats;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Show the Reader's currently Reading books and a summary of this year's reading.
     */
    public function __invoke(Request $request, ReadingStats $stats): Response
    {
        $reader = $request->user();
        $year = now()->year;

        return Inertia::render('Dashboard', [
            'reading' => $reader->libraryEntries()
                ->where('status', ReadingStatus::Reading)
                ->with('book.authors')
                ->orderByDesc('started_on')
                ->orderByDesc('id')
                ->get(),
            'statusCounts' => $stats->statusCounts($reader),
            'year' => $year,
            'completedPerMonth' => $stats->monthlyTotals($reader, $year)['completedPerMonth'],
        ]);
    }
}
