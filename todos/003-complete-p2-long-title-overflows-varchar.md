---
status: complete
priority: p2
issue_id: '003'
tags: [code-review, data]
dependencies: []
title: 'OpenLibrary title and author name are stored in 255-character columns with no length guard'
location: database/migrations/2026_11_05_105708_create_books_table.php:17
---

# OpenLibrary title and author name are stored in 255-character columns with no length guard

## Problem

A reader who tries to add a book with a very long title or author name gets an error page and can never add that book.

books.title and authors.name are `string()` (VARCHAR(255)). AddBookToLibrary::importBook writes whatever OpenLibrary returns, and OpenLibrary::mapWork does not trim or limit those strings. OpenLibrary has works with very long titles (old works with long subtitles, collected editions). On MySQL in strict mode the INSERT fails with 'Data too long'. The transaction rolls back, so no data is corrupted, but the Reader gets a 500 error and can never add that Book. Tests run on SQLite, which does not enforce VARCHAR length, so they cannot catch this. Fix: change `title` to `text()` (and `name` to `string('name', 512)` or `text()`) in the create migrations, which is safe because the app is not deployed yet. Or limit the values in mapWork (`Str::limit($doc['title'], 255, '')`) and add a validation-style guard. Blast radius: every Reader who adds an affected work; no existing rows (new tables). Before deploy: none needed (new tables). After deploy: `SELECT MAX(CHAR_LENGTH(title)) FROM books; SELECT MAX(CHAR_LENGTH(name)) FROM authors;`. Rollback: migrate:rollback of the four new tables (nothing depends on them yet).

## Findings

- Location: `database/migrations/2026_11_05_105708_create_books_table.php:17`
- Evidence: `$table->string('title');` in the books migration and `$table->string('name');` in the authors migration; OpenLibrary::mapWork passes `'title' => $doc['title']` unchanged to Book::createOrFirst. CLAUDE.md: MySQL 8.4 locally, while tests and CI use SQLite :memory:, which ignores VARCHAR length.
- Found in: code review

## Acceptance Criteria

- [x] Adding a work whose title is 300+ characters succeeds on MySQL, either because the column holds it or because the value is limited before insert.
- [x] Tests cover it and pass

## Work Log

- 2026-11-05: books.title is now VARCHAR(1024) and authors.name VARCHAR(512). mapWork cuts the title and name to those lengths and skips work keys and author keys longer than 255. SQLite ignores VARCHAR length, so the tests prove the cut in PHP. The local MySQL schema needs `php artisan migrate:fresh`.
