<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { BookCheck, BookMarked, BookOpen } from '@lucide/vue';
import { computed } from 'vue';
import MonthlyBarChart from '@/components/MonthlyBarChart.vue';
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

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Stats',
                href: stats(),
            },
        ],
    },
});

const props = defineProps<{
    statusCounts: Record<ReadingStatus, number>;
    year: number;
    years: number[];
    completedPerMonth: number[];
    topAuthors: { id: number; name: string; completed_books: number }[];
}>();

const STATUS_ICONS = {
    want_to_read: BookMarked,
    reading: BookOpen,
    completed: BookCheck,
} as const;

const statuses = Object.values(READING_STATUS).map((status) => ({
    status,
    label: READING_STATUS_LABELS[status],
    count: props.statusCounts[status],
    icon: STATUS_ICONS[status],
    href: library({ query: { status } }),
}));

const completedInYear = computed(() =>
    props.completedPerMonth.reduce((total, count) => total + count, 0),
);

function books(count: number): string {
    return `${count} ${count === 1 ? 'Book' : 'Books'}`;
}

function showYear(value: unknown): void {
    router.get(
        stats.url({ query: { year: String(value) } }),
        {},
        { preserveScroll: true, preserveState: true },
    );
}
</script>

<template>
    <Head title="Stats" />

    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-8">
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
            <ul class="grid gap-4 sm:grid-cols-3">
                <li v-for="item in statuses" :key="item.status">
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

        <div class="grid gap-6 lg:grid-cols-3">
            <section
                aria-labelledby="months-heading"
                class="flex flex-col gap-6 rounded-2xl border bg-card p-5 shadow-sm lg:col-span-2"
            >
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div class="flex flex-col gap-1">
                        <h2 id="months-heading" class="text-xl font-bold">
                            Completed Books by month
                        </h2>
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

                <MonthlyBarChart :counts="completedPerMonth" :year="year" />
            </section>

            <section
                aria-labelledby="authors-heading"
                class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm"
            >
                <h2 id="authors-heading" class="text-xl font-bold">
                    Top Authors
                </h2>
                <ol v-if="topAuthors.length > 0" class="flex flex-col gap-2.5">
                    <li
                        v-for="(author, position) in topAuthors"
                        :key="author.id"
                        class="flex items-baseline gap-3 text-sm"
                    >
                        <span
                            class="w-5 text-right font-display font-bold text-primary tabular-nums"
                        >
                            {{ position + 1 }}
                        </span>
                        <span class="min-w-0 flex-1 truncate font-semibold">
                            {{ author.name }}
                        </span>
                        <span class="text-muted-foreground tabular-nums">
                            {{ books(author.completed_books) }}
                        </span>
                    </li>
                </ol>
                <p v-else class="text-sm text-muted-foreground">
                    Finish your first Book and its Author takes a seat here.
                </p>
            </section>
        </div>
    </div>
</template>
