---
status: complete
priority: p2
issue_id: '012'
tags: [code-review, quality]
dependencies: []
title: Closing the quick menu now scrolls the shelf even when the Book did not move (Escape, same choice, Remove, non-moving sort)
location: resources/js/components/LibraryEntryMenu.vue:58
---

# Closing the quick menu now scrolls the shelf even when the Book did not move

## Problem

Every time someone opens and closes a book's menu near the bottom of the screen, even without changing anything, the page jumps to show the whole book card, which is disorienting and goes beyond the requested 'scroll only when the book moved'.

`returnFocus` runs on every menu close and always calls `focusAndReveal(trigger, li)`, whose `scrollIntoView({ block: 'nearest' })` on the tall `<li>` (with `scroll-mb-20`) scrolls whenever the card isn't fully on screen. Reka's default only focused the button, which never scrolls a visible button.

## Findings

- Location: `resources/js/components/LibraryEntryMenu.vue:58-65`, `resources/js/lib/utils.ts:23-29`, `resources/js/pages/library/Index.vue:331`
- Evidence: unconditional `scrollIntoView`; seen in this session's browser check (Escape moved the page 775 → 807px).
- Found in: code review

## Acceptance Criteria

- [x] When the quick menu closes and the Book's menu button is already visible, the page does not scroll. When the saved Book has moved off-screen, the page still scrolls smoothly to it (instantly under reduced motion).

## Work Log

- 2026-11-06: `focusAndReveal()` now scrolls only when the focused button is outside the viewport minus the card's scroll margins (which leave room for the sticky header and bottom tabs). Browser check at 375px: Escape on a Book whose card ran below the fold left the scroll at 551px with focus back on its button; rating the last Book still moved it to the front with a smooth 42-step scroll and focus on it.
