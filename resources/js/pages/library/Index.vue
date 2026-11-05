<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Search, Star } from '@lucide/vue';
import { nextTick, ref } from 'vue';
import BookCover from '@/components/BookCover.vue';
import LibraryEntryMenu from '@/components/LibraryEntryMenu.vue';
import ReadingStatusBadge from '@/components/ReadingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { search } from '@/routes';
import { destroy, index, show, update } from '@/routes/library';
import { READING_STATUS_LABELS } from '@/types';
import type { LibraryEntry, Paginated, ReadingStatus } from '@/types';

type LibrarySort = 'updated' | 'title' | 'finished';

const props = defineProps<{
    entries: Paginated<LibraryEntry>;
    filters: { status: ReadingStatus | null; sort: LibrarySort };
}>();

const ALL_STATUSES = 'all';

const statusOptions = [
    { value: ALL_STATUSES, text: 'All' },
    ...Object.entries(READING_STATUS_LABELS).map(([value, text]) => ({
        value,
        text,
    })),
];

const sortOptions: { value: LibrarySort; text: string }[] = [
    { value: 'updated', text: 'Recently updated' },
    { value: 'title', text: 'Title' },
    { value: 'finished', text: 'Finished Date' },
];

const saving = ref<number | null>(null);
const entryToRemove = ref<LibraryEntry | null>(null);
const removing = ref(false);
const positionToRefocus = ref<number | null>(null);

function visitWith(filters: {
    status: ReadingStatus | null;
    sort: LibrarySort;
}): void {
    router.get(
        index.url({
            query: {
                status: filters.status ?? undefined,
                sort: filters.sort === 'updated' ? undefined : filters.sort,
            },
        }),
        {},
        { preserveState: true },
    );
}

function isReadingStatus(value: unknown): value is ReadingStatus {
    return typeof value === 'string' && value in READING_STATUS_LABELS;
}

function isLibrarySort(value: unknown): value is LibrarySort {
    return sortOptions.some((option) => option.value === value);
}

function filterByStatus(value: unknown): void {
    visitWith({
        status: isReadingStatus(value) ? value : null,
        sort: props.filters.sort,
    });
}

function sortBy(value: unknown): void {
    if (isLibrarySort(value)) {
        visitWith({ status: props.filters.status, sort: value });
    }
}

function save(
    entry: LibraryEntry,
    data: { status: ReadingStatus } | { rating: number | null },
): void {
    const position = positionOf(entry);

    router.patch(update.url(entry.id), data, {
        preserveScroll: true,
        onStart: () => {
            saving.value = entry.id;
        },
        onFinish: () => {
            saving.value = null;
            nextTick(() => {
                if (document.activeElement === document.body) {
                    focusMenuOrNeighbour(position);
                }
            });
        },
    });
}

function remove(): void {
    if (entryToRemove.value === null) {
        return;
    }

    router.delete(destroy.url(entryToRemove.value.id), {
        preserveScroll: true,
        onStart: () => {
            removing.value = true;
        },
        onFinish: () => {
            removing.value = false;
            entryToRemove.value = null;
        },
    });
}

function authorNames(entry: LibraryEntry): string {
    return entry.book.authors.map((author) => author.name).join(', ');
}

function positionOf(entry: LibraryEntry): number {
    return props.entries.data.findIndex((item) => item.id === entry.id);
}

/**
 * Keeps the Reader's place on the shelf: focus goes to the menu button of the
 * Book at the position the changed Book had. That is the same Book when it
 * stayed put, and the one now in its place when it moved (the default sort puts
 * it first), left the filter or page, or was removed. The heading is the
 * fallback only for an empty shelf.
 */
function focusMenuOrNeighbour(position: number): void {
    const entries = props.entries.data;
    const inPlace = entries[Math.min(position, entries.length - 1)];
    const target =
        (inPlace
            ? document.getElementById(`entry-menu-${inPlace.id}`)
            : null) ?? document.getElementById('library-heading');

    target?.focus();
}

/**
 * The remove dialog was opened from a menu item that no longer exists, so focus
 * goes back to that Book's menu button, or to the Book now in its place.
 */
function restoreFocus(event: Event): void {
    event.preventDefault();

    if (positionToRefocus.value !== null) {
        focusMenuOrNeighbour(positionToRefocus.value);
    }
}

function confirmRemove(entry: LibraryEntry): void {
    positionToRefocus.value = positionOf(entry);
    entryToRemove.value = entry;
}

