<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    BookCheck,
    BookMarked,
    BookOpen,
    LibraryBig,
    Table2,
} from '@lucide/vue';
import { computed, ref, useId } from 'vue';
import MonthlyBarChart from '@/components/MonthlyBarChart.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { stats } from '@/routes';
import { index as library } from '@/routes/library';
import { READING_STATUS, READING_STATUS_LABELS } from '@/types';
import type { ReadingStatus } from '@/types';

const props = defineProps<{
    statusCounts: Record<ReadingStatus, number>;
    year: number;
    years: number[];
    completedPerMonth: number[];
    pagesPerMonth: number[];
    topAuthors: {
        id: number;
        name: string;
        total_books: number;
        want_to_read: number;
        reading: number;
        completed: number;
        average_rating: number | null;
    }[];
}>();

const STATUS_ICONS = {
    want_to_read: BookMarked,
    reading: BookOpen,
    completed: BookCheck,
} as const;

const statuses = Object.values(READING_STATUS).map((status) => ({
    key: status,
    label: READING_STATUS_LABELS[status],
    count: props.statusCounts[status],
    icon: STATUS_ICONS[status],
    href: library({ query: { status } }),
}));

const tiles = [
    {
        key: 'total',
        label: 'Total in Library',
        count: statuses.reduce((total, item) => total + item.count, 0),
        icon: LibraryBig,
        href: library(),
    },
    ...statuses,
];

const completedInYear = computed(() =>
    props.completedPerMonth.reduce((total, count) => total + count, 0),
);

const pagesInYear = computed(() =>
    props.pagesPerMonth.reduce((total, pages) => total + pages, 0),
);

const mostBooksByOneAuthor = computed(() =>
    Math.max(1, ...props.topAuthors.map((author) => author.total_books)),
);

const completedTableId = useId();
const pagesTableId = useId();
const showCompletedTable = ref(false);
const showPagesTable = ref(false);
const isLoadingYear = ref(false);

function books(count: number): string {
    return `${count} ${count === 1 ? 'Book' : 'Books'}`;
}

/** Page counts are medians across Editions, so they are always shown as approximate. */
function pages(count: number): string {
    return count === 0 ? '0 pages' : `~${count.toLocaleString('en')} pages`;
}

function showYear(value: unknown): void {
    router.get(
        stats.url({ query: { year: String(value) } }),
        {},
        {
            only: ['year', 'completedPerMonth', 'pagesPerMonth'],
            preserveScroll: true,
            preserveState: true,
            onStart: () => (isLoadingYear.value = true),
            onFinish: () => (isLoadingYear.value = false),
        },
    );
}
</script>

