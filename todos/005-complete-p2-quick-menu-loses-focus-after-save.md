---
status: complete
priority: p2
issue_id: '005'
tags: [code-review, quality]
dependencies: []
title: Quick-menu trigger is disabled while saving, so keyboard focus is lost after picking a Reading Status or Rating
location: resources/js/pages/library/Index.vue:274
---

# Quick-menu trigger is disabled while saving, so keyboard focus is lost after picking a Reading Status or Rating

## Problem

Keyboard and screen-reader users who change a Book's reading status or rating from its cover menu are sent back to the top of the page each time, and lose their place on the shelf.

`library/Index.vue` passes `:disabled="saving === entry.id"` to `LibraryEntryMenu`, which binds it to the trigger `<Button>`. Picking a radio item emits `status`/`rating`, `save()` calls `router.patch`, and its `onStart` sets `saving` synchronously. The menu then closes, and Reka's `DropdownMenuContent` focuses the trigger in a `setTimeout(0)`. With the global reduced-motion rule (0.01ms), and usually with normal latency too, that happens while the trigger is still disabled. `focus()` on a disabled button does nothing, so focus falls to `<body>`. The same happens when a Status filter is active and the new status removes the Book from the list: the trigger no longer exists. Remove already handles this through `restoreFocus`; Status and Rating don't.

## Findings

- Location: `resources/js/pages/library/Index.vue:274`, `resources/js/components/LibraryEntryMenu.vue:64`
- Evidence: `Index.vue:274` `:disabled="saving === entry.id"`; `LibraryEntryMenu.vue:64` binds `:disabled="disabled"` on the trigger Button; reka-ui `DropdownMenuContent` restores focus with `setTimeout(() => triggerElement.focus(), 0)`; `save()` has no focus handling in `onFinish`.
- Found in: code review

## Acceptance Criteria

- [x] After choosing a Reading Status or Rating from a cover's menu by keyboard, including with reduced motion on and with a Status filter that hides the Book, `document.activeElement` is that Book's menu button or the Library heading, never `body`.

## Work Log

- 2026-11-05: The menu trigger stays focusable during a save; the menu's choices are disabled instead. `save()`'s `onFinish` puts focus back on the Book's menu button, or on the Library heading when the Book has left the list. Checked in the browser with reduced motion: a Status change under the "Want to Read" filter moved focus to the heading; a Rating change that moved the Book to the front kept focus on its menu button.
