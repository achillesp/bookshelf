---
title: 'feat: Expand Stats with Library total, pages-read chart, data tables and Author breakdown'
type: feat
date: 2026-11-06
branch: feat/stats-expansion
---

# feat: Expand Stats with Library total, pages-read chart, data tables and Author breakdown

## Context

Request in conversation, 2026-11-06 (quoted in Premises); no ticket or brainstorm. Terms per `GLOSSARY.md`; ADR-001 constrains data only.

## Premises

- `user` (request, 2026-11-06): "in the top boxes (to-read, reading, completed) add a badge for total books in library (make it 1st)"; "add a 2nd graph of pages read per month, based on approximate page count"; "show these 2 graphs side by side with a year selection dropdown that affects and updates both of them"; "add a button to each graph to show a table of the data used (like a on/off expandable section of the card containing the graph - show in the same card)"; "expand the top authors table. Make it have columns for: total books (with horizontal bar), want to read, reading, completed, avg rating".
- `user`: that request overrides the cozy-redesign brainstorm's non-goal "New chart types or statistics on the Stats page" (`docs/brainstorms/2026-11-05-cozy-redesign-brainstorm.md:75`), which scoped that redesign.
- `user` (answers, 2026-11-06): a Book's pages count in the month of its Finished Date; Top Authors are ranked by total Books (any Reading Status), ties by Completed then name; average Rating = mean of the Reader's Ratings on that Author's Books, unrated skipped, `—` when none, one decimal.
- `observed`: `books.page_count_median` is a nullable median across Editions (`database/migrations/2026_11_05_105708_create_books_table.php:20`), shown as `~N pages` (`resources/js/pages/library/Show.vue:83`); ADR-001 says pages are approximate and must be shown so.
- `observed` (local MySQL): `php artisan tinker --execute` counts → `{"books":15,"books_no_pages":1,"completed_with_date":13,"completed_with_date_and_pages":13,"rated":14}`.
- `observed`: `completedByYear()` loads Finished Dates and buckets them in PHP; `years` = years with Completed Books + current year (`app/Services/ReadingStats.php:39`). Dashboard reads only its `completedPerMonth` key (`app/Http/Controllers/DashboardController.php:30`).
- `observed`: `topAuthors()` counts Completed entries only, grouped by `authors.id` (`app/Services/ReadingStats.php:70`).
- `observed` (probe, local MySQL and in-memory SQLite): `count(*)`, `sum(case when library_entries.status = ? then 1 else 0 end)`, `avg(library_entries.rating)` grouped by Author run on both; MySQL returns `"completed":"5","average_rating":"5.0000"` (strings), SQLite `"completed":1,"average_rating":4` → cast in PHP.
- `observed`: `MonthlyBarChart` already renders the monthly numbers as an sr-only table captioned "Completed Books" (`resources/js/components/MonthlyBarChart.vue:69`); Dashboard uses it as a compact sparkline (`resources/js/pages/Dashboard.vue:189`).
- `observed`: the year Select sits inside the Completed card and reloads with `preserveState: true` (`resources/js/pages/stats/Index.vue:49`, `:123`); the Completed card has a summary line (`:113`).
- `observed`: no JS test runner (`package.json`: build, check, types:check); Pest tests in `tests/Feature/StatsTest.php`.
- `inferred`: one Library Entry per Book (ADR-001), so "total books in library" = sum of `statusCounts`.
- `inferred`: `preserveState` keeps each card's local toggle open across a year change (checked in Verification).
- `user` (answers, 2026-11-06): the total tile links to the unfiltered Library; a Completed Book with no page count adds 0 pages, with no on-page note.
- `user` (answers, 2026-11-06): changing the year reloads only the chart props (partial reload); year totals appear as each chart's subtitle, like "8 Books finished in 2026.", not as a table totals row.
- `observed`: `StatsController` passes props as eager values (`app/Http/Controllers/StatsController.php:20`), so a partial reload would still compute them; Inertia skips only props wrapped in closures.

## Reuse inventory

- Data: `library_entries.status/rating/finished_on`, `books.page_count_median`, `author_book`; indexes `(user_id, status)`, `(user_id, finished_on)`.
- Code: `ReadingStats`, `StatsRequest` year rule, `MonthlyBarChart` and its table, `Select`/`Label`/`Button`, `ui/collapsible`, Wayfinder `library()`, `READING_STATUS_LABELS`.
- Total Books: code derives (sum of `statusCounts`). No person-owned values: column names and copy come from the request; "top 10" is the existing `TOP_AUTHORS` constant.

## Design options

- **A (chosen, 5 new concepts; +1 for the promoted partial reload):** `completedByYear()` also returns `pagesPerMonth` (same query plus a `books` join); `topAuthors()` returns per-status counts and average Rating via conditional aggregates; total tile summed client-side; `MonthlyBarChart` gains `label` (default "Completed Books", so Dashboard is untouched) and `showTable` (removes `sr-only` from its existing table); each card owns a toggle `ref`; one year Select above both cards; Top Authors as a `<table>` with a bar in the total cell. Concepts: `pagesPerMonth` prop, Author row shape, `label` prop, `showTable` toggle, total tile.
- **B (8 new concepts):** new `pagesByYear()` service method with its own query and a server `totalBooks` prop; new `ChartCard` component (heading, `ui/collapsible` toggle, slot) and new `MonthlyDataTable` component, while `MonthlyBarChart` keeps its sr-only table; same shared year Select, Author query and Author table as A. Concepts: `pagesByYear`, `totalBooks`, `pagesPerMonth`, Author row shape, `ChartCard`, `MonthlyDataTable`, total tile, collapsible toggle. Ruled out: same behaviour, but a second Finished-Date query and a second copy of the monthly table (`observed` above).

