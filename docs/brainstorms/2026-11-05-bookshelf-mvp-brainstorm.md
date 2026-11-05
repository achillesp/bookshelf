---
title: 'BookShelf MVP: library, reading tracking and stats'
type: feat
date: 2026-11-05
source:
    - conversation
    - GLOSSARY.md
    - docs/adrs/ADR-001-shared-catalogue-of-openlibrary-works.md
---

# feat: BookShelf MVP: library, reading tracking and stats

## Problem

Readers lose track of what they have read and when. Spreadsheets have no metadata or covers, and large services such as Goodreads feel bloated. The core pain is **remembering what was read and when**; statistics come out of that data, and search/import exists only so adding a book is quick and needs no typing of metadata.

BookShelf is a public app (anyone can register), built both as a real tool and as a learning project for the Laravel 13 / Inertia v3 / Vue 3 stack. The aim is a solid, well-tested MVP of exactly three features, nothing more.

Success: a Reader can find a book and add it in under 10 seconds, move it from Want to Read → Reading → Completed, rate it and write notes, and see a dashboard that answers "how many books did I finish each month this year, which Authors do I read most, and what's on my pile?"

## What We're Building

A public, multi-Reader web app on the existing Vue starter kit (Fortify auth, no email verification). Books and Authors form a shared catalogue imported from OpenLibrary; each Reader's Library is private. Three main areas: **Dashboard** (the starter kit's page, turned into currently-reading plus stats), **Search** and **Library**. Terms follow `GLOSSARY.md`.

- A Reader can search OpenLibrary by title, author or ISBN in a single box and see about 20 results with cover, title, Authors and first published year, so that they can find a book quickly.
- A Reader can add a result to their Library with one click, choosing the starting Reading Status (Want to Read by default), so that adding takes seconds. Results already in their Library are marked and cannot be added twice.
- A Reader can change a Library Entry's Reading Status in any direction between Want to Read, Reading and Completed, so that their Library reflects reality. Started and Finished dates fill in automatically:
    - → Reading: Started Date set to today if empty, Finished Date cleared.
    - → Completed: Finished Date set to today, Started Date too if empty.
    - → Want to Read: both dates cleared.
- A Reader can edit Started and Finished dates, so that books read in the past can be recorded (e.g. add _Dune_ as Completed, Finished March 2019). Finished cannot be before Started; neither can be in the future.
- A Reader can give an optional Rating (whole stars, 1–5) in any status and write free-text Notes, so that they remember what they thought.
- A Reader can browse their Library filtered by Reading Status, sorted by recently updated, title or Finished Date, paginated, and change Status and Rating directly from the list. Dates and Notes are edited on the entry's own page.
- A Reader can remove a Library Entry after confirming; its Rating, Notes and dates are permanently deleted.
- A Reader can see on the Dashboard:
    - their currently Reading books;
    - **by month**: a bar chart of Completed books per month by Finished Date, for a chosen year (default this year; selector lists years with completed books);
    - **by author**: top 10 Authors by number of Completed Books, all time (a co-written Book counts for each of its Authors);
    - **by status**: count of Library Entries in each Reading Status.

## Why This Approach

- **Shared catalogue of OpenLibrary works** (see ADR-001) over per-Reader copies or edition-level Books: on a public app it avoids duplicating data and imports, gives Authors a stable identity for stats, and counts a read once per work regardless of edition. Edition-level picking was rejected as extra steps, extra API calls and grouping complexity for little MVP value.
- **Search goes through our server** rather than the browser calling OpenLibrary directly: we can cache results briefly, rate-limit per Reader, and identify the app to OpenLibrary as its usage policy asks, which matters for a public app.
- **Dashboard becomes the stats page** rather than adding a separate Stats page: fewer pages, and "what am I reading now" belongs next to "what have I read".
- **One Library Entry per Book** (no re-read history): keeps the model and stats simple; re-read sessions can be added later.

## Key Decisions & Tradeoffs

- **Book = OpenLibrary work, shared catalogue of Books and Authors identified by OpenLibrary IDs**: see [ADR-001](../adrs/ADR-001-shared-catalogue-of-openlibrary-works.md).
- **Stored Book data**: title, Authors, first published year, cover reference and approximate page count (median across editions, shown as "~412 pages"), all taken from the search result so import needs no extra request. Never refreshed after import. Page count is display-only in the MVP.
- **ISBN is a search input, not stored**: OpenLibrary resolves an ISBN to its work.
- **Authors are shared records**; a Book may have several, and stats count a co-written Book for each.
- **Exactly three Reading Statuses**, free movement in any direction; no Did Not Finish. A dropped book goes back to Want to Read or is removed.
- **No re-read history**: at most one Library Entry per Book per Reader; re-reading overwrites dates.
- **Rating**: optional, whole stars 1–5, allowed in any status. **Notes**: one plain-text field per Library Entry.
- **Dates auto-fill on status change and are editable**, with Finished ≥ Started and no future dates. Finished Date alone places a Book in a month for stats.
- **Stats count Completed Books only** for month and author; Want to Read would measure intent, not reading.
- **Everything personal is private**; only the Book/Author catalogue is shared. No public profiles or social features.
- **OpenLibrary only**: no manual book entry, to avoid user-created data in a shared catalogue.
- **Covers load directly from OpenLibrary's cover service**; not stored locally.
- **If OpenLibrary is down**, the Library and Dashboard keep working; only search and adding new books stop.
- **Removal is permanent** (no undo/archive). Account deletion (already in the starter kit) removes the Reader's whole Library. Books and Authors no Reader uses any more stay in the catalogue.
- **No email verification** (disabled at setup); per-Reader rate limiting on search covers the abuse risk for now.
- **English-only interface**; book data appears in whatever language OpenLibrary returns.

## Risks / Open Questions

- OpenLibrary field names and availability (e.g. median page count, author IDs in search results, ISBN queries) need confirming against the live API during planning.
- OpenLibrary availability and rate limits under public use: cache duration and per-Reader search limits need concrete values in the plan.
- Without email verification, throwaway accounts could still consume OpenLibrary capacity; revisit if abuse appears.

## Out of Scope

- Editions: recording which edition a Reader owns (ISBN, exact page count, edition cover). Planned as a possible future addition on the Library Entry; see ADR-001.
- Re-read history (multiple reading sessions per Book).
- Did Not Finish / Abandoned status.
- Pages-read statistics, average rating, reading speed or other extra stats.
- Manual book entry or editing catalogue data; refreshing Book metadata after import.
- Book descriptions, subjects and advanced search filters.
- Social features: public profiles, sharing, following.
- Email verification (may be added later).
- Translations / non-English interface.
- Undo or archive for removed entries.
