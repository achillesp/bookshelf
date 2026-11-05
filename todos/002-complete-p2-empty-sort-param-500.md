---
status: complete
priority: p2
issue_id: '002'
tags: [code-review, quality]
dependencies: []
title: 'An empty `sort` query parameter causes a 500 on the Library page'
location: app/Http/Requests/Library/LibraryIndexRequest.php:41
---

# An empty `sort` query parameter causes a 500 on the Library page

## Problem

Opening the Library with an empty sort option in the address (for example from a shared or edited link) shows an error page instead of the Library.

`LibraryIndexRequest::sort()` returns `$this->string('sort', 'updated')->toString()`. The `nullable` rule lets `?sort=` through, and ConvertEmptyStringsToNull turns it into `null`. `input()` returns that `null` because the key exists, so the default never applies. `sort()` therefore returns `''`, and `LibraryIndexRequest::SORTS['']` in `LibraryEntryController::index` throws an 'Undefined array key' ErrorException (500). Fix: fall back on emptiness instead of key absence, e.g. `return $this->filled('sort') ? $this->string('sort')->toString() : 'updated';` or `return $this->validated('sort') ?? 'updated';`. Add a LibraryIndexTest case that requests `route('library.index', ['sort' => ''])` and asserts the default sort.

## Findings

- Location: `app/Http/Requests/Library/LibraryIndexRequest.php:41`
- Evidence: `return $this->string('sort', 'updated')->toString();` with rule `'sort' => ['nullable', Rule::in(...)]`. In vendor InteractsWithData::string, `$this->data($key, $default)` is `data_get(...)`, which returns the stored `null` when the key exists. The controller then does `$sort = LibraryIndexRequest::SORTS[$request->sort()];`
- Found in: code review

## Acceptance Criteria

- [x] GET /library?sort= returns 200 with filters.sort = 'updated', and a test covers it.
- [x] Tests cover it and pass

## Work Log

- 2026-11-05: `sort()` and `year()` fall back when the value is empty, not only when the key is missing. The same fix covers an empty `?year=` on the Dashboard, which was reported as a P3. Regression tests are in LibraryIndexTest and DashboardTest.
