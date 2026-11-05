---
module: Library
date: 2026-11-05
problem_type: logic_error
component: controller
symptoms:
    - 'GET /library?sort= returns 500: ErrorException Undefined array key ""'
    - 'GET /dashboard?year= charts year 0 instead of the current year'
root_cause: wrong_api
framework_version: 13.40.0
resolution_type: code_fix
severity: medium
related_components: [model]
tags:
    [
        form-request,
        query-string,
        convert-empty-strings-to-null,
        request-defaults,
        validation,
    ]
---

# Empty query parameter ignores the default in `$request->string()` / `integer()`

## Problem

Form Requests for the Library and Dashboard read optional query parameters with a default, e.g. `$this->string('sort', 'updated')` and `$this->integer('year', now()->year)`. An empty value in the URL (`?sort=`, `?year=`) skipped the default.

## Symptoms

- `GET /library?sort=` → 500, `ErrorException: Undefined array key ""` at `LibraryIndexRequest::SORTS[$request->sort()]`.
- `GET /dashboard?year=` → the chart shows year 0.

## What didn't work

- Fixed on the first attempt once the cause was clear. The `nullable` validation rule alone doesn't help: it lets the empty value through as valid.

## Root cause

The `ConvertEmptyStringsToNull` middleware turns `?sort=` into `sort => null`. The key now exists, so `InteractsWithData::string($key, $default)` → `data_get($input, $key, $default)` returns the stored `null`, not the default; `string()` then casts it to `''` and `integer()` to `0`. Request helper defaults apply only when the key is **absent**, not when it's empty.

## Fix

Fall back when the value is empty, not only when the key is missing (`app/Http/Requests/Library/LibraryIndexRequest.php`, `app/Http/Requests/DashboardRequest.php`):

```php
// before
return $this->string('sort', 'updated')->toString();
return $this->integer('year', now()->year);

// after
return $this->filled('sort') ? $this->string('sort')->toString() : 'updated';
return $this->filled('year') ? $this->integer('year') : now()->year;
```

`$this->enum('status', ReadingStatus::class)` is fine as is: it returns `null` for an empty value.

## Prevention

- Use `filled()` (or `$this->validated('x') ?? default`) for defaults on optional query parameters; don't rely on the `$default` argument of `string()`, `integer()`, `input()`.
- Add a test per optional parameter with an empty value: `get(route('library.index', ['sort' => '']))->assertOk()` and assert the default (see `tests/Feature/Library/LibraryIndexTest.php`, `tests/Feature/DashboardTest.php`).

## Related

- See also: [Inertia form loses typed text](../ui-bugs/2026-11-05-inertia-form-loses-unsaved-input.md), another finding from the same review.
