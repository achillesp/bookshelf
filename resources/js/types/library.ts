export type Author = {
    openlibrary_id: string;
    name: string;
};

/**
 * An OpenLibrary work as returned by search, before it is added to a Library.
 */
export type SearchResult = {
    openlibrary_id: string;
    title: string;
    authors: Author[];
    first_publish_year: number | null;
    cover_id: number | null;
    page_count_median: number | null;
};

export const READING_STATUS = {
    WANT_TO_READ: 'want_to_read',
    READING: 'reading',
    COMPLETED: 'completed',
} as const;

export type ReadingStatus =
    (typeof READING_STATUS)[keyof typeof READING_STATUS];

export const READING_STATUS_LABELS: Record<ReadingStatus, string> = {
    want_to_read: 'Want to Read',
    reading: 'Reading',
    completed: 'Completed',
};

export type Book = {
    id: number;
    openlibrary_id: string;
    title: string;
    first_publish_year: number | null;
    cover_id: number | null;
    page_count_median: number | null;
    authors: Author[];
};

/**
 * A Reader's record of one Book; dates are `YYYY-MM-DD` strings.
 */
export type LibraryEntry = {
    id: number;
    book_id: number;
    status: ReadingStatus;
    rating: number | null;
    notes: string | null;
    started_on: string | null;
    finished_on: string | null;
    created_at: string;
    updated_at: string;
    book: Book;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};
