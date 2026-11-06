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
     * Authors with the most Books in the Library (any Reading Status), ties broken by Completed Books then name.
     * A co-written Book counts for each of its Authors. The average Rating skips unrated Books and is null when none are rated.
     *
     * @return list<array{id: int, name: string, total_books: int, want_to_read: int, reading: int, completed: int, average_rating: float|null}>
     */
    public function topAuthors(User $reader): array
    {
        $query = $reader->libraryEntries()
            ->toBase()
            ->join('author_book', 'author_book.book_id', '=', 'library_entries.book_id')
            ->join('authors', 'authors.id', '=', 'author_book.author_id')
            ->groupBy('authors.id', 'authors.name')
            ->select('authors.id', 'authors.name')
            ->selectRaw('count(*) as total_books')
            ->selectRaw('avg(library_entries.rating) as average_rating');

        foreach (ReadingStatus::cases() as $status) {
            $query->selectRaw(
                "sum(case when library_entries.status = ? then 1 else 0 end) as {$status->value}",
                [$status->value],
            );
        }

        return array_values($query
            ->orderByDesc('total_books')
            ->orderByDesc(ReadingStatus::Completed->value)
            ->orderBy('authors.name')
            ->limit(self::TOP_AUTHORS)
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'total_books' => (int) $row->total_books,
                'want_to_read' => (int) $row->{ReadingStatus::WantToRead->value},
                'reading' => (int) $row->{ReadingStatus::Reading->value},
                'completed' => (int) $row->{ReadingStatus::Completed->value},
                'average_rating' => $row->average_rating === null ? null : round((float) $row->average_rating, 1),
            ])
            ->all());
    }
}
