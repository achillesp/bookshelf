<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

/**
 * Searches OpenLibrary works on behalf of a Reader, caching results and limiting uncached calls.
 *
 * @phpstan-type Work array{
 *     openlibrary_id: string,
 *     title: string,
 *     authors: list<array{openlibrary_id: string, name: string}>,
 *     first_publish_year: int|null,
 *     cover_id: int|null,
 *     page_count_median: int|null,
 * }
 */
class OpenLibrary
{
    private const string SEARCH_URL = 'https://openlibrary.org/search.json';

    private const string FIELDS = 'key,title,author_name,author_key,first_publish_year,cover_i,number_of_pages_median';

    private const int RESULT_LIMIT = 20;

    private const int CACHE_TTL_SECONDS = 24 * 60 * 60;

    private const int READER_LIMIT = 20;

    private const int READER_LIMIT_DECAY_SECONDS = 60;

    private const int TIMEOUT_SECONDS = 3;

    private const int CONNECT_TIMEOUT_SECONDS = 2;

    /**
     * Search works by title, author or ISBN; each result is also cached under its work key.
     *
     * @return list<Work>
     *
     * @throws OpenLibraryException
     */
    public function search(string $query, User $reader): array
    {
        $query = Str::of($query)->squish()->lower()->toString();

        return Cache::remember(
            'openlibrary:search:'.hash('xxh128', $query),
            self::CACHE_TTL_SECONDS,
            function () use ($query, $reader): array {
                $works = $this->fetch($query, self::RESULT_LIMIT, $reader);

                foreach ($works as $work) {
                    Cache::put($this->workCacheKey($work['openlibrary_id']), $work, self::CACHE_TTL_SECONDS);
                }

                return $works;
            },
        );
    }

    /**
     * Cache key holding one mapped work.
     */
    private function workCacheKey(string $openLibraryId): string
    {
        return 'openlibrary:work:'.$openLibraryId;
    }

    /**
     * Call the search API, counting the call toward the Reader's limit.
     *
     * @return list<Work>
     *
     * @throws OpenLibraryException
     */
    private function fetch(string $query, int $limit, User $reader): array
    {
        $readerKey = 'openlibrary:'.$reader->id;

        if (RateLimiter::tooManyAttempts($readerKey, self::READER_LIMIT)) {
            throw OpenLibraryException::readerLimitReached();
        }

        RateLimiter::hit($readerKey, self::READER_LIMIT_DECAY_SECONDS);

        try {
            $docs = Http::withUserAgent('BookShelf ('.config('services.openlibrary.contact').')')
                ->connectTimeout(self::CONNECT_TIMEOUT_SECONDS)
                ->timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get(self::SEARCH_URL, [
                    'q' => $query,
                    'fields' => self::FIELDS,
                    'limit' => $limit,
                ])
                ->throw()
                ->json('docs', []);
        } catch (ConnectionException|RequestException $exception) {
            throw OpenLibraryException::unavailable($exception);
        }

        $works = [];

        foreach (is_array($docs) ? $docs : [] as $doc) {
            if (is_array($doc) && is_string($doc['key'] ?? null) && is_string($doc['title'] ?? null)) {
                $works[] = $this->mapWork($doc);
            }
        }

        return $works;
    }

    /**
     * Map one search document to a work; every field but key and title may be missing.
     *
     * @param  array<string, mixed>  $doc
     * @return Work
     */
    private function mapWork(array $doc): array
    {
        $authorKeys = is_array($doc['author_key'] ?? null) ? array_values($doc['author_key']) : [];
        $authorNames = is_array($doc['author_name'] ?? null) ? array_values($doc['author_name']) : [];
        $authors = [];

        foreach ($authorKeys as $index => $authorKey) {
            $name = $authorNames[$index] ?? null;

            if (is_string($authorKey) && is_string($name)) {
                $authors[] = ['openlibrary_id' => $authorKey, 'name' => $name];
            }
        }

        return [
            'openlibrary_id' => Str::after($doc['key'], '/works/'),
            'title' => $doc['title'],
            'authors' => $authors,
            'first_publish_year' => is_int($doc['first_publish_year'] ?? null) ? $doc['first_publish_year'] : null,
            'cover_id' => is_int($doc['cover_i'] ?? null) ? $doc['cover_i'] : null,
            'page_count_median' => is_int($doc['number_of_pages_median'] ?? null) ? $doc['number_of_pages_median'] : null,
        ];
    }
}
