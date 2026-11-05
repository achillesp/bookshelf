---
status: complete
priority: p2
issue_id: '004'
tags: [code-review, simplicity]
dependencies: []
title: 'ReadingStatus::label() has no caller'
location: app/Enums/ReadingStatus.php:14
---

# ReadingStatus::label() has no caller

## Problem

The status names are written out twice, and one copy is never shown to anyone, so a rename could change one copy and miss the other without anyone noticing.

The enum's label() method is never called: no PHP code or test uses it, and the UI labels come from READING_STATUS_LABELS in resources/js/types/library.ts. So the same three labels are kept in two places, and only the TypeScript copy is ever shown. Fix: delete label() from app/Enums/ReadingStatus.php so the enum holds only its cases. Bring it back when server-side code needs a label, for example a mail or export.

## Findings

- Location: `app/Enums/ReadingStatus.php:14`
- Evidence: `grep -rn "label()" app tests` finds only the definition at app/Enums/ReadingStatus.php:14. The page labels come from READING_STATUS_LABELS (resources/js/types/library.ts:27), which Dashboard.vue, library/Index.vue and ReadingStatusSelect.vue import.
- Found in: code review

## Acceptance Criteria

- [x] ReadingStatus has no label() method, or label() has at least one caller.
- [x] Tests cover it and pass

## Work Log

- 2026-11-05: Removed the unused `ReadingStatus::label()`. The UI labels live in `READING_STATUS_LABELS`.
