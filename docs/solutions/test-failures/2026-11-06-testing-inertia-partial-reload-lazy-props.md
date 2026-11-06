---
module: Stats
date: 2026-11-06
problem_type: test_failure
component: testing_framework
symptoms:
    - "A partial-reload test asserting `->missing('topAuthors')` passes even though every prop is still computed"
    - 'Expected response status code [200] but received 409 (hand-written X-Inertia headers)'
    - 'Not a valid Inertia response (from `reloadOnly`, when the reload request errors)'
root_cause: wrong_api
framework_version: 13.40.0
resolution_type: test_fix
severity: medium
related_components: [controller]
tags:
    [
        inertia,
        partial-reload,
        reload-only,
        lazy-props,
        closures,
        query-count,
        pest,
    ]
---

# Testing an Inertia partial reload: a response-only test passes against eager props

## Problem

The Stats year Select reloads only the chart props (`only: ['year', 'completedPerMonth', 'pagesPerMonth']`) so that the tiles and Top Authors are not recomputed. A Pest test that checks the partial response for missing props cannot prove that: it passes whether the other props are skipped or computed and thrown away.

## Symptoms

- With every prop passed as an eager value, `->reloadOnly([...], fn ($reload) => $reload->missing('statusCounts'))` passed. Not red, so not a test of the behaviour.
- Building the partial request by hand (`X-Inertia`, `X-Inertia-Partial-Component`, `X-Inertia-Partial-Data`) returned `409`: the Inertia middleware rejects a request without the current asset version (`X-Inertia-Version`).
- `reloadOnly` failed with `Not a valid Inertia response`. The real cause was a `500` in the reload: the test's `QueryExecuted` import had been removed by the format-on-edit hook before the code using it was added, so the listener threw. The message names neither.

## What didn't work

- **Asserting only on the partial response.** The adapter filters unrequested props out of the response even when it evaluated them, so the response looks the same either way.
- **Hand-written partial-reload headers.** They need the asset version too; `reloadOnly` already sends the page's URL (query string included), component and version.

## Root cause

Inertia's Laravel adapter evaluates a prop on a partial reload only if it is a closure (or `Inertia::optional`/`defer`). A plain value has already been computed by the time `Inertia::render()` runs; the partial reload just leaves it out of the JSON. "Was it computed" is visible only in the work done (queries), not in the response.

## Fix

Controller: every prop that a partial reload may skip is a closure, and props that share one query share a memoized result (`app/Http/Controllers/StatsController.php`):

```php
// before: every query runs on every request
'statusCounts' => $stats->statusCounts($reader),
...$stats->monthlyTotals($reader, $year),

// after: only the requested props run
$totals = null;
$monthlyTotals = function () use (&$totals, $stats, $reader, $year): array {
    return $totals ??= $stats->monthlyTotals($reader, $year);
};

return Inertia::render('stats/Index', [
    'statusCounts' => fn (): array => $stats->statusCounts($reader),
    'year' => $year,
    'completedPerMonth' => fn (): array => $monthlyTotals()['completedPerMonth'],
    'pagesPerMonth' => fn (): array => $monthlyTotals()['pagesPerMonth'],
    // ...
]);
```

`once()` doesn't fit here: it runs its callback immediately and returns the value, not a memoized closure.

Test (`tests/Feature/StatsTest.php`): use `reloadOnly` and count the queries of the reload. Register the listener **after** the full first request so only the reload is counted:

```php
$response = $this->actingAs($reader)->get(route('stats', ['year' => 2025]));
$libraryEntryQueries = 0;
DB::listen(function (QueryExecuted $query) use (&$libraryEntryQueries) {
    if (str_contains($query->sql, 'library_entries')) {
        $libraryEntryQueries++;
    }
});

$response->assertInertia(fn (Assert $page) => $page
    ->reloadOnly(['year', 'completedPerMonth', 'pagesPerMonth'], fn (Assert $reload) => $reload
        ->where('pagesPerMonth', [0, 0, 300, 0, 0, 0, 0, 0, 0, 0, 0, 0])
        ->missing('statusCounts')
        ->missing('topAuthors'),
    ),
);
expect($libraryEntryQueries)->toBe(1);
```

Against eager props this fails with `Failed asserting that 4 is identical to 1`; with closures it passes, and the `1` also proves the two chart props share one query.

## Prevention

- A partial-reload test is red first only if it measures work done: count queries, not just keys in the response.
- Prefer `reloadOnly`/`reloadExcept` over hand-built headers.
- When `reloadOnly` says `Not a valid Inertia response`, look for an exception or a `409` in the reload, not at the assertion.
- In the browser, check the reload's response body in the network tab: it should hold only the requested props (plus `errors`).

## Related

- See also: [Inertia form loses unsaved input](../ui-bugs/2026-11-05-inertia-form-loses-unsaved-input.md) (another Inertia behaviour that a quick check gets wrong).
