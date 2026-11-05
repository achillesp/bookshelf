<?php

namespace App\Services;

use RuntimeException;
use Throwable;

/**
 * A search or lookup on OpenLibrary that could not be made; its message is shown to the Reader.
 */
class OpenLibraryException extends RuntimeException
{
    public static function unavailable(Throwable $previous): self
    {
        return new self(__('Book search is unavailable right now. Please try again later.'), previous: $previous);
    }

    public static function readerLimitReached(): self
    {
        return new self(__("You've reached the search limit. Please wait a minute and try again."));
    }

    public static function busy(): self
    {
        return new self(__('Book search is busy right now. Please try again in a moment.'));
    }
}
