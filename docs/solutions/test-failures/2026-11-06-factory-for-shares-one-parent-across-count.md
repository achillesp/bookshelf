---
module: System
date: 2026-11-06
problem_type: test_failure
component: testing_framework
symptoms:
    - 'SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed: library_entries.user_id, library_entries.book_id'
    - '`->for(Book::factory())->count(2)` creates two Library Entries but only one Book'
    - 'Without a unique constraint: test data silently has n children of ONE parent instead of n parents'
root_cause: wrong_api
framework_version: 13.40.0
resolution_type: test_fix
severity: low
related_components: [model]
tags: [factory, for, count, belongs-to, unique-constraint, pest, test-data]
---

# Factory `->for(Parent::factory())->count(n)` creates ONE parent shared by all n models

## Problem

In the Top Authors test, giving the Reader two Books by the same Author with

```php
LibraryEntry::factory()->for($reader)->reading()
    ->for(Book::factory()->hasAttached($leGuin))
    ->count(2)
    ->create();
```

created one Book and tried to add it to the Reader's Library twice.

## Symptoms

```
SQLSTATE[23000]: Integrity constraint violation: 19 UNIQUE constraint failed:
library_entries.user_id, library_entries.book_id
```

`library_entries` has `unique(['user_id', 'book_id'])`: one Library Entry per Book per Reader (ADR-001). On a table without such a constraint there is no error: the test just runs on different data than it says (n entries of one Book, so per-Book counts are wrong).

## What didn't work

- Fixed on the first attempt once the cause was clear. The confusing part: an older line in the same test, `->completed()->for(Book::factory()->hasAttached($leGuin))->count(5)->create()`, worked. It has no `->for($reader)`, so each of the five entries got its own new User, and five Users sharing one Book never breaks `(user_id, book_id)`. Sharing the Book was the point there.

## Root cause

`for()` registers a belongs-to parent, and the parent is created **once** and cached for every model the factory call makes. `BelongsToRelationship::resolver()` (`vendor/laravel/framework/src/Illuminate/Database/Eloquent/Factories/BelongsToRelationship.php`):

```php
if (! $this->resolved) {
    $instance = /* ... */ $this->factory->create();   // first model: create the parent
    return $this->resolved = $instance->getKey();      // ...and remember it
}

return $this->resolved;                                // every later model: same parent
```

A factory used as an **attribute value** works the other way: `Factory::expandAttributes()` calls `->create()` for each model. That's why `LibraryEntryFactory`'s default `'book_id' => Book::factory()` gives every entry its own Book.

## Fix

`tests/Feature/StatsTest.php`: one `create()` per Book:

```php
// before: one Book, two entries → unique violation
LibraryEntry::factory()->for($reader)->reading()
    ->for(Book::factory()->hasAttached($leGuin))->count(2)->create();

// after: two Books
foreach (range(1, 2) as $book) {
    LibraryEntry::factory()->for($reader)->reading()
        ->for(Book::factory()->hasAttached($leGuin))->create();
}
```

Equivalent without a loop, because an attribute factory is created per model:

```php
LibraryEntry::factory()->for($reader)->reading()
    ->state(['book_id' => Book::factory()->hasAttached($leGuin)])
    ->count(2)
    ->create();
```

## Prevention

| You want                                        | Use                                                                |
| ----------------------------------------------- | ------------------------------------------------------------------ |
| n children, **one shared** parent               | `->for(Parent::factory())->count(n)`                               |
| n children, **one parent each**                 | `->state(['parent_id' => Parent::factory()])->count(n)`, or a loop |
| n children spread over parents you already have | `->for(...)` per call, or a `Sequence` of parents                  |

When a test needs "n Books by this Author for this Reader", read `->for(...)->count(n)` as "n entries of the same Book".

## Related

- See also: none yet.
