---
title: "Cozy redesign: homepage, theme and app layout for teen Readers"
type: feat
date: 2026-11-05
source:
  - conversation
  - GLOSSARY.md
  - docs/brainstorms/2026-11-05-bookshelf-mvp-brainstorm.md
---

# feat: Cozy redesign: homepage, theme and app layout for teen Readers

## Problem
The MVP works but still looks like the starter kit it was built on. Two groups lose out:

- **Prospective Readers** land on `/` and see the stock Laravel Welcome page (`Welcome.vue`), which says nothing about BookShelf. Nobody signs up from it.
- **Existing Readers** get a grey, sidebar-driven interface that feels like an admin panel rather than a place they want to come back to.

Nothing is deployed yet, so now is the cheapest moment to set the visual identity, before real Readers form habits around the current UI.

Success: a teen who lands on the homepage understands what BookShelf is within about 5 seconds and wants to sign up, and the logged-in app feels like a cozy place (a neighbourhood bookshop or a reading corner at home) rather than a tool.

## What We're Building
A full visual redesign aimed at teens (roughly 13–19). It changes look, feel, layout and copy only: no new data, no new features beyond reorganising existing stats. A new theme (colours, type, components, motion) applies to every page. Bespoke layout work goes into the homepage, Dashboard, a new Stats page, Search and Library. Auth and settings pages inherit the theme, and the auth screens gain an illustration.

- A visitor can land on a public homepage that explains BookShelf, so that they know within seconds what it is and can sign up or log in. Sections, in order: **Hero** (headline, one-line pitch, Sign up / Log in, reading-nook illustration) → **How it works** in three steps (find a Book → add it to your Library → track Reading Status, Rating and Notes) → **A peek inside** (a styled mock of the Dashboard) → **closing call to action** → footer.
- A logged-in Reader who visits `/` goes straight to their Dashboard, so that the homepage never gets in their way.
- A Reader can move between **Dashboard · Search · Library · Stats** from a top bar on desktop and a bottom tab bar on mobile (avatar/user menu in a slim top bar), so that navigation feels like an app rather than an admin panel. This replaces the sidebar layout.
- A Reader sees a Dashboard "reading corner": a time-of-day greeting, their currently-Reading Books shown large, and a **stats summary**: tiles for Completed this year, Reading now, Want to Read and Completed all time, plus a small this-year-by-month sparkline and a "See all your stats →" link. The tiles sit in a grid that can take more tiles later without a layout change.
- A Reader can open a new **Stats** page holding the existing three charts (Completed Books per month with the year selector, top 10 Authors, count by Reading Status), restyled. Its layout is built to take more chart types later. No new chart types now.
- A Reader can browse their Library as **book covers on wooden shelf rows**, with title, Authors and Rating under each cover, so that their Library feels like a real shelf. Changing Reading Status and Rating from the list stays possible through a quick menu on each cover (tap on mobile), so no MVP behaviour is lost.
- A Reader sees Search results and the Library Entry page restyled in the same theme, with covers given prominence.
- A Reader can still choose light, dark or system appearance. Light mode is the reading nook; dark mode is the late-night study.

## Why This Approach
- **Two moods, one system**: light mode follows the *reading nook* direction (warm cream with terracotta, sage and mustard, rounded shapes, soft shadows, small hand-drawn doodles). Dark mode follows the *late-night study* direction (dusky plum and navy lit by lamplight amber). Both share type, layout, shapes and the wooden-shelf motif and differ only in palette and lighting. A *neighbourhood bookshop* direction (forest green, brick red) was considered and rejected as too grown-up for teens; only its wooden shelves and serif headings were kept.
- **Covers as the main imagery** inside the app: OpenLibrary covers already give every page colour and warmth at no cost.
- **Illustrations over photos**: stock bookshop photos look generic, clash with each other, raise licensing and attribution questions and add page weight. Illustrations and texture are more distinctive, suit teens, and can be made to work in both modes.
- **Public landing page at `/`** rather than a separate logged-in "home": the Dashboard already is the Reader's home, so `/` only needs to explain and convert.
- **Top bar and bottom tabs instead of a sidebar**: four destinations do not need a sidebar, and a top bar reads as a site or a shop rather than an admin tool.
- **Stats split off the Dashboard**: the Dashboard stays a glanceable summary while Stats gets room to grow into more graph types later, without the Dashboard getting crowded.
- **Cover shelves without a grid/list toggle**: the shelf is the app's signature view. Moving inline edits into a per-cover quick menu keeps the MVP behaviour without the extra scope of maintaining two views.

