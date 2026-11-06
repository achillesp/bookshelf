<?php

namespace App\Http\Controllers;

use App\Http\Requests\StatsRequest;
use App\Services\ReadingStats;
use Inertia\Inertia;
use Inertia\Response;

class StatsController extends Controller
{
    /**
     * Show the Reader's full reading statistics.
     */
    public function __invoke(StatsRequest $request, ReadingStats $stats): Response
    {
        $reader = $request->user();
        $year = $request->year();

        return Inertia::render('stats/Index', [
            'statusCounts' => $stats->statusCounts($reader),
            'year' => $year,
            'years' => $stats->completedYears($reader),
            ...$stats->monthlyTotals($reader, $year),
            'topAuthors' => $stats->topAuthors($reader),
        ]);
    }
}
