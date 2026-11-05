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
        return new self(__("We can't reach the Book catalogue right now. Give it a minute and try again."), previous: $previous);
    }

    public static function readerLimitReached(): self
    {
        return new self(__("That's a lot of searching! Take a short breather and try again in a minute."));
    }

    public static function busy(): self
    {
        return new self(__('The Book catalogue is busy right now. Try again in a moment.'));
    }
}
