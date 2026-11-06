<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        /** A value for each month, January first. */
        counts: number[];
        year: number;
        /** What the values are, for the table header and screen-reader text. */
        label?: string;
        /** Formats a value for the tooltip, table and summary. */
        formatValue?: (value: number) => string;
        /** A small sparkline without month labels or tooltips. */
        compact?: boolean;
        /** Shows the month-by-month table under the chart. */
        showTable?: boolean;
        /** The id of the table's container, for the button that toggles it. */
        tableId?: string;
    }>(),
    {
        label: 'Completed Books',
        formatValue: (value: number) => String(value),
        compact: false,
        showTable: false,
        tableId: undefined,
    },
);

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

const MONTH_NAMES = [
    'January',
    'February',
    'March',
    'April',
    'May',
    'June',
    'July',
    'August',
    'September',
    'October',
    'November',
    'December',
];

const max = computed(() => Math.max(1, ...props.counts));

const summary = computed(() => {
    const total = props.counts.reduce((sum, count) => sum + count, 0);

    if (total === 0) {
        return `${props.label} in ${props.year}: none yet.`;
    }

    const busiest = Math.max(...props.counts);
    const busiestMonth = MONTH_NAMES[props.counts.indexOf(busiest)];

    return `${props.label} in ${props.year}: ${props.formatValue(total)} in total, most in ${busiestMonth} (${props.formatValue(busiest)}).`;
});
</script>

<template>
    <div class="flex flex-col gap-2">
        <div
            aria-hidden="true"
            class="flex items-end border-b border-border"
            :class="compact ? 'h-12 gap-0.5' : 'h-48 gap-1 sm:gap-1.5'"
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
                    {{ MONTHS[month] }}: {{ formatValue(count) }}
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
        <div v-if="!compact" aria-hidden="true" class="flex gap-1 sm:gap-1.5">
            <span
                v-for="month in MONTHS"
                :key="month"
                class="min-w-0 flex-1 text-center text-[0.6875rem] text-muted-foreground sm:text-xs"
            >
                {{ month }}
            </span>
        </div>

        <p v-if="compact || !showTable" class="sr-only">{{ summary }}</p>

        <div v-if="!compact" :id="tableId">
            <table v-if="showTable" class="mt-4 w-full text-sm">
                <caption class="sr-only">
                    {{
                        label
                    }}
                    per month in
                    {{
                        year
                    }}
                </caption>
                <thead>
                    <tr class="border-b text-left text-muted-foreground">
                        <th scope="col" class="py-2 font-medium">Month</th>
                        <th scope="col" class="py-2 text-right font-medium">
                            {{ label }}
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <tr v-for="(count, month) in counts" :key="month">
                        <th scope="row" class="py-1.5 text-left font-normal">
                            {{ MONTH_NAMES[month] }}
                        </th>
                        <td class="py-1.5 text-right tabular-nums">
                            {{ formatValue(count) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</template>
