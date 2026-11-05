---
status: complete
priority: p3
issue_id: '007'
tags: [code-review, quality]
dependencies: []
title: Homepage and search-error copy use words the glossary avoids
location: resources/js/pages/Welcome.vue:15
---

# Homepage and search-error copy use words the glossary avoids

## Problem

A few lines of visitor-facing text call books "titles" or write "book" in lowercase, which breaks the agreed vocabulary that the rest of the new wording follows.

`GLOSSARY.md` lists "Title" as a word to avoid for Book, yet the How it works step says "Search millions of titles by name, Author or ISBN." The rewritten OpenLibrary errors say "book catalogue" with a lowercase "book", while the rest of the new copy writes the term as "Book".

## Findings

- Location: `resources/js/pages/Welcome.vue:15`, `app/Services/OpenLibraryException.php:15`, `app/Services/OpenLibraryException.php:25`
- Evidence: "Search millions of titles by name, Author or ISBN."; "We can't reach the book catalogue right now…"; "The book catalogue is busy right now…"
- Found in: code review

## Acceptance Criteria

- [x] No new Reader-facing copy uses a word GLOSSARY.md lists as one to avoid, and the domain term is always written as "Book"/"Books".
- [x] The literal assertions in `SearchTest.php` and `Library/StoreLibraryEntryTest.php` match the reworded messages and pass.
