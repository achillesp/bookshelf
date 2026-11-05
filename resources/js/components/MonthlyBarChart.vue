<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    /** Completed Books for each month, January first. */
    counts: number[];
    year: number;
    /** A small sparkline without month labels or tooltips. */
    compact?: boolean;
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

const max = computed(() => Math.max(1, ...props.counts));
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            aria-hidden="true"
            class="flex items-end border-b border-border"
            :class="compact ? 'h-12 gap-0.5' : 'h-48 gap-1.5'"
        >
            <div
                v-for="(count, month) in counts"
                :key="month"
                class="group relative flex h-full flex-1 flex-col justify-end"
            >
                <span
                    v-if="!compact"
                    class="pointer-events-none absolute -top-1 left-1/2 z-10 -translate-x-1/2 -translate-y-full rounded-lg border bg-popover px-2 py-1 text-xs whitespace-nowrap text-popover-foreground opacity-0 shadow-sm transition-opacity group-hover:opacity-100"
                >
                    {{ MONTHS[month] }}: {{ count }}
                </span>
                <div
                    class="mx-auto w-full bg-primary"
                    :class="
                        compact
                            ? 'max-w-3 rounded-t-sm'
                            : 'max-w-9 rounded-t-lg transition-colors group-hover:bg-primary/85'
                    "
                    :style="{ height: `${(count / max) * 100}%` }"
                />
            </div>
        </div>
        <div v-if="!compact" aria-hidden="true" class="flex gap-1.5">
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
                    <tr v-for="(count, month) in counts" :key="month">
                        <th scope="row">{{ MONTHS[month] }}</th>
                        <td>{{ count }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