function formatDate(date: string): string {
    return new Date(`${date}T00:00:00`).toLocaleDateString(undefined, {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
}
</script>

<template>
    <Head title="Library" />

    <div class="flex flex-col gap-8 px-4 py-8">
        <header class="flex flex-wrap items-end justify-between gap-6">
            <div class="flex flex-col gap-1">
                <h1
                    id="library-heading"
                    tabindex="-1"
                    class="text-3xl font-bold tracking-tight focus:outline-none"
                >
                    Your Library
                </h1>
                <p
                    v-if="entries.total > 0"
                    class="text-muted-foreground"
                    aria-live="polite"
                >
                    {{ entries.total }}
                    {{ entries.total === 1 ? 'Book' : 'Books' }}
                    {{
                        filters.status
                            ? `marked ${READING_STATUS_LABELS[filters.status]}`
                            : 'in your Library'
                    }}
                </p>
            </div>
            <div class="flex flex-wrap items-end gap-4">
                <div class="grid gap-2">
                    <Label for="status-filter">Reading Status</Label>
                    <Select
                        :model-value="filters.status ?? ALL_STATUSES"
                        @update:model-value="filterByStatus"
                    >
                        <SelectTrigger id="status-filter" class="w-40">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in statusOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.text }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div class="grid gap-2">
                    <Label for="sort">Sort by</Label>
                    <Select
                        :model-value="filters.sort"
                        @update:model-value="sortBy"
                    >
                        <SelectTrigger id="sort" class="w-44">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in sortOptions"
                                :key="option.value"
                                :value="option.value"
                            >
                                {{ option.text }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>
        </header>

        <div
            v-if="entries.data.length === 0"
            class="flex flex-col items-start gap-4 rounded-2xl border border-dashed bg-card p-6"
        >
            <p class="text-muted-foreground">
                {{
                    filters.status
                        ? `Nothing marked ${READING_STATUS_LABELS[filters.status]} right now.`
                        : 'Your Library is waiting for its first Book.'
                }}
            </p>
            <Button as-child>
                <Link :href="search()">
                    <Search />
                    Find Books to add
                </Link>
            </Button>
        </div>

        <ul
            v-else
            class="grid grid-cols-2 gap-y-10 overflow-x-clip sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5"
        >
            <li
                v-for="entry in entries.data"
                :key="entry.id"
                class="group/book flex flex-col"
            >
                <div class="relative px-3 pt-4 sm:px-5">
                    <Link
                        :href="show(entry.id)"
                        tabindex="-1"
                        aria-hidden="true"
                        class="block transition-transform duration-200 group-hover/book:-translate-y-1"
                    >
                        <BookCover
                            :cover-id="entry.book.cover_id"
                            :title="entry.book.title"
                            size="L"
                            class="w-full rounded-l-sm rounded-r-md shadow-md"
                        />
                    </Link>
                    <div class="absolute top-2 right-1 sm:right-3">
                        <LibraryEntryMenu
                            :entry="entry"
                            :saving="saving === entry.id"
                            @status="save(entry, { status: $event })"
                            @rating="save(entry, { rating: $event })"
                            @remove="confirmRemove(entry)"
                        />
                    </div>
                </div>
                <div
                    aria-hidden="true"
                    class="h-3.5 bg-linear-to-b from-wood from-60% to-wood-edge shadow-[0_10px_12px_-10px_rgb(40_20_5/0.6)] group-last/book:-mr-[100vw]"
                />
                <div class="flex flex-col gap-1.5 px-3 pt-4 sm:px-5">
                    <h2 class="line-clamp-2 leading-snug font-bold">
                        <Link
                            :href="show(entry.id)"
                            class="rounded-sm hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                        >
                            {{ entry.book.title }}
                        </Link>
                    </h2>
                    <p
                        v-if="entry.book.authors.length > 0"
                        class="line-clamp-1 text-sm text-muted-foreground"
                    >
                        {{ authorNames(entry) }}
                    </p>
                    <div class="flex flex-wrap items-center gap-2">
                        <ReadingStatusBadge :status="entry.status" />
                        <span
                            v-if="entry.rating !== null"
                            class="flex items-center gap-0.5"
                        >
                            <span class="sr-only">
                                Rated {{ entry.rating }} out of 5
                            </span>
                            <Star
                                v-for="star in entry.rating"
                                :key="star"
                                aria-hidden="true"
                                class="size-3.5 fill-star text-star"
                            />
                        </span>
                    </div>
                    <p
                        v-if="entry.finished_on"
                        class="text-xs text-muted-foreground"
                    >
                        Finished {{ formatDate(entry.finished_on) }}
                    </p>
                </div>
            </li>
        </ul>

        <nav
            v-if="entries.last_page > 1"
            aria-label="Library pages"
            class="flex items-center justify-between gap-4"
        >
            <Button
                v-if="entries.prev_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="entries.prev_page_url" preserve-state>
                    <ChevronLeft />
                    Previous
                </Link>
            </Button>
            <span v-else />
            <span class="text-sm text-muted-foreground">
                Page {{ entries.current_page }} of {{ entries.last_page }}
            </span>
            <Button
                v-if="entries.next_page_url"
                as-child
                variant="outline"
                size="sm"
            >
                <Link :href="entries.next_page_url" preserve-state>
                    Next
                    <ChevronRight />
                </Link>
            </Button>
            <span v-else />
        </nav>

        <Dialog
            :open="entryToRemove !== null"
            @update:open="
                (open: boolean) => {
                    if (!open && !removing) entryToRemove = null;
                }
            "
        >
            <DialogContent @close-auto-focus="restoreFocus">
                <DialogHeader>
                    <DialogTitle>
                        Remove “{{ entryToRemove?.book.title }}”?
                    </DialogTitle>
                    <DialogDescription>
                        Its Reading Status, Rating, Notes and dates will be
                        permanently deleted.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Keep it</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        :disabled="removing"
                        @click="remove"
                    >
                        Remove
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
