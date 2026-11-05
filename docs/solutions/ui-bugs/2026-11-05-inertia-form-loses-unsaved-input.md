---
module: Library
date: 2026-11-05
problem_type: ui_bug
component: frontend_component
symptoms:
    - 'Notes typed on the Library Entry page vanish after a failed save (validation error on the dates)'
    - 'Saved Notes are not shown in the textarea when the page loads'
    - 'Unsaved Notes and dates are wiped when the Reader clicks a star Rating or changes the Reading Status'
root_cause: wrong_api
framework_version: 3.5.45
resolution_type: code_fix
severity: high
related_components: [template]
tags: [inertia-form, vue, textarea, default-value, component-key, unsaved-input]
---

# Inertia form loses typed text: textarea `:value` / `:default-value` and a `:key` on `updated_at`

## Problem

On `resources/js/pages/library/Show.vue`, an Inertia v3 `<Form>` edits the dates and Notes of a Library Entry, next to a status select and star Rating that save immediately with `router.patch`. Text the Reader had typed but not saved was lost in three different ways.

## Symptoms

- A Finished Date before the Started Date shows "The Finished Date cannot be before the Started Date." and the Notes textarea is suddenly empty.
- After switching the textarea to `:default-value`, a fresh page load shows an empty Notes field even though Notes are saved.
- Typing Notes, then clicking a star Rating (before pressing Save), clears the Notes and any edited date.

## What didn't work

- **`:value="entry.notes ?? ''"` on the textarea:** when the validation error re-renders the form, Vue patches the DOM `value` back to the prop and overwrites what the Reader typed.
- **`:default-value="entry.notes ?? ''"`:** on a native element Vue treats the kebab-case name as an HTML attribute called `default-value`, not the DOM `defaultValue` property. The text was kept on error only because nothing was ever set; existing Notes never showed.
- **`:defaultValue` (camelCase) plus `<Form :key="entry.updated_at">`:** load and validation errors worked, but every unrelated save (the Rating) bumps `updated_at`, so the Form remounts and its uncontrolled inputs reset to their defaults.

## Root cause

Two separate things:

1. Vue binds `:value` as a live DOM property (re-applied on every patch), and a kebab-case binding on a native element becomes an attribute. Only `defaultValue` in camelCase sets the property that seeds an uncontrolled textarea.
2. The Form was re-keyed on `updated_at` so that dates the server sets after a status change (Completed sets `finished_on` to today) appear in the form. Any save changes `updated_at`, including ones unrelated to the form.

## Fix

Key the Form only on the persisted values it shows from the server, and keep Notes as a small `v-model` draft outside the keyed Form (`resources/js/pages/library/Show.vue`):

```vue
<!-- before -->
<Form :key="entry.updated_at" v-bind="update.form(entry.id)">
    <textarea name="notes" :value="entry.notes ?? ''" />

<!-- after -->
<script setup lang="ts">
const notes = ref(props.entry.notes ?? '');
</script>

<Form :key="`${entry.started_on}|${entry.finished_on}`" v-bind="update.form(entry.id)">
    <textarea name="notes" v-model="notes" />
```

A Rating change no longer remounts the Form. A status change that sets a date does remount it, so the server-set dates show, and typed Notes survive because the draft lives outside the key. Uncontrolled date inputs use the project's `<Input :default-value>` component, which handles the prop itself.

## Prevention

- For an uncontrolled native `<textarea>`/`<input>`, bind `:defaultValue` (camelCase) or use `v-model`; never `:value` if the field must keep what the user typed across re-renders.
- Key a form only on the values it displays, never on `updated_at` or anything an unrelated action changes.
- Browser check: type in every field, trigger a validation error, then use every "save on change" control on the page; the typed text must survive each. There is no JS test runner in this project, so this lives in the manual browser pass.

## Related

- See also: [Empty query parameters ignore request defaults](../logic-errors/2026-11-05-empty-query-param-ignores-request-default.md), another finding from the same review.
