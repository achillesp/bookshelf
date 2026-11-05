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
