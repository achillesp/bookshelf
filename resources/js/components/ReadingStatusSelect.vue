<script setup lang="ts">
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { READING_STATUS_LABELS } from '@/types';
import type { ReadingStatus } from '@/types';

defineProps<{
    label: string;
    disabled?: boolean;
}>();

const status = defineModel<ReadingStatus>({ required: true });

const options = Object.entries(READING_STATUS_LABELS).map(([value, text]) => ({
    value,
    text,
}));

function isReadingStatus(value: unknown): value is ReadingStatus {
    return typeof value === 'string' && value in READING_STATUS_LABELS;
}

function update(value: unknown): void {
    if (isReadingStatus(value)) {
        status.value = value;
    }
}
</script>

<template>
    <Select
        :model-value="status"
        :disabled="disabled"
        @update:model-value="update"
    >
        <SelectTrigger :aria-label="label" class="w-36">
            <SelectValue />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="option in options"
                :key="option.value"
                :value="option.value"
            >
                {{ option.text }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
