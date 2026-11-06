---
module: Stats
date: 2026-11-06
problem_type: database_issue
component: database
symptoms:
    - "A Book Completed on Dec 31 is missing from that year's totals on SQLite but counted on MySQL"
    - "`where('finished_on', '<=', '2025-12-31')` misses a row whose stored value is `2025-12-31 00:00:00`"
    - 'Tests that create dates through the factory pass, while rows written through `today()` are dropped'
root_cause: wrong_api
framework_version: 13.40.0
resolution_type: code_fix
severity: medium
related_components: [service, model, testing_framework]
tags:
    [sqlite, mysql, date-cast, date-range, finished-on, half-open-range, carbon]
---

# SQLite `date` column holds `Y-m-d` or `Y-m-d H:i:s`: `<= 'YYYY-12-31'` drops Dec 31

## Problem

`library_entries.finished_on` is a `date` column cast as `date:Y-m-d`. On SQLite (tests and CI, per `CLAUDE.md`) the stored text depends on **how the value was written**, so a year filter written as `between Jan 1 and Dec 31` silently drops some Dec 31 rows. MySQL's `DATE` type truncates every write to `Y-m-d`, so the same query is right there, and the difference hides.

## Symptoms

Probed on in-memory SQLite (Laravel 13.40):

| Written as                                                                                      | Stored                | `<= '2025-12-31'` finds it? |
| ----------------------------------------------------------------------------------------------- | --------------------- | --------------------------- |
| string `'2025-12-31'` (the factory's `completed('2025-12-31')`)                                 | `2025-12-31`          | yes                         |
| Carbon `CarbonImmutable::parse('2025-12-31 15:30')`                                             | `2025-12-31 15:30:00` | no                          |
| `today()` (what `LibraryEntry` sets when a Book is Completed, `app/Models/LibraryEntry.php:84`) | `2026-11-06 00:00:00` | no, on Dec 31               |

SQLite compares these as text, and `'2025-12-31 00:00:00' > '2025-12-31'`.

## What didn't work

- **Assuming every write is stored with a time.** While building `ReadingStats::monthlyTotals()` I first stated that Eloquent always stores `date` casts as `Y-m-d H:i:s` on SQLite. A probe showed strings are stored as given. Only Carbon values get the time. The `date:Y-m-d` format affects serialization, not storage.
- **Trusting a factory-based test to catch it.** The Stats tests' Dec 31 entries are created from strings, so they are stored without a time and an `<= Dec 31` bound still passes them.

## Root cause

Eloquent converts a Carbon value with the model's storage format (`Y-m-d H:i:s`) but writes a string as is. SQLite has no real date type: the column stores whatever text arrives and compares it as text. The column ends up holding two formats, and an inclusive upper bound on the last day only matches one of them.

## Fix

Filter a period with a **half-open range**: from the first day, up to but not including the first day of the next period. Both stored formats compare correctly, and on MySQL the plain column comparison still uses the `(user_id, finished_on)` index (`whereYear()`/`whereDate()` wrap the column in a function and do not).

`app/Services/ReadingStats.php`, `monthlyTotals()`. The previous code loaded every year's dates and filtered in PHP; moving the filter into SQL is where the trap appears:

```php
// the obvious version: drops Carbon-written rows on the last day (SQLite)
->whereBetween('finished_on', ["{$year}-01-01", "{$year}-12-31"])

// after: correct for `2025-12-31` and `2025-12-31 00:00:00`
->where('library_entries.finished_on', '>=', CarbonImmutable::create($year)->toDateString())
->where('library_entries.finished_on', '<', CarbonImmutable::create($year + 1)->toDateString())
```

Bucketing by month happens in PHP (`CarbonImmutable::parse($date)->month`), which also reads both formats. SQL month functions differ between MySQL and SQLite.

## Prevention

- For any `date`/`datetime` period filter, use `>= start` and `< next start`, never `<= last day`.
- Never compare a `date` column for equality with a `Y-m-d` string on SQLite (`where('finished_on', '2026-11-06')`): Carbon-written rows hold `2026-11-06 00:00:00` and won't match.
- A test that catches it has to write the boundary date **the way the app does** (a Carbon value), not as a factory string. `tests/Feature/StatsTest.php`, "stats count a Book Completed on the last day of the year in December":

```php
$entry = LibraryEntry::factory()->for($reader)->reading('2025-12-01')->create();
$this->travelTo('2025-12-31 18:00:00');
$entry->changeStatus(ReadingStatus::Completed);   // writes finished_on = today()
$entry->save();
$this->travelTo('2026-11-05 10:00:00');

$response = $this->actingAs($reader)->get(route('stats', ['year' => 2025]));

$response->assertInertia(fn (Assert $page) => $page
    ->where('completedPerMonth', [0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1]),
);
```

Swapping the bound for `<= '2025-12-31'` turns only this test red (December 0); the factory-string tests stay green.

## Related

- See also: none yet.
