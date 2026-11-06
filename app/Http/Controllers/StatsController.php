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
     *
     * Props are closures so that a year change, which reloads only the chart props, computes only those.
     */
    public function __invoke(StatsRequest $request, ReadingStats $stats): Response
    {
        $reader = $request->user();
        $year = $request->year();
        $totals = null;
        $monthlyTotals = function () use (&$totals, $stats, $reader, $year): array {
            return $totals ??= $stats->monthlyTotals($reader, $year);
        };

        return Inertia::render('stats/Index', [
            'statusCounts' => fn (): array => $stats->statusCounts($reader),
            'year' => $year,
            'years' => fn (): array => $stats->completedYears($reader),
            'completedPerMonth' => fn (): array => $monthlyTotals()['completedPerMonth'],
            'pagesPerMonth' => fn (): array => $monthlyTotals()['pagesPerMonth'],
            'topAuthors' => fn (): array => $stats->topAuthors($reader),
        ]);
    }
}
