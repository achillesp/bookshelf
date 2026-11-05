<?php

namespace App\Http\Controllers;

use App\Enums\ReadingStatus;
use App\Http\Requests\DashboardRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const int TOP_AUTHORS = 10;

    /**
     * Show the Reader's currently Reading books and reading statistics.
     */
    public function __invoke(DashboardRequest $request): Response
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
            'statusCounts' => $this->statusCounts($reader),
            'year' => $year,
            ...$this->completedByYear($reader, $year),
            'topAuthors' => $this->topAuthors($reader),
        ]);
    }

    /**
     * Number of Library Entries in each Reading Status.
     *
     * @return array<string, int>
     */
    private function statusCounts(User $reader): array
    {
        $counts = $reader->libraryEntries()
            ->toBase()
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return collect(ReadingStatus::cases())
            ->mapWithKeys(fn (ReadingStatus $status): array => [$status->value => (int) ($counts[$status->value] ?? 0)])
            ->all();
    }

    /**
     * Years with Completed Books (plus the current year), and Completed Books per month of the chosen year.
     *
     * @return array{years: list<int>, completedPerMonth: list<int>}
     */
    private function completedByYear(User $reader, int $year): array
    {
        $finishedDates = $reader->libraryEntries()
            ->where('status', ReadingStatus::Completed)
            ->whereNotNull('finished_on')
            ->pluck('finished_on')
            ->map(fn (mixed $date): CarbonImmutable => CarbonImmutable::parse($date));

        $years = array_values($finishedDates
            ->map(fn (CarbonImmutable $date): int => $date->year)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->all());

        $perMonth = array_fill(0, 12, 0);

        foreach ($finishedDates as $date) {
            if ($date->year === $year) {
                $perMonth[$date->month - 1]++;
            }
        }

        return ['years' => $years, 'completedPerMonth' => array_values($perMonth)];
    }

    /**
     * Authors with the most Completed Books; a co-written Book counts for each of its Authors.
     *
     * @return list<array{id: int, name: string, completed_books: int}>
     */
    private function topAuthors(User $reader): array
    {
        return array_values($reader->libraryEntries()
            ->toBase()
            ->join('author_book', 'author_book.book_id', '=', 'library_entries.book_id')
            ->join('authors', 'authors.id', '=', 'author_book.author_id')
            ->where('library_entries.status', ReadingStatus::Completed->value)
            ->groupBy('authors.id', 'authors.name')
            ->select('authors.id', 'authors.name')
            ->selectRaw('count(*) as completed_books')
            ->orderByDesc('completed_books')
            ->orderBy('authors.name')
            ->limit(self::TOP_AUTHORS)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'completed_books' => (int) $row->completed_books,
            ])
            ->all());
    }
}