<template>
    <Head title="Stats" />

    <div class="flex flex-col gap-8 px-4 py-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold tracking-tight">
                Your reading stats
            </h1>
            <p class="text-muted-foreground">
                Every Book you've finished, counted and charted.
            </p>
        </header>

        <section aria-labelledby="status-heading" class="flex flex-col gap-3">
            <h2 id="status-heading" class="sr-only">
                Library by Reading Status
            </h2>
            <ul class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <li v-for="item in tiles" :key="item.key">
                    <Link
                        :href="item.href"
                        class="flex items-center gap-4 rounded-2xl border bg-card p-5 shadow-sm transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span
                            aria-hidden="true"
                            class="flex size-11 shrink-0 items-center justify-center rounded-full bg-accent text-accent-foreground"
                        >
                            <component :is="item.icon" class="size-5" />
                        </span>
                        <span class="flex flex-col">
                            <span class="text-sm text-muted-foreground">
                                {{ item.label }}
                            </span>
                            <span
                                class="font-display text-3xl font-bold tabular-nums"
                            >
                                {{ item.count }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
        </section>

        <section aria-labelledby="months-heading" class="flex flex-col gap-4">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <h2 id="months-heading" class="text-xl font-bold">
                    Month by month
                </h2>
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
                class="grid grid-cols-1 items-start gap-6 transition-opacity lg:grid-cols-2"
                :class="{ 'opacity-60': isLoadingYear }"
                :aria-busy="isLoadingYear"
            >
                <section
                    aria-labelledby="completed-heading"
                    class="flex min-w-0 flex-col gap-6 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div class="flex flex-col gap-1">
                            <h3
                                id="completed-heading"
                                class="text-lg font-bold"
                            >
                                Completed Books by month
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                <template v-if="completedInYear > 0">
                                    {{ books(completedInYear) }} finished in
                                    {{ year }}.
                                </template>
                                <template v-else>
                                    Nothing finished in {{ year }} yet. There's
                                    still time.
                                </template>
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            :aria-expanded="showCompletedTable"
                            :aria-controls="completedTableId"
                            @click="showCompletedTable = !showCompletedTable"
                        >
                            <Table2 aria-hidden="true" />
                            {{ showCompletedTable ? 'Hide data' : 'Show data' }}
                            <span class="sr-only">for Completed Books</span>
                        </Button>
                    </div>

                    <MonthlyBarChart
                        :counts="completedPerMonth"
                        :year="year"
                        :show-table="showCompletedTable"
                        :table-id="completedTableId"
                    />
                </section>

                <section
                    aria-labelledby="pages-heading"
                    class="flex min-w-0 flex-col gap-6 rounded-2xl border bg-card p-5 shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div class="flex flex-col gap-1">
                            <h3 id="pages-heading" class="text-lg font-bold">
                                Pages read by month
                            </h3>
                            <p class="text-sm text-muted-foreground">
                                <template v-if="pagesInYear > 0">
                                    {{ pages(pagesInYear) }} read in {{ year }}.
                                </template>
                                <template v-else>
                                    No pages read in {{ year }} yet.
                                </template>
                            </p>
                        </div>
                        <Button
                            variant="outline"
                            size="sm"
                            :aria-expanded="showPagesTable"
                            :aria-controls="pagesTableId"
                            @click="showPagesTable = !showPagesTable"
                        >
                            <Table2 aria-hidden="true" />
                            {{ showPagesTable ? 'Hide data' : 'Show data' }}
                            <span class="sr-only">for pages read</span>
                        </Button>
                    </div>

                    <MonthlyBarChart
                        :counts="pagesPerMonth"
                        :year="year"
                        label="Pages read"
                        :format-value="pages"
                        :show-table="showPagesTable"
                        :table-id="pagesTableId"
                    />
                </section>
            </div>
        </section>

        <section
            aria-labelledby="authors-heading"
            class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm"
        >
            <h2 id="authors-heading" class="text-xl font-bold">Top Authors</h2>
            <div
                v-if="topAuthors.length > 0"
                class="-mx-5 overflow-x-auto px-5"
            >
                <table class="w-full min-w-xl text-sm">
                    <thead>
                        <tr class="border-b text-left text-muted-foreground">
                            <th scope="col" class="w-8 py-2 pr-2 font-medium">
                                <span aria-hidden="true">#</span>
                                <span class="sr-only">Rank</span>
                            </th>
                            <th scope="col" class="py-2 pr-4 font-medium">
                                Author
                            </th>
                            <th scope="col" class="w-1/4 py-2 pr-4 font-medium">
                                Total
                            </th>
                            <th
                                v-for="status in READING_STATUS"
                                :key="status"
                                scope="col"
                                class="py-2 pr-4 text-right font-medium"
                            >
                                {{ READING_STATUS_LABELS[status] }}
                            </th>
                            <th scope="col" class="py-2 text-right font-medium">
                                Avg Rating
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr
                            v-for="(author, position) in topAuthors"
                            :key="author.id"
                        >
                            <td
                                class="py-2.5 pr-2 font-display font-bold text-primary tabular-nums"
                            >
                                {{ position + 1 }}
                            </td>
                            <th
                                scope="row"
                                class="py-2.5 pr-4 text-left font-semibold"
                            >
                                {{ author.name }}
                            </th>
                            <td class="py-2.5 pr-4">
                                <div class="flex items-center gap-3">
                                    <span
                                        class="w-6 shrink-0 font-semibold tabular-nums"
                                    >
                                        {{ author.total_books }}
                                    </span>
                                    <span
                                        aria-hidden="true"
                                        class="h-2.5 rounded-full bg-primary"
                                        :style="{
                                            width: `${(author.total_books / mostBooksByOneAuthor) * 100}%`,
                                        }"
                                    />
                                </div>
                            </td>
                            <td
                                v-for="status in READING_STATUS"
                                :key="status"
                                class="py-2.5 pr-4 text-right tabular-nums"
                            >
                                {{ author[status] }}
                            </td>
                            <td class="py-2.5 text-right tabular-nums">
                                <template v-if="author.average_rating !== null">
                                    {{ author.average_rating.toFixed(1) }}
                                </template>
                                <template v-else>
                                    <span aria-hidden="true">—</span>
                                    <span class="sr-only">Not rated</span>
                                </template>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Add your first Book and its Author takes a seat here.
            </p>
        </section>
    </div>
</template>
