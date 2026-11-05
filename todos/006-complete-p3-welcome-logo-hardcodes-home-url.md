---
status: complete
priority: p3
issue_id: '006'
tags: [code-review, quality]
dependencies: []
title: Homepage logo link hard-codes "/" instead of the Wayfinder home() route
location: resources/js/pages/Welcome.vue:72
---

# Homepage logo link hard-codes "/" instead of the Wayfinder home() route

## Problem

If the home address ever changes, the logo on the public homepage would still point to the old address while every other logo link follows the change.

`Welcome.vue` links the logo with `href="/"`, while the rest of the change (e.g. `AuthSplitLayout.vue`) uses Wayfinder's `home()` from `@/routes`.

## Findings

- Location: `resources/js/pages/Welcome.vue:72`
- Evidence: `href="/"`; line 9 imports only `login, register` from `@/routes`; `home()` exists in `resources/js/routes/index.ts`
- Found in: code review

## Acceptance Criteria

- [x] `Welcome.vue` has no literal route paths in `href`; the logo link uses `home()` from `@/routes`.
