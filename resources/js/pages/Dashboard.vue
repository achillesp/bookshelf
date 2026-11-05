<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { computed } from 'vue';
import BookCover from '@/components/BookCover.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { dashboard, search } from '@/routes';
import { index as library, show } from '@/routes/library';
import { READING_STATUS, READING_STATUS_LABELS } from '@/types';
import type { LibraryEntry, ReadingStatus } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});

const props = defineProps<{
    reading: LibraryEntry[];
    statusCounts: Record<ReadingStatus, number>;
    year: number;
    years: number[];
    completedPerMonth: number[];
    topAuthors: { id: number; name: string; completed_books: number }[];
}>();

const MONTHS = [
    'Jan',
    'Feb',
    'Mar',
    'Apr',
    'May',
    'Jun',
    'Jul',
    'Aug',
    'Sep',
    'Oct',
    'Nov',
    'Dec',
];

const statuses = Object.values(READING_STATUS).map((status) => ({
    label: READING_STATUS_LABELS[status],
    count: props.statusCounts[status],
    href: library({ query: { status } }),
}));

const completedInYear = computed(() =>
    props.completedPerMonth.reduce((total, count) => total + count, 0),
);

const chartMax = computed(() => Math.max(1, ...props.completedPerMonth));

function showYear(value: unknown): void {
    router.get(
        dashboard.url({ query: { year: String(value) } }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <h1 class="sr-only">Dashboard</h1>

        <section aria-labelledby="status-heading" class="grid gap-3">
            <h2 id="status-heading" class="sr-only">
                Library by Reading Status
            </h2>
            <ul class="grid gap-4 sm:grid-cols-3">
                <li v-for="status in statuses" :key="status.label">
                    <Link
                        :href="status.href"
                        class="flex flex-col gap-1 rounded-xl border border-border p-4 hover:bg-accent focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span class="text-sm text-muted-foreground">
                            {{ status.label }}
                        </span>
                        <span class="text-3xl font-semibold tabular-nums">
                            {{ status.count }}
                        </span>
                    </Link>
                </li>
            </ul>
        </section>

        <section
            aria-labelledby="reading-heading"
            class="flex flex-col gap-3 rounded-xl border border-border p-4"
        >
            <h2 id="reading-heading" class="font-medium">Currently Reading</h2>
            <ul
                v-if="reading.length > 0"
                class="grid grid-cols-[repeat(auto-fill,minmax(7rem,1fr))] gap-4"
            >
                <li v-for="entry in reading" :key="entry.id">
                    <Link
                        :href="show(entry.id)"
                        class="flex flex-col gap-2 rounded-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <BookCover
                            :cover-id="entry.book.cover_id"
                            :title="entry.book.title"
                            class="w-full"
                        />
                        <span class="line-clamp-2 text-sm font-medium">
                            {{ entry.book.title }}
                        </span>
                        <span
                            v-if="entry.book.authors.length > 0"
                            class="line-clamp-1 text-xs text-muted-foreground"
                        >
                            {{
                                entry.book.authors
                                    .map((author) => author.name)
                                    .join(', ')
                            }}
                        </span>
                    </Link>
                </li>
            </ul>
            <div v-else class="flex flex-col items-start gap-3">
                <p class="text-sm text-muted-foreground">
                    Nothing on the go. Move a Book to Reading to see it here.
                </p>
                <Button as-child variant="outline" size="sm">
                    <Link :href="search()">
                        <Search />
                        Find a book
                    </Link>
                </Button>
            </div>
        </section>

        <div class="grid gap-6 lg:grid-cols-3">
            <section
                aria-labelledby="months-heading"
                class="flex flex-col gap-4 rounded-xl border border-border p-4 lg:col-span-2"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <h2 id="months-heading" class="font-medium">
                            Completed Books by month
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            {{ completedInYear }}
                            {{ completedInYear === 1 ? 'Book' : 'Books' }}
                            completed in {{ year }}
                        </p>
                    </div>
                    <div class="flex items-center gap-2">
                        <Label for="year">Year</Label>
                        <Select
                            :model-value="String(year)"
                            @update:model-value="showYear"
                        >
                            <SelectTrigger id="year" class="w-28">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="option in years"
                                    :key="option"
                                    :value="String(option)"
                                >
                                    {{ option }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div
                    aria-hidden="true"
                    class="flex h-48 items-end gap-1 border-b"
                >
                    <div
                        v-for="(count, month) in completedPerMonth"
                        :key="month"
                        class="group relative flex h-full flex-1 flex-col justify-end"
                    >
                        <span
                            class="pointer-events-none absolute -top-1 left-1/2 z-10 -translate-x-1/2 -translate-y-full rounded-md border bg-popover px-2 py-1 text-xs whitespace-nowrap text-popover-foreground opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                        >
                            {{ MONTHS[month] }}: {{ count }}
                        </span>
                        <div
                            class="mx-auto w-full max-w-8 rounded-t-[4px] bg-primary"
                            :style="{
                                height: `${(count / chartMax) * 100}%`,
                            }"
                        />
                    </div>
                </div>
                <div aria-hidden="true" class="-mt-2 flex gap-1">
                    <span
                        v-for="month in MONTHS"
                        :key="month"
                        class="flex-1 text-center text-xs text-muted-foreground"
                    >
                        {{ month }}
                    </span>
                </div>

                <div class="sr-only">
                    <table>
                        <caption>
                            Completed Books per month in
                            {{
                                year
                            }}
                        </caption>
                        <thead>
                            <tr>
                                <th scope="col">Month</th>
                                <th scope="col">Completed Books</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(count, month) in completedPerMonth"
                                :key="month"
                            >
                                <th scope="row">{{ MONTHS[month] }}</th>
                                <td>{{ count }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>

            <section
                aria-labelledby="authors-heading"
                class="flex flex-col gap-3 rounded-xl border border-border p-4"
            >
                <h2 id="authors-heading" class="font-medium">Top Authors</h2>
                <ol v-if="topAuthors.length > 0" class="flex flex-col gap-2">
                    <li
                        v-for="(author, position) in topAuthors"
                        :key="author.id"
                        class="flex items-baseline gap-3 text-sm"
                    >
                        <span
                            class="w-5 text-right text-muted-foreground tabular-nums"
                        >
                            {{ position + 1 }}.
                        </span>
                        <span class="min-w-0 flex-1 truncate">
                            {{ author.name }}
                        </span>
                        <span class="text-muted-foreground tabular-nums">
                            {{ author.completed_books }}
                            {{
                                author.completed_books === 1 ? 'Book' : 'Books'
                            }}
                        </span>
                    </li>
                </ol>
                <p v-else class="text-sm text-muted-foreground">
                    Complete a Book to see your most-read Authors.
                </p>
            </section>
        </div>
    </div>
</template>
