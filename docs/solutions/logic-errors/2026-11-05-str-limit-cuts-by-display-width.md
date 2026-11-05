---
module: Catalogue
date: 2026-11-05
problem_type: logic_error
component: service
symptoms:
    - 'Full-width (Chinese, Japanese, Korean) titles stored cut to about 512 characters in a VARCHAR(1024) column'
    - 'Author names in full-width scripts stored cut to about 256 characters in a VARCHAR(512) column'
root_cause: wrong_api
framework_version: 13.40.0
resolution_type: code_fix
severity: low
related_components: [api_integration, database]
tags: [str-limit, mb-strwidth, truncation, cjk, varchar, openlibrary]
---

# `Str::limit` cuts by display width, not characters: CJK text stops at half the column

## Problem

`App\Services\OpenLibrary::mapWork` bounds OpenLibrary titles and Author names to their column lengths before insert (`books.title` VARCHAR(1024), `authors.name` VARCHAR(512)). It used `Str::limit($value, N, '')`, which cut full-width text to about half of what fits.

## Symptoms

- A 1,000-character full-width title (`str_repeat('本', 1000)`) was stored as 512 characters, though the column holds 1,024.
- The first test only used ASCII and a range assertion (`toBeLessThanOrEqual(1024)`), so it passed.

## What didn't work

- **`Str::limit($title, 1024, '')`:** looks right and passes ASCII tests, but measures the wrong unit.

## Root cause

`Str::limit` measures with `mb_strwidth` and cuts with `mb_strimwidth`. Full-width characters have display width 2, so the "limit" is in terminal columns, not characters. MySQL VARCHAR(n) on utf8mb4 counts characters.

## Fix

Cut by character count (`app/Services/OpenLibrary.php`):

```php
// before
'title' => Str::limit($doc['title'], self::TITLE_MAX_LENGTH, ''),

// after
'title' => Str::substr($doc['title'], 0, self::TITLE_MAX_LENGTH),
```

Same for the Author name. In the same change, the work key got `max:255` in `StoreLibraryEntryRequest`, so an over-long key fails validation before any OpenLibrary call or cache lookup.

## Prevention

- To fit a column, cut with `Str::substr` / `mb_substr`; use `Str::limit` only for display text with an ellipsis.
- Test truncation with full-width input and an exact length: `tests/Feature/Library/StoreLibraryEntryTest.php` → "adding a work stores a full-width title by character count" (1,000 chars stored whole; 1,100 stored as exactly 1,024).
- SQLite (tests) ignores VARCHAR length, so prove the bound in PHP, not via a database error.

## Related

- None yet.
