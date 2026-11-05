---
status: complete
priority: p2
issue_id: '010'
tags: [code-review, quality]
dependencies: []
title: Changing a Book on page 1 with the default sort still scrolls the shelf to the top, because focus follows the Book to its new first position
location: resources/js/pages/library/Index.vue:153
---

# Changing a Book on page 1 with the default sort still scrolls the shelf to the top, because focus follows the Book to its new first position

## Problem

A Reader scrolled down their shelf who changes a Book's status or rating is thrown back to the top of the page, because the Book moves to the front and the page follows it.

With the default "Recently updated" sort, a change moves the Book to index 0. Vue's keyed move drops focus to `body`, and `focusMenuOrNeighbour()` then focuses the Book's own button at the top of the grid, which scrolls the page there and undoes `preserveScroll`.

## Findings

- Location: `resources/js/pages/library/Index.vue:149-159`
- Evidence: `focusMenuOrNeighbour()` prefers `entry-menu-${entryId}` even when the Book has moved; `LibraryEntryController` orders by `updated_at desc` by default.
- Found in: code review

## Acceptance Criteria

- [x] On page 1 with the "Recently updated" sort and no filter, changing the Status of a Book far down the shelf leaves the scroll position where it was, and focus lands on a menu button that is visible in the viewport.

## Recommended Action

Approved by the user: same rule as 008. If the Book is no longer at its old position, focus the Book now at that position (or the last one), so the Reader keeps their place.

## Work Log

- 2026-11-05: `focusMenuOrNeighbour(position)` now always focuses the Book at the changed Book's old position (itself when it stayed put), with the heading only for an empty shelf; the remove dialog uses the same rule. Browser check at 375px, default sort, no filter: rating the last Book moved it to the front, focus landed on The Two Towers (in the viewport) and the scroll stayed (1929 → 1900px).
