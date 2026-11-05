---
status: complete
priority: p2
issue_id: '008'
tags: [code-review, quality]
dependencies: []
title: New focus fallback scrolls the shelf to the top whenever a changed Book leaves the current page or filter
location: resources/js/pages/library/Index.vue:107
---

# New focus fallback scrolls the shelf to the top whenever a changed Book leaves the current page or filter

## Problem

A Reader scrolled down a long shelf who changes a Book's status or rating, so that the Book disappears from the page they are on, is thrown back to the top of the page and loses their place.

`save()`'s `onFinish` calls `focusMenuOrHeading(entry.id)` when focus is on `<body>`. When the Book's `<li>` is gone after the reload (a Status filter hides it, or on page 2+ with the default "Recently updated" sort it moves to page 1), `#library-heading.focus()` runs with default scrolling, so the page jumps to the top and undoes `preserveScroll: true`. Reka refocuses the trigger after pointer selection too, so mouse users are affected as well. Before the fix, focus fell to `body` but the scroll position stayed.

## Findings

- Location: `resources/js/pages/library/Index.vue:107` (onFinish), `resources/js/pages/library/Index.vue:141-144` (`focusMenuOrHeading`)
- Evidence: `(trigger ?? document.getElementById('library-heading'))?.focus();` with no `preventScroll`; the request uses `preserveScroll: true`.
- Found in: code review

## Acceptance Criteria

- [x] After a Status or Rating change that removes the Book from the visible grid (filter active, or page 2+ with the default sort), the scroll position stays where it was and focus lands on a neighbouring Book's menu button, or on the heading without scrolling.

## Recommended Action

Approved by the user (option a): when the Book's menu button is gone, focus the menu button of the Book now at the same grid position (or the last one), and fall back to the heading only when the shelf is empty. Apply the same to the remove dialog's focus return, which has the same fallback.

## Work Log

- 2026-11-05: `focusMenuOrNeighbour()` focuses the Book's menu button, else the Book now at the same grid position (or the last one), else the heading. The remove dialog uses it too. Browser check at 375px with reduced motion: changing the last Completed Book to Want to Read under the Completed filter moved focus to the new last Book's menu button and the scroll position stayed (786 → 775px, the page got shorter).