## Approach

- `ReadingStats::completedByYear()` (`app/Services/ReadingStats.php:39`): select `finished_on` and `books.page_count_median` for the Reader's Completed entries; bucket counts and pages; return `{years, completedPerMonth, pagesPerMonth}`.
- `ReadingStats::topAuthors()` (`:70`): drop the Completed filter; `count(*) as total_books`, one `sum(case …)` per `ReadingStatus` case, `avg(rating)`; order total desc, completed desc, name; cast to int and `round((float) …, 1)` or null.
- `StatsController` (`app/Http/Controllers/StatsController.php:20`): wrap `statusCounts` and `topAuthors` in closures and compute `completedByYear()` once inside closures for `years`, `completedPerMonth`, `pagesPerMonth`, so a partial reload computes only what it asks for. `showYear` (`resources/js/pages/stats/Index.vue:49`) adds `only: ['year', 'years', 'completedPerMonth', 'pagesPerMonth']`.
- `MonthlyBarChart.vue`: `label` drives caption ("{label} per month in {year}"), column header and tooltip; `showTable` toggles `sr-only`; table gets an `id` for `aria-controls`.
- `stats/Index.vue`:
  - Tiles: "Total in Library" first (sum, links to `library()`); grid `sm:grid-cols-2 lg:grid-cols-4`.
  - One year Select in a row above the charts, moved from the Completed card; `showYear` unchanged.
  - Charts in `grid lg:grid-cols-2`: Completed card and "Pages read by month" card (subtitle like `:113`, e.g. "~3,120 pages read in 2026.", labelled approximate per ADR-001). Each card: `Button` "Show data"/"Hide data" with `aria-expanded` + `aria-controls`.
  - Top Authors: full-width card, `<table>` with rank, Author, Total (number + `aria-hidden` bar scaled to the top total), Want to Read, Reading, Completed, Avg Rating; `overflow-x-auto` inside the card.

## Acceptance Criteria

- [ ] Four tiles, "Total in Library" first, equal to the sum of the status tiles
- [ ] Pages-read chart beside the Completed chart on wide screens, stacked on phones; one year Select changes both
- [ ] Pages per month = sum of `page_count_median` of the Reader's Completed Books finished that month; the card subtitle gives the year's total
- [ ] Changing the year fetches only the chart props; tiles and Authors table are not recomputed
- [ ] Each chart card's button shows/hides a month-by-month table inside the card; screen readers get the table either way
- [ ] Top Authors: top 10 by total Books with bar, Want to Read, Reading, Completed, Avg Rating (`—` when unrated); a co-written Book counts for each Author
- [ ] No horizontal page scroll at 360px; bar meets WCAG 2.2 AA non-text contrast in light and dark mode

## Task Breakdown

- [ ] **Total in Library tile** (AFK)
  - AC: four tiles, total first and correct
  - Blocked by: none
- [ ] **Side-by-side Completed and pages-read charts with data tables, under one year Select that reloads only the charts** (AFK)
  - AC: Pest test for `pagesPerMonth` (chosen year, this Reader only, null page count adds 0); both charts follow the year; Dashboard sparkline unchanged
  - AC: each chart card's toggle shows and hides its data table with correct `aria-expanded`; an open table stays open after a year change; `types:check` passes
  - AC: Pest partial-reload test (`X-Inertia-Partial-Data` headers) returns only the chart props for the chosen year; browser network tab shows the same
  - Blocked by: none
- [ ] **Top Authors table with status columns, bar and average Rating** (AFK)
  - AC: updated Pest test covers ranking by total, per-status counts, average Rating with unrated entries and all-unrated `null`, other Readers excluded; table renders
  - Blocked by: none

## Verification

- Seam: `tests/Feature/StatsTest.php` (copy the tests at `:31` and `:89`); `ReadingStats` is reached through the controllers, so test at the HTTP layer.
- `php artisan test --compact tests/Feature/StatsTest.php tests/Feature/DashboardTest.php`; `vendor/bin/pint --dirty --format agent`; `npm run check`; `npm run types:check`; `npm run build`.
- Browser `/stats` (light/dark, 360px and desktop): tiles, side-by-side charts, year change updates both, toggles by keyboard, Author table scrolls inside its card.
- Open a data table, change the year: it stays open (checks the `preserveState` premise; if not, keep toggle state in the page across visits).

## Not in MVP

| # | Kind | Item | Detail |
| --- | --- | --- | --- |
| 1 | extra | "N Completed Books without a page count" note on the pages card | Honest about gaps; 1 concept (count prop); local data has none missing among Completed |
| 2 | extra | Partial reload (`only: [...]`) on year change | **promoted** (Task 2) |
| 3 | extra | Totals row in the data tables | Declined: year totals go in each chart's subtitle instead |
