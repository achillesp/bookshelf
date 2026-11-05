---
title: "Use a shared catalogue of OpenLibrary works"
date: 2026-11-05
status: accepted
tags: [catalogue, openlibrary, domain-model, books, editions]
decision: "A Book is an OpenLibrary work shared by all Readers, identified by its OpenLibrary ID, with per-Reader data held in Library Entries"
context: "Readers import books from OpenLibrary, which separates works from their many editions, and the app is public so the same book is imported by many Readers"
consequences: "Reading counts once per work and the catalogue never duplicates, but edition data (ISBN, exact page count, exact cover) is not stored"
---

# ADR-001: Use a shared catalogue of OpenLibrary works

## Status
Accepted

## Context
BookShelf is a public app where Readers find books through the OpenLibrary API and add them to a private Library. OpenLibrary models a *work* (e.g. *Dune*) separately from its *editions* (dozens of printings, formats and translations, each with its own ISBN, page count and cover). Its search returns works.

Two questions had to be settled:

1. Should each Reader import a private copy of a book's metadata, or should books be shared?
2. Is a book a work or an edition?

Readers care that they read *Dune*, not which printing they held. Statistics (books completed per month, top Authors) need a stable identity per book and per author, which free-text titles and author names cannot give.

## Decision
- **Shared catalogue.** Books and Authors are shared records, imported once and identified by their OpenLibrary IDs. A Book can have several Authors. Everything personal (Reading Status, Rating, Notes, Started and Finished dates) lives on a Reader's **Library Entry**, at most one per Book.
- **A Book is a work, not an edition.** Only work-level data from the search result is stored: title, Authors, first published year, cover reference and the **median page count across editions**, shown as an approximation (e.g. "~412 pages").
- **ISBN is a search input, not stored data.** The search box accepts an ISBN, and OpenLibrary resolves it to the work it belongs to.
- **Catalogue data is not refreshed** after import.

Alternatives considered:

- *Per-Reader copies of metadata*: rejected; it duplicates data on a public app and gives author statistics no shared identity.
- *Edition-level Books (the Reader picks their edition)*: rejected for now. It adds an edition-picker step and an extra API request per add, and two editions of one work would have to be grouped back together for "one entry per Book" and author statistics.

## Consequences
- Easier: one Library Entry per work, simple statistics, no duplicate catalogue rows, and adding a book needs no extra API request while its search result is cached; otherwise one rate-limited lookup.
- Adding a book sends only its work key, so Readers never write catalogue metadata. The server imports the work from its cached search result (kept 24 hours); when that has expired, it looks the work up once by key, and that lookup counts toward the same OpenLibrary limits as a search. A Book already in the catalogue needs no lookup at all.
- Harder: Readers cannot record their exact edition's ISBN, page count or cover, and page counts are approximate. A "pages read" statistic would be approximate too.
- The catalogue is only as correct as OpenLibrary's work data at import time.

**Future option:** a Library Entry may *optionally* record which edition the Reader owns (ISBN, exact page count, edition cover). This adds to the model without changing it: Book stays the work, and the edition is extra detail on the Library Entry.
