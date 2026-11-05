<script setup lang="ts">
import { READING_STATUS_LABELS } from '@/types';
import type { ReadingStatus } from '@/types';

defineProps<{
    status: ReadingStatus;
}>();

const STATUS_CLASSES: Record<ReadingStatus, string> = {
    want_to_read: 'bg-accent text-accent-foreground',
    reading: 'bg-mustard text-mustard-foreground',
    completed: 'bg-secondary text-secondary-foreground',
};
</script>

<template>
    <span
        class="inline-flex w-fit items-center rounded-full px-2.5 py-0.5 text-xs font-bold"
        :class="STATUS_CLASSES[status]"
    >
        {{ READING_STATUS_LABELS[status] }}
    </span>
</template>
