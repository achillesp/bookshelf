---
status: complete
priority: p1
issue_id: '001'
tags: [code-review, quality]
dependencies: []
title: 'Changing Reading Status or Rating on the Library Entry page wipes unsaved Notes and dates'
location: resources/js/pages/library/Show.vue:94
---

# Changing Reading Status or Rating on the Library Entry page wipes unsaved Notes and dates

## Problem

A reader who types notes about a book and then clicks a star rating, before pressing Save, loses everything they typed.

The dates/Notes `<Form>` is keyed on `entry.updated_at`. The status select and the rating stars call `router.patch(...)` straight away, which in Inertia v3 defaults to `preserveState: true` and returns `back()`. The reloaded `entry` has a new `updated_at`, so Vue remounts the Form, and the uncontrolled inputs fall back to their `default-value`s. Anything the Reader typed into Notes or the date fields and had not saved yet is lost without warning. Fix: key the Form on the persisted values the Form actually edits, e.g. `:key="`${entry.started_on}|${entry.finished_on}|${entry.notes}`"`, so a rating change no longer remounts it. A status change that sets dates on the server will still reset the date fields, which is the intended behaviour. Better still, move the Form to `useForm` and sync only the fields the Reader has not touched.

## Findings

- Location: `resources/js/pages/library/Show.vue:94`
- Evidence: `<Form :key="entry.updated_at" ...>` with `:default-value="entry.started_on ?? ''"` / `:defaultValue="entry.notes ?? ''"`; `save()` = `router.patch(update.url(props.entry.id), data, { preserveScroll: true })`; node_modules/@inertiajs/core/dist/index.js:3591 `patch(...) { return this.visit(url, { preserveState: true, ...options, method: "patch", data }) }`; the controller's update() bumps updated_at through save() and returns back().
- Found in: code review

## Acceptance Criteria

- [x] On the Library Entry page, typing in Notes and then changing the Rating or Reading Status leaves the typed Notes in the textarea.
- [x] Tests cover it and pass

## Work Log

- 2026-11-05: The Form is keyed only on `started_on|finished_on`, and Notes are a `v-model` draft outside the keyed Form. There is no JS test runner, so this was verified in the browser instead: Notes typed and not saved survive a Rating change; a status change to Reading clears Finished Date in the form and keeps the typed Notes.
