<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import BookCover from '@/components/BookCover.vue';
import RatingInput from '@/components/RatingInput.vue';
import ReadingStatusSelect from '@/components/ReadingStatusSelect.vue';
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

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Library',
                href: index(),
            },
        ],
    },
});

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
    router.patch(update.url(entry.id), data, {
        preserveScroll: true,
        onStart: () => {
            saving.value = entry.id;
        },
        onFinish: () => {
            saving.value = null;
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

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <h1 class="sr-only">Library</h1>

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
            <p
                v-if="entries.total > 0"
                class="text-sm text-muted-foreground md:ml-auto"
                aria-live="polite"
            >
                {{ entries.total }}
                {{ entries.total === 1 ? 'Book' : 'Books' }}
            </p>
        </div>

        <div
            v-if="entries.data.length === 0"
            class="flex flex-col items-start gap-3 rounded-xl border border-dashed border-sidebar-border/70 p-6 dark:border-sidebar-border"
        >
            <p class="text-sm text-muted-foreground">
                {{
                    filters.status
                        ? `No Books with the status ${READING_STATUS_LABELS[filters.status]}.`
                        : 'Your Library is empty.'
                }}
            </p>
            <Button as-child variant="outline" size="sm">
                <Link :href="search()">
                    <Search />
                    Find books to add
                </Link>
            </Button>
        </div>

        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="entry in entries.data"
                :key="entry.id"
                class="flex gap-4 rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border"
            >
                <BookCover
                    :cover-id="entry.book.cover_id"
                    :title="entry.book.title"
                    class="w-16"
                />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <h2 class="font-medium">
                        <Link
                            :href="show(entry.id)"
                            class="hover:underline focus-visible:underline"
                        >
                            {{ entry.book.title }}
                        </Link>
                    </h2>
                    <p
                        v-if="entry.book.authors.length > 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            entry.book.authors
                                .map((author) => author.name)
                                .join(', ')
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="entry.book.page_count_median">
                            ~{{ entry.book.page_count_median }} pages
                        </span>
                        <span
                            v-if="
                                entry.book.page_count_median &&
                                entry.finished_on
                            "
                            aria-hidden="true"
                        >
                            ·
                        </span>
                        <span v-if="entry.finished_on">
                            Finished {{ formatDate(entry.finished_on) }}
                        </span>
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-3">
                        <ReadingStatusSelect
                            :model-value="entry.status"
                            :label="`Reading Status of ${entry.book.title}`"
                            :disabled="saving === entry.id"
                            @update:model-value="
                                save(entry, { status: $event })
                            "
                        />
                        <RatingInput
                            :model-value="entry.rating"
                            :label="`Rating of ${entry.book.title}`"
                            :disabled="saving === entry.id"
                            @update:model-value="
                                save(entry, { rating: $event })
                            "
                        />
                        <Button
                            variant="ghost"
                            size="icon"
                            class="ml-auto"
                            :aria-label="`Remove ${entry.book.title} from your Library`"
                            @click="entryToRemove = entry"
                        >
                            <Trash2 />
                        </Button>
                    </div>
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
            <DialogContent>
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
                        <Button variant="secondary">Cancel</Button>
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
