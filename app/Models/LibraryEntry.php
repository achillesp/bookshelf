<?php

namespace App\Models;

use App\Enums\ReadingStatus;
use Carbon\CarbonImmutable;
use Database\Factories\LibraryEntryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A Reader's personal record of one Book in their Library.
 *
 * @property int $id
 * @property int $user_id
 * @property int $book_id
 * @property ReadingStatus $status
 * @property int|null $rating
 * @property string|null $notes
 * @property CarbonImmutable|null $started_on
 * @property CarbonImmutable|null $finished_on
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 */
#[Fillable(['book_id', 'status', 'rating', 'notes', 'started_on', 'finished_on'])]
class LibraryEntry extends Model
{
    /** @use HasFactory<LibraryEntryFactory> */
    use HasFactory;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ReadingStatus::class,
            'rating' => 'integer',
            'started_on' => 'date:Y-m-d',
            'finished_on' => 'date:Y-m-d',
        ];
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return BelongsTo<Book, $this>
     */
    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class);
    }

    /**
     * Move the entry to a Reading Status, filling in or clearing the Started and Finished dates.
     */
    public function changeStatus(ReadingStatus $status): void
    {
        if ($this->status === $status) {
            return;
        }

        $this->status = $status;

        match ($status) {
            ReadingStatus::WantToRead => $this->forceFill(['started_on' => null, 'finished_on' => null]),
            ReadingStatus::Reading => $this->forceFill([
                'started_on' => $this->started_on ?? today(),
                'finished_on' => null,
            ]),
            ReadingStatus::Completed => $this->forceFill([
                'started_on' => $this->started_on ?? today(),
                'finished_on' => today(),
            ]),
        };
    }
}
