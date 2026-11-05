---
status: complete
priority: p2
issue_id: '011'
tags: [code-review, quality]
dependencies: []
title: After a change moves a Book, focus lands silently on a different Book's menu, so screen reader and keyboard users are not told the save worked and their next action hits the wrong Book
location: resources/js/pages/library/Index.vue:150
---

# After a change moves a Book, focus lands silently on a different Book's menu

## Problem

When someone using a keyboard or screen reader changes a book's status or rating, the cursor jumps to a different book's button with no message saying the change saved or where the book went, so their next keypress may change the wrong book.

With the default "Recently updated" sort every Status or Rating change moves the Book to the front, and the todo 010 rule focuses the Book now in the old slot, whose button reads `Change “<other title>”`. The page's only live region is the count paragraph, which doesn't change on a Status or Rating edit.

## Findings

- Location: `resources/js/pages/library/Index.vue:150-158`, live region at `Index.vue:200-204`
- Evidence: focus goes to `entries[Math.min(position, entries.length - 1)]`; the trigger's `aria-label` names that other Book; no status announcement after a save.
- Found in: code review

## Acceptance Criteria

- [x] After a Status or Rating change that moves the Book, a polite live region already in the DOM announces the change with the Book's title and its new place, while focus still follows the position rule.

## Work Log

- 2026-11-05: An always-rendered `sr-only` `aria-live="polite"` paragraph under the Library heading is cleared when a save starts and, after the reload, says “<title>” saved, saved and moved to the front of your Library, moved to place N on this page, or that it's no longer on this page. Browser check at 375px: rating the last Book announced "“The Two Towers” saved and moved to the front of your Library." while focus stayed at its old place and the scroll held.
