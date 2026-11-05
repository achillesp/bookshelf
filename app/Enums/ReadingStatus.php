<?php

namespace App\Enums;

/**
 * Where a Library Entry stands.
 */
enum ReadingStatus: string
{
    case WantToRead = 'want_to_read';
    case Reading = 'reading';
    case Completed = 'completed';
}
