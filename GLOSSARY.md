# BookShelf

A personal library where readers find books, keep them in their own library, and track their reading.

## Catalogue

**Book**:
A literary work as identified by OpenLibrary, shared by all readers. It represents the work, not a particular edition or printing.
_Avoid_: Title, work, volume, copy

**Edition**:
One specific published form of a Book (a printing, format or translation), with its own ISBN and page count. Editions are not part of the catalogue.
_Avoid_: Book, version, printing

**Author**:
A person credited with writing one or more Books, as identified by OpenLibrary. A Book may have several Authors.
_Avoid_: Writer, creator

## Reading

**Reader**:
A registered user of BookShelf.
_Avoid_: Member, account, customer

**Library**:
The private collection of Library Entries belonging to one Reader.
_Avoid_: Collection, bookshelf, shelf

**Library Entry**:
A Reader's personal record of one Book in their Library, holding its Reading Status, Rating and Notes. A Reader has at most one Library Entry per Book.
_Avoid_: Copy, user book, shelf item

**Reading Status**:
Where a Library Entry stands: Want to Read, Reading, or Completed.
_Avoid_: State, progress, shelf

**Started Date**:
The day a Reader began reading the Book of a Library Entry.
_Avoid_: Start time, began

**Finished Date**:
The day a Reader completed the Book of a Library Entry; it places the Book in a month for reading statistics.
_Avoid_: Completed at, end date, read date

**Rating**:
A Reader's optional score for a Book in their Library, in whole stars from 1 to 5.
_Avoid_: Score, review

**Notes**:
A Reader's private free-text remarks on a Library Entry.
_Avoid_: Review, comment, journal
