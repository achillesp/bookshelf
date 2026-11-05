<?php

namespace App\Actions\Library;

use App\Enums\ReadingStatus;
use App\Models\Author;
use App\Models\Book;
use App\Models\LibraryEntry;
use App\Models\User;
use App\Services\OpenLibrary;
use App\Services\OpenLibraryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Adds an OpenLibrary work to a Reader's Library, importing the Book and its Authors on first use.
 */
class AddBookToLibrary
{
    public function __construct(private OpenLibrary $openLibrary) {}

    /**
     * Returns the Reader's entry for the Book; `wasRecentlyCreated` is false when it was already in the Library.
     *
     * @throws OpenLibraryException
     * @throws ValidationException
     */
    public function handle(User $reader, string $openLibraryId, ReadingStatus $status): LibraryEntry
    {
        $book = Book::firstWhere('openlibrary_id', $openLibraryId);

        if ($book !== null) {
            return $this->addEntry($reader, $book, $status);
        }

        $work = $this->openLibrary->findWork($openLibraryId, $reader)
            ?? throw ValidationException::withMessages([
                'openlibrary_id' => __('This book could not be found on OpenLibrary.'),
            ]);

        return DB::transaction(fn (): LibraryEntry => $this->addEntry($reader, $this->importBook($work), $status));
    }

    /**
     * Create the Reader's entry with the dates of its starting status, or return the one a parallel request created.
     */
    private function addEntry(User $reader, Book $book, ReadingStatus $status): LibraryEntry
    {
        $draft = new LibraryEntry;
        $draft->changeStatus($status);

        $entry = $reader->libraryEntries()->createOrFirst(
            ['book_id' => $book->id],
            $draft->only(['status', 'started_on', 'finished_on']),
        );

        return $entry->setRelation('book', $book);
    }

    /**
     * Create the shared Book, attaching its Authors only when this call created it.
     *
     * @param  array{openlibrary_id: string, title: string, authors: list<array{openlibrary_id: string, name: string}>, first_publish_year: int|null, cover_id: int|null, page_count_median: int|null}  $work
     */
    private function importBook(array $work): Book
    {
        $book = Book::createOrFirst(
            ['openlibrary_id' => $work['openlibrary_id']],
            [
                'title' => $work['title'],
                'first_publish_year' => $work['first_publish_year'],
                'cover_id' => $work['cover_id'],
                'page_count_median' => $work['page_count_median'],
            ],
        );

        if ($book->wasRecentlyCreated) {
            $authorIds = collect($work['authors'])
                ->unique('openlibrary_id')
                ->map(fn (array $author): int => Author::createOrFirst(
                    ['openlibrary_id' => $author['openlibrary_id']],
                    ['name' => $author['name']],
                )->id);

            $book->authors()->attach($authorIds->all());
        }

        return $book;
    }
}
