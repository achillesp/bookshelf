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

    /** OpenLibrary allows identified clients 3 requests a second, shared by all Readers. */
    private const int GLOBAL_LIMIT = 3;

    private const int GLOBAL_LIMIT_DECAY_SECONDS = 1;

    private const string GLOBAL_LIMIT_KEY = 'openlibrary:global';

    private const int TIMEOUT_SECONDS = 3;

    private const int CONNECT_TIMEOUT_SECONDS = 2;

    /** Column lengths of books.openlibrary_id and authors.openlibrary_id; longer keys are skipped. */
    private const int KEY_MAX_LENGTH = 255;

    /** Column length of books.title; longer titles are cut. */
    private const int TITLE_MAX_LENGTH = 1024;

    /** Column length of authors.name; longer names are cut. */
    private const int AUTHOR_NAME_MAX_LENGTH = 512;

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
     * Find one work by its key (e.g. "OL893414W"), from the cache when a recent search returned it.
     *
     * @return Work|null
     *
     * @throws OpenLibraryException
     */
    public function findWork(string $openLibraryId, User $reader): ?array
    {
        $cacheKey = $this->workCacheKey($openLibraryId);
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            /** @var Work $cached */
            return $cached;
        }

        $work = collect($this->fetch('key:/works/'.$openLibraryId, 1, $reader))
            ->firstWhere('openlibrary_id', $openLibraryId);

        if ($work !== null) {
            Cache::put($cacheKey, $work, self::CACHE_TTL_SECONDS);
        }

        return $work;
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

        if (RateLimiter::tooManyAttempts(self::GLOBAL_LIMIT_KEY, self::GLOBAL_LIMIT)) {
            throw OpenLibraryException::busy();
        }

        RateLimiter::hit($readerKey, self::READER_LIMIT_DECAY_SECONDS);
        RateLimiter::hit(self::GLOBAL_LIMIT_KEY, self::GLOBAL_LIMIT_DECAY_SECONDS);

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
            $work = is_array($doc) ? $this->mapWork($doc) : null;

            if ($work !== null) {
                $works[] = $work;
            }
        }

        return $works;
    }

    /**
     * Map one search document to a work, cutting strings to their column lengths.
     *
     * Every field but key and title may be missing; a document without them, or with a key
     * longer than its column, is skipped, and so is an Author whose key is too long.
     *
     * @param  array<string, mixed>  $doc
     * @return Work|null
     */
    private function mapWork(array $doc): ?array
    {
        if (! is_string($doc['key'] ?? null) || ! is_string($doc['title'] ?? null)) {
            return null;
        }

        $openLibraryId = Str::after($doc['key'], '/works/');

        if (mb_strlen($openLibraryId) > self::KEY_MAX_LENGTH) {
            return null;
        }

        $authorKeys = is_array($doc['author_key'] ?? null) ? array_values($doc['author_key']) : [];
        $authorNames = is_array($doc['author_name'] ?? null) ? array_values($doc['author_name']) : [];
        $authors = [];

        foreach ($authorKeys as $index => $authorKey) {
            $name = $authorNames[$index] ?? null;

            if (is_string($authorKey) && is_string($name) && mb_strlen($authorKey) <= self::KEY_MAX_LENGTH) {
                $authors[] = ['openlibrary_id' => $authorKey, 'name' => Str::substr($name, 0, self::AUTHOR_NAME_MAX_LENGTH)];
            }
        }

        return [
            'openlibrary_id' => $openLibraryId,
            'title' => Str::substr($doc['title'], 0, self::TITLE_MAX_LENGTH),
            'authors' => $authors,
            'first_publish_year' => is_int($doc['first_publish_year'] ?? null) ? $doc['first_publish_year'] : null,
            'cover_id' => is_int($doc['cover_i'] ?? null) ? $doc['cover_i'] : null,
            'page_count_median' => is_int($doc['number_of_pages_median'] ?? null) ? $doc['number_of_pages_median'] : null,
        ];
    }
}
