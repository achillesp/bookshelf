---
title: "feat: Cozy redesign with homepage, top-bar navigation and Stats page"
type: feat
date: 2026-11-05
branch: feat/cozy-redesign
---

# feat: Cozy redesign with homepage, top-bar navigation and Stats page

## Context

Brainstorm: `docs/brainstorms/2026-11-05-cozy-redesign-brainstorm.md` (WHY/WHAT); terms per `GLOSSARY.md`, ADR-001 constrains data only.

## Premises

- `user` (brainstorm, What We're Building): "A new theme (colours, type, components, motion) applies to every page. Bespoke layout work goes into the homepage, Dashboard, a new Stats page, Search and Library. Auth and settings pages inherit the theme, and the auth screens gain an illustration."
- `user` (brainstorm): Dashboard "tiles sit in a grid that can take more tiles later without a layout change"; Stats holds "the existing three charts … No new chart types now"; Library "Changing Reading Status and Rating from the list stays possible through a quick menu on each cover".
- `user` (brainstorm, Key Decisions): "Empty states, flash messages and Reader-facing errors are rewritten in this voice"; light mode "rounded shapes, soft shadows"; "WCAG 2.2 AA contrast in both modes"; motion "all disabled under `prefers-reduced-motion`"; "no stock photography"; hero "treated as a swappable slot".
- `user` (CLAUDE.md, Directory Structure): "`/app/Services/` - Business logic services"; (Conventions) "Do not change the application's dependencies without approval."
- `observed` (local, Playwright): `/dashboard`, `/library` render the starter sidebar shell, neutral palette, Instrument Sans; `/` is the stock Laravel page (`resources/js/pages/Welcome.vue`).
- `observed`: tokens are raw vars in `:root`/`.dark` mapped by `@theme inline` (`resources/css/app.css:10-62,92-163`); `--font-sans` set twice (`app.css:11,85`); dark is class-based (`app.css:8`).
- `observed`: html background hard-coded at `resources/views/app.blade.php:23-31`; progress colour at `resources/js/app.ts:34`; amber in `RatingInput.vue`; `sidebar-border` in 5 non-ui files (`grep -rl sidebar-border resources/js`).
- `observed`: fonts self-hosted at build via `bunny()` (`vite.config.ts:6,14-18`), printed by `@fonts` (`app.blade.php:37`); `curl fonts.bunny.net/css?family=fraunces|nunito` → `200` for both. No new dependency, no runtime third-party request.
- `observed`: layout resolver `resources/js/app.ts:12-23`; unused `layouts/app/AppHeaderLayout.vue`, `components/AppHeader.vue`, `layouts/auth/AuthSplitLayout.vue:19-30` (side panel).
- `observed`: stats queries are private methods in `app/Http/Controllers/DashboardController.php:43-112`; `year` validated by `app/Http/Requests/DashboardRequest.php`; tests `tests/Feature/DashboardTest.php`.
- `observed`: `vendor/laravel/framework/src/Illuminate/Auth/Middleware/RedirectIfAuthenticated.php:63-67` defaults to `route('dashboard')`, so `->middleware('guest')` on `/` redirects Readers with no config.
- `observed`: `.env.example:1` `APP_NAME=BookShelf`; `components/AppLogo.vue` reads `page.props.name` (local `.env` says "Bookshelf 2").
- `observed`: `components/ui/dropdown-menu` exists with radio groups; no popover component.
- `observed`: OpenLibrary cover `14627509` is 180×298 at `-M`, 301×500 at `-L` (curl + JPEG header), so `L` is sharp at ~150px shelf width on 2× screens.
- `observed`: draft palette contrast (WCAG formula, script in session): light fg/bg 12.6, muted 6.0, terracotta button 5.5, terracotta text 5.3, star 3.4 (non-text ≥3); dark fg/bg 13.7, muted 7.6, amber button 8.9; **mustard text on cream 1.7 → mustard is fill-only**.
- `observed`: `docs/solutions/ui-bugs/2026-11-05-inertia-form-loses-unsaved-input.md` (never key a Form on `updated_at`); `docs/solutions/logic-errors/2026-11-05-empty-query-param-ignores-request-default.md` (test empty `?year=`); commit 934a767 (sr-only chart table goes inside an sr-only div).
- `user` (plan confirmation, 2026-11-05): fonts "Fraunces + Nunito"; quick menu as "Menu with choices"; promote "Font preloading" and "Delete unused starter files".
- `inferred`: draft palette light cream `#FBF5EA`/brown `#3B2A20`/terracotta `#A8482A`/sage `#DDE6D3`/mustard `#E9B949`, dark plum `#1E1A2E`/cream `#F3E6D3`/amber `#F2B45A`. Proposals, approved in Task 1's review.
- `inferred`: Select-in-Popover nests two portals and needs extra focus handling (not probed; Option C is ruled out on concept count alone).
- `observed`: SSR is on (`config/inertia.php:19`), so a time-of-day greeting must be set after mount to use the browser's local time (no Reader timezone is stored).
- `inferred`: after `DashboardRequest` is removed, `/dashboard?year=2020` is a plain 200 (Laravel ignores unvalidated query params); Task 4's test asserts it.

**Readings.** "New theme on every page": minimal = tokens + fonts reach every page; maximal = bespoke layout everywhere. The brainstorm picks minimal-plus-five-bespoke pages. "WCAG 2.2 AA": minimal = token contrast; maximal = full audit. Same design either way; Verification does token ratios + Lighthouse a11y + keyboard pass per page. "Reduced motion": every animation, including Reka's tw-animate classes, so a global rule rather than per-class variants.

## Reuse inventory

- Data: existing props only. Dashboard summary = `statusCounts` + this year's per-month completed array (sum = Completed this year) + `reading`. No migrations.
- Code: token system; `bunny()` fonts; layout resolver; `AppHeader`/`AppHeaderLayout`, `UserMenuContent`, `useCurrentUrl`; `AuthSplitLayout`; `BookCover` (sizes, fallback); `ReadingStatusSelect`, `RatingInput` (Show page); ui dropdown-menu + dialog; Dashboard bar chart and sr-only table; `guest` middleware; Wayfinder.
- Palette, fonts, logo, copy. Person supplies: the user, at the HITL reviews in Tasks 1 and 6. Code derives: nothing; the draft values above are proposals.

## Design options

All options share: tokens rethemed in place, Fraunces/Nunito via `bunny()`, a new top-bar layout plus bottom tabs replacing the sidebar, the `guest` redirect on `/`, an in-house SVG decor set, and the split auth layout.

- **A (chosen):** stats queries move to `app/Services/ReadingStats.php`; invokable `StatsController` + `StatsRequest`; `MonthlyBarChart` component (full + compact); Library quick menu on the existing DropdownMenu (status radio group, rating radio group with "No Rating", "Remove…" opening the existing dialog). New concepts: 12 (Stats route+page, StatsController, ReadingStats, MonthlyBarChart, AppTopBarLayout, BottomTabBar, quick menu, decor SVG set, tokens `--font-display`/`--star`/`--wood`, global reduced-motion rule, guest redirect on `/`, time-of-day greeting).
- **B (no service):** `DashboardController` gains a `stats()` action next to `__invoke()`, sharing its private query methods; same front end as A. New concepts: 11. Ruled out by the `user` CLAUDE.md premise "`/app/Services/` - Business logic services" and the single-action controller convention (`DashboardController`, `SearchController`).
- **C (popover):** as complete as A, but the quick menu is a new ui Popover wrapping `ReadingStatusSelect` + `RatingInput`. New concepts: 13 (adds the popover ui component). Ruled out: more concepts than A for the same need.

## Approach

- **Theme**: rewrite `app.css:92-163` with the approved palette; add `--font-display`, `--star`, `--wood` to `@theme inline`; one `--font-sans`; larger `--radius`; global reduced-motion rule (0.01ms keeps Reka's animationend). Sync `app.blade.php:23-31`, `app.ts:34`; `RatingInput` amber → `--star`; `sidebar-border` → `border`; mustard never as text.
- **Fonts**: `vite.config.ts:14-18` swaps Instrument Sans for `bunny('Nunito')` + `bunny('Fraunces')`, using the `preload` option for the above-the-fold weights only.
- **Cleanup**: delete unused `components/ui/sidebar`, `layouts/auth/AuthCardLayout.vue`, `layouts/auth/AuthSimpleLayout.vue` once nothing imports them.
- **Identity**: new `AppLogoIcon.vue` (book + steam curl, `currentColor`); `AppLogo.vue` serif wordmark from `page.props.name`; replace `public/favicon.svg`, `favicon.ico`, `apple-touch-icon.png`.
- **Stats**: `ReadingStats` takes `statusCounts`, `completedByYear`, `topAuthors` from `DashboardController.php:43-112`. `GET /stats` → `StatsController` → `stats/Index.vue`. Task 2 adds `StatsRequest` beside `DashboardRequest` and leaves the Dashboard year selector working; Task 4 deletes `DashboardRequest` and the Dashboard year props and moves the remaining year tests to `tests/Feature/StatsTest.php`. `/stats` joins the `auth`+`verified` group (`routes/web.php:10`); `ReadingStats` only receives `$request->user()`. `StatsTest.php` covers guest and unverified redirects.
- **Nav**: rename the unused `layouts/app/AppHeaderLayout.vue` to `AppTopBarLayout.vue` (keeps Toaster, gains `BottomTabBar`, drops the breadcrumbs prop); `AppHeader.vue` with 4 items and the avatar menu (starter links and dead search removed); `components/BottomTabBar.vue` (`md:hidden`, ≥44px, `aria-current`). `AppLayout.vue` points to it. Remove page breadcrumbs, `AppSidebar*`, `NavMain`, `NavFooter`, `Breadcrumbs`, `sidebarOpen` (`HandleInertiaRequests.php:44`, `types/global.d.ts:22`), `sidebar_state` (`bootstrap/app.php:18`).
- **Nav shell**: `AppShell.vue` and `AppContent.vue` keep only the header shape (drop the `variant` prop, the `AppVariant` type in `types/ui.ts` and the `sidebarOpen` read); delete `NavUser.vue`. `components/ui/sidebar` goes only once `grep -rl sidebar resources/js` finds no import outside it.
- **Dashboard**: greeting (neutral on the server render, time of day set after mount, since SSR is on per `config/inertia.php:19`), Reading covers (`L`), tile grid, compact chart, "See all your stats →".
- **Library**: `library/Index.vue` cover grid (`L`) on wood-plank rows; quick menu items call the existing `save()` (`router.patch`, no keyed Form); filters, pagination, remove dialog kept.
- **Search / Show**: restyle; `Show.vue` Notes behaviour untouched.
- **Homepage**: `routes/web.php:8` adds `->middleware('guest')`; `Welcome.vue` rebuilt with Hero (SVG nook placeholder), How it works, Peek inside (static mock, no numbers claimed as real), CTA, footer.
- **Auth**: `AuthLayout.vue` → `AuthSplitLayout` with decor illustration.
- **Copy**: each slice rewrites its own empty states; Library flashes in `LibraryEntryController.php:59-106` and the search errors in `app/Services/OpenLibraryException.php:13-25` (Task 5), updating the literal assertions in `SearchTest.php` and `Library/StoreLibraryEntryTest.php`.

## Acceptance Criteria

- [ ] Every page uses the approved palette and fonts in light and dark, with no flash of the old background
- [ ] Guests see the new homepage at `/`; signed-in Readers visiting `/` land on the Dashboard
- [ ] Top bar on desktop, bottom tabs on mobile: Dashboard · Search · Library · Stats; no sidebar
- [ ] Stats shows the three existing statistics with the year selector; Dashboard shows greeting, Reading covers, 4 tiles, sparkline, link
- [ ] Library shows covers on shelf rows; Reading Status, Rating and Remove work per cover by mouse, touch and keyboard
- [ ] New logo, wordmark and favicon; no Laravel mark remains
- [ ] AA contrast for all text and UI pairs; no animation under `prefers-reduced-motion: reduce`; copy uses glossary terms and the agreed voice
- [ ] `composer ci:check` green

## Task Breakdown

- [x] **Visual identity: theme, fonts, logo and favicon** (HITL: palette, font and logo review)
  - AC: all pages render the approved palette and Fraunces/Nunito in both modes; html background matches on first paint; contrast script passes AA for every pair; no animation under reduced motion; `<link rel="preload">` only for above-the-fold weights; new logo in app and auth screens and new favicon in the tab; no Laravel mark in `resources/js` or `public/`
  - Blocked by: none
- [x] **Stats page backed by ReadingStats** (AFK)
  - AC: queries live in `ReadingStats` and `DashboardTest.php` still passes before the page is added; `/stats` shows status counts, month chart with year select, top Authors; `StatsTest.php` covers year, years, empty `?year=`, rejected years, top Authors, guest and unverified redirects; the nav link arrives with Task 3
  - Blocked by: Task 1
- [x] **Top bar and mobile bottom tabs replace the sidebar** (AFK)
  - AC: Dashboard · Search · Library · Stats with active state at 1280px and 375px; avatar menu reaches Settings and Log out; no sidebar renders and `components/ui/sidebar` is gone; toasts still show
  - Blocked by: Task 2
- [x] **Dashboard reading corner with stats summary** (AFK)
  - AC: greeting, Reading covers, 4 tiles, sparkline with sr-only table, link to Stats; new-voice empty states; `DashboardTest.php` asserts summary props and that `?year=2020` is a 200
  - Blocked by: Task 2
- [ ] **Library shelves, quick menu, Search and Library Entry pages** (AFK)
  - AC: Library covers on shelf rows; Status, Rating and Remove work from each cover's menu by keyboard and touch; filters, sort, pagination unchanged; Search and Library Entry restyled with covers prominent; typing Notes then changing Status keeps the typed text; flashes reworded; Library and Search tests green
  - Blocked by: Task 1
- [ ] **Public homepage with guest-only route** (HITL: copy and layout review)
  - AC: guest sees Hero, How it works, Peek inside, CTA, footer at 1280px and 375px; signed-in `/` redirects to `/dashboard` (`ExampleTest.php`)
  - Blocked by: Task 1
- [ ] **Auth screens with illustration** (AFK)
  - AC: login, register, forgot and reset use the split layout with illustration in both modes; `AuthCardLayout.vue` and `AuthSimpleLayout.vue` gone; auth tests green
  - Blocked by: Task 1
- [x] **Tests do not depend on the dev server's SSR** (AFK, added during Task 5)
  - AC: `config/inertia.php` reads `INERTIA_SSR_ENABLED` (default `true`); `phpunit.xml` sets it to `false`; `SearchTest.php` green while `composer dev` runs
  - Blocked by: none

## Verification

- Seam: Pest feature tests with `assertInertia` (copy `tests/Feature/DashboardTest.php`); new `tests/Feature/StatsTest.php`; extend `tests/Feature/ExampleTest.php`.
- `php artisan test --compact`; `composer ci:check`.
- Browser (Playwright), 1280px and 375px, light and dark: `/`, login, `/dashboard`, `/stats`, `/library` (Reader with entries), `/library/{id}`, `/search`, settings.
- Contrast script over every token pair in both modes; Lighthouse a11y audit per page; keyboard-only pass through top bar, bottom tabs, quick menu, year select.
- Emulate `prefers-reduced-motion: reduce`; confirm no animation.
- Risk row 2: on `/library` and `/search`, count fallback covers and check for blurry covers at 2× zoom.

## Not in MVP

| # | Kind | Item | Detail |
| --- | --- | --- | --- |
| 1 | extra | Sourced hero illustration | Swap the SVG placeholder for a licensed or user-made illustration; 0 concepts; waits on an external asset |
| 2 | risk (`inferred`) | Many missing covers make shelves look patchy | Trigger: fallback covers dominate the browser pass → style the fallback as a cloth-bound cover with the title |
| 3 | extra | Font preloading | promoted (Task 1) |
| 4 | extra | Dark-scheme favicon variant | Favicon adapts to the OS theme; 0 concepts; no requirement |
| 5 | extra | Delete unused `ui/sidebar`, `AuthCardLayout`, `AuthSimpleLayout` | promoted (Tasks 3, 7) |

## Deviations

- Task 1 (HITL, 2026-11-05): the user approved the draft palette, Fraunces + Nunito and the open-book-with-steam logo as proposed, and chose the shelf wood from a set of variations: birch `#DDBF8A` / edge `#B8935B` in light, mahogany `#723124` / edge `#4A1D15` in dark (tokens `--wood`, `--wood-edge`).
- Task 1: `AppLogoIcon.vue` already existed (the starter's Laravel mark) and was rewritten, not created. The stock `Welcome.vue` still carries the Laravel mark until Task 6 replaces the page.
- Task 1: Inertia's progress bar uses `var(--primary)` instead of a fixed hex, so it follows the mode.
- Task 2 (Premise Break, patched with the user): email verification is off (`User` does not implement `MustVerifyEmail`, no `verification.notice` route), so `verified` lets every signed-in Reader through and an "unverified redirect" test cannot exist. `/stats` stays in the `auth`+`verified` group like the Dashboard; `StatsTest.php` covers the guest redirect only.
- Task 3: the four nav items live in `resources/js/lib/mainNav.ts`, shared by `AppHeader` and `BottomTabBar`. `AppContent` now centres every page at `max-w-6xl`, and the unused `--sidebar-*` tokens were removed. The Toaster's bottom offset follows a `--toast-bottom` variable so toasts clear the bottom tabs up to `md`. The starter's unused `Breadcrumbs.vue` went with the sidebar; `library/Show.vue` lost its breadcrumbs (Task 5 restyles it).
- Task 2: the Dashboard's inline bar chart was swapped for `MonthlyBarChart` in this task rather than Task 4, so only one copy of the chart exists.
- New task (agreed with the user during Task 5): with the Vite dev server running, `public/hot` makes Inertia call `/__inertia_ssr` on the dev server, and `Http::preventStrayRequests` failed all 11 HTTP-faking `SearchTest` cases, before this branch too. SSR is now off in tests through `INERTIA_SSR_ENABLED`.

## Risks & Dependencies

- `observed`, costly to redo: mustard fails as text (1.7:1). Task 1 is HITL and the contrast script gates it before later slices build on the tokens.
- `inferred`, breaking (internal only): Dashboard props change and `year` moves to `/stats`; Task 4 asserts old `?year=` links still load.
