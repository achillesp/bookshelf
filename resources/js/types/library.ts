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
