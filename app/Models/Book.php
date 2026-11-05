<?php

namespace App\Models;

use Carbon\CarbonImmutable;
use Database\Factories\BookFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A work from OpenLibrary, shared by all Readers.
 *
 * @property int $id
 * @property string $openlibrary_id
 * @property string $title
 * @property int|null $first_publish_year
 * @property int|null $cover_id
 * @property int|null $page_count_median
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['openlibrary_id', 'title', 'first_publish_year', 'cover_id', 'page_count_median'])]
class Book extends Model
{
    /** @use HasFactory<BookFactory> */
    use HasFactory;

    /**
     * @return BelongsToMany<Author, $this>
     */
    public function authors(): BelongsToMany
    {
        return $this->belongsToMany(Author::class);
    }

    /**
     * @return HasMany<LibraryEntry, $this>
     */
    public function libraryEntries(): HasMany
    {
        return $this->hasMany(LibraryEntry::class);
    }
}