## Key Decisions & Tradeoffs
- **Teen targeting is presentation only**: look, feel and copy. No gamification, social features, age gate or teen-specific policies.
- **Theme scope is the whole app**: a half-cozy app looks broken. Bespoke layouts only for homepage, Dashboard, Stats, Search and Library; auth and settings inherit the theme.
- **Typography**: a soft, characterful serif for headings and a rounded, friendly sans for body text, the same in both modes.
- **Imagery**: book covers inside the app; simple in-house SVG decor (mug, lamp, plant, stacked books, shelf) for empty states, auth screens and accents; one homepage hero illustration treated as a swappable slot; no stock photography.
- **Dark mode kept** as a designed "evening" variant, not a grey inversion. Every colour and illustration must work in both modes.
- **Library stays the term**: `GLOSSARY.md` says to avoid "shelf"/"bookshelf" for the Library. Shelves are a visual motif only; copy keeps Library, Library Entry, Reading Status, Want to Read, Reading and Completed. "Stats" is a page name, not a new domain term.
- **Dashboard shows a summary only**: four stat tiles, a sparkline and a link. The full charts and the year selector move to Stats.
- **Stats page reuses existing data**: same three statistics as the MVP, restyled. New graph types belong to a later brainstorm.
- **Library quick menu**: Status and Rating editing from the list is preserved via a per-cover menu; dates and Notes stay on the Library Entry page.
- **Identity**: a new simple SVG logo mark (e.g. a book with a steam curl, or a book leaning on a mug), a serif "BookShelf" wordmark, and a matching favicon, replacing the starter kit's mark.
- **Copy voice**: warm, short, lightly witty ("Nothing on your nightstand yet. Find your next read →"). No teen slang. Emoji only in the odd empty state, never in buttons or navigation. Empty states, flash messages and Reader-facing errors are rewritten in this voice.
- **No fake social proof** on the homepage: no testimonials or user counts while there are no users.
- **Motion**: small and warm only (covers lift gently on hover, soft page fades, a small bounce when a Book is added), all disabled under `prefers-reduced-motion`.
- **Accessibility is a hard requirement**: WCAG 2.2 AA contrast in both modes, which matters most for terracotta and mustard on cream and for amber on dark.

## Risks / Open Questions
- **Hero illustration source**: a licensed or free illustration set restyled to the palette, or an image the user generates. Licensing must allow commercial use without attribution problems. Until it is settled, the hero ships with a placeholder that can be swapped.
- **Font loading**: Google Fonts versus self-hosted files. This affects privacy (third-party requests from a teen audience), performance, and how the fonts render offline.
- **Contrast of the warm palette**: terracotta and mustard text on cream can fall below AA; the palette may need darker text shades than the mood suggests.
- **Cover quality varies**: missing or low-resolution OpenLibrary covers will stand out more on shelf rows; the placeholder cover in `BookCover.vue` needs to look good at shelf size.
- **Quick menu on touch devices**: per-cover Status and Rating editing must stay easy and accessible (keyboard, screen reader, target size) without hover.

## Out of Scope
- New features: reading goals, streaks, badges, social features or sharing.
- New chart types or statistics on the Stats page.
- A grid/list toggle on the Library page.
- Stock photography.
- Any change to how data is stored, or to the catalogue, Reading Status rules or OpenLibrary integration.
- Age verification or teen-specific privacy policies.
- Translations / non-English interface.
