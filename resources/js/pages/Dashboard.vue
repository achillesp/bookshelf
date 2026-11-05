<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Search } from '@lucide/vue';
import { computed, onMounted, ref } from 'vue';
import BookCover from '@/components/BookCover.vue';
import MonthlyBarChart from '@/components/MonthlyBarChart.vue';
import { Button } from '@/components/ui/button';
import { search, stats } from '@/routes';
import { index as library, show } from '@/routes/library';
import { READING_STATUS } from '@/types';
import type { LibraryEntry, ReadingStatus } from '@/types';

const props = defineProps<{
    reading: LibraryEntry[];
    statusCounts: Record<ReadingStatus, number>;
    year: number;
    completedPerMonth: number[];
}>();

const page = usePage();

const firstName = computed(
    () => page.props.auth.user.name.trim().split(/\s+/)[0] ?? '',
);

/**
 * The server render has no Reader timezone, so it greets neutrally and the
 * browser swaps in a time-of-day greeting once it knows the local hour.
 */
const greeting = ref(`Welcome back, ${firstName.value}`);

onMounted(() => {
    const hour = new Date().getHours();

    if (hour >= 5 && hour < 12) {
        greeting.value = `Good morning, ${firstName.value}`;
    } else if (hour >= 12 && hour < 17) {
        greeting.value = `Good afternoon, ${firstName.value}`;
    } else if (hour >= 17 && hour < 22) {
        greeting.value = `Good evening, ${firstName.value}`;
    } else {
        greeting.value = `Still up, ${firstName.value}?`;
    }
});

const completedThisYear = computed(() =>
    props.completedPerMonth.reduce((total, count) => total + count, 0),
);

const tiles = computed(() => [
    {
        label: `Completed in ${props.year}`,
        count: completedThisYear.value,
        href: stats(),
    },
    {
        label: 'Reading now',
        count: props.statusCounts.reading,
        href: library({ query: { status: READING_STATUS.READING } }),
    },
    {
        label: 'Want to Read',
        count: props.statusCounts.want_to_read,
        href: library({ query: { status: READING_STATUS.WANT_TO_READ } }),
    },
    {
        label: 'Completed all time',
        count: props.statusCounts.completed,
        href: library({ query: { status: READING_STATUS.COMPLETED } }),
    },
]);

function authorNames(entry: LibraryEntry): string {
    return entry.book.authors.map((author) => author.name).join(', ');
}
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex flex-col gap-10 px-4 py-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold tracking-tight sm:text-4xl">
                {{ greeting }}
            </h1>
            <p class="text-muted-foreground">
                Here's what's on your nightstand.
            </p>
        </header>

        <section aria-labelledby="reading-heading" class="flex flex-col gap-4">
            <h2 id="reading-heading" class="text-xl font-bold">
                Currently Reading
            </h2>
            <ul
                v-if="reading.length > 0"
                class="grid grid-cols-[repeat(auto-fill,minmax(8.5rem,1fr))] gap-6 sm:grid-cols-[repeat(auto-fill,minmax(10rem,1fr))]"
            >
                <li v-for="entry in reading" :key="entry.id">
                    <Link
                        :href="show(entry.id)"
                        class="group flex flex-col gap-3 rounded-xl focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-4 focus-visible:ring-offset-background focus-visible:outline-none"
                    >
                        <BookCover
                            :cover-id="entry.book.cover_id"
                            :title="entry.book.title"
                            size="L"
                            class="w-full rounded-lg shadow-md transition-transform duration-200 group-hover:-translate-y-1 group-hover:shadow-lg"
                        />
                        <span class="flex flex-col gap-0.5">
                            <span class="line-clamp-2 font-semibold">
                                {{ entry.book.title }}
                            </span>
                            <span
                                v-if="entry.book.authors.length > 0"
                                class="line-clamp-1 text-sm text-muted-foreground"
                            >
                                {{ authorNames(entry) }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
            <div
                v-else
                class="flex flex-col items-start gap-4 rounded-2xl border border-dashed bg-card p-6"
            >
                <p class="text-muted-foreground">
                    Nothing on your nightstand yet. Pick something to start and
                    it'll wait for you here.
                </p>
                <Button as-child>
                    <Link :href="search()">
                        <Search />
                        Find your next read
                    </Link>
                </Button>
            </div>
        </section>

        <section aria-labelledby="summary-heading" class="flex flex-col gap-4">
            <h2 id="summary-heading" class="text-xl font-bold">
                Your reading at a glance
            </h2>
            <ul
                class="grid grid-cols-2 gap-4 lg:grid-cols-[repeat(auto-fit,minmax(11rem,1fr))]"
            >
                <li v-for="tile in tiles" :key="tile.label">
                    <Link
                        :href="tile.href"
                        class="flex h-full flex-col gap-1 rounded-2xl border bg-card p-4 shadow-sm transition-shadow hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none sm:p-5"
                    >
                        <span
                            class="font-display text-3xl font-bold tabular-nums sm:text-4xl"
                        >
                            {{ tile.count }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            {{ tile.label }}
                        </span>
                    </Link>
                </li>
            </ul>

            <div
                class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm sm:flex-row sm:items-end sm:gap-8"
            >
                <div class="flex flex-col gap-1 sm:w-56 sm:shrink-0">
                    <h3 class="font-bold">Your {{ year }} so far</h3>
                    <p class="text-sm text-muted-foreground">
                        <template v-if="completedThisYear > 0">
                            {{ completedThisYear }}
                            {{ completedThisYear === 1 ? 'Book' : 'Books' }}
                            finished, month by month.
                        </template>
                        <template v-else>
                            Your first finished Book of the year will show up
                            here.
                        </template>
                    </p>
                    <Link
                        :href="stats()"
                        class="mt-2 inline-flex items-center gap-1 self-start rounded-md text-sm font-semibold text-primary underline-offset-4 hover:underline focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        See all your stats
                        <ArrowRight aria-hidden="true" class="size-4" />
                    </Link>
                </div>
                <MonthlyBarChart
                    :counts="completedPerMonth"
                    :year="year"
                    compact
                    class="min-w-0 flex-1"
                />
            </div>
        </section>
    </div>
</template>
