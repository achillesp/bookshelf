<?php

namespace App\Services;

use App\Enums\ReadingStatus;
use App\Models\User;
use Carbon\CarbonImmutable;

/**
 * Reading statistics for one Reader, built from their own Library Entries.
 */
class ReadingStats
{
    private const int TOP_AUTHORS = 10;

    /**
     * Number of Library Entries in each Reading Status.
     *
     * @return array<string, int>
     */
    public function statusCounts(User $reader): array
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
     * Years with Completed Books, plus the current year, newest first.
     *
     * @return list<int>
     */
    public function completedYears(User $reader): array
    {
        return array_values($reader->libraryEntries()
            ->where('status', ReadingStatus::Completed)
            ->whereNotNull('finished_on')
            ->pluck('finished_on')
            ->map(fn (mixed $date): int => CarbonImmutable::parse($date)->year)
            ->push(now()->year)
            ->unique()
            ->sortDesc()
            ->all());
    }

    /**
     * Completed Books and their approximate pages per month of the chosen year, January first.
     * A Book without a page count adds no pages.
     *
     * @return array{completedPerMonth: list<int>, pagesPerMonth: list<int>}
     */
    public function monthlyTotals(User $reader, int $year): array
    {
        $completedEntries = $reader->libraryEntries()
            ->toBase()
            ->join('books', 'books.id', '=', 'library_entries.book_id')
            ->where('library_entries.status', ReadingStatus::Completed->value)
            ->where('library_entries.finished_on', '>=', CarbonImmutable::create($year)->toDateString())
            ->where('library_entries.finished_on', '<', CarbonImmutable::create($year + 1)->toDateString())
            ->get(['library_entries.finished_on', 'books.page_count_median']);

        $completedPerMonth = array_fill(0, 12, 0);
        $pagesPerMonth = array_fill(0, 12, 0);

        foreach ($completedEntries as $entry) {
            $month = CarbonImmutable::parse($entry->finished_on)->month - 1;
            $completedPerMonth[$month]++;
            $pagesPerMonth[$month] += (int) $entry->page_count_median;
        }

        return ['completedPerMonth' => $completedPerMonth, 'pagesPerMonth' => $pagesPerMonth];
    }

    /**
     * Authors with the most Completed Books; a co-written Book counts for each of its Authors.
     *
     * @return list<array{id: int, name: string, completed_books: int}>
     */
    public function topAuthors(User $reader): array
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
