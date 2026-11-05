---
status: complete
priority: p3
issue_id: '009'
tags: [code-review, quality]
dependencies: []
title: Shelf no longer shows any sign that a Book is being saved, and the `disabled` prop no longer disables the button
location: resources/js/components/LibraryEntryMenu.vue:23
---

# Shelf no longer shows any sign that a Book is being saved, and the `disabled` prop no longer disables the button

## Problem

After picking a new reading status or rating, nothing on the Book's cover shows the change is still being saved, so a Reader may think the click did nothing.

The earlier fix removed `:disabled` from the trigger Button, which was the only visible saving cue (`disabled:opacity-50`). The prop is still called `disabled` but now only disables the menu's choices.

## Findings

- Location: `resources/js/components/LibraryEntryMenu.vue:23`, `resources/js/pages/library/Index.vue:285`
- Evidence: trigger lost `:disabled`; `Index.vue` passes `:disabled="saving === entry.id"`; no `aria-busy`.
- Found in: code review

## Acceptance Criteria

- [x] While a Book's change is in flight its menu button is visibly marked as busy and has `aria-busy`, and the prop name says what it does.

## Work Log

- 2026-11-05: Prop renamed `disabled` → `saving`. While saving, the menu button gets `aria-busy="true"` and pulses at reduced opacity; the menu's choices are disabled. Seen in the browser via a MutationObserver during a Status change.
