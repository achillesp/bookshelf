<script setup lang="ts">
import { EllipsisVertical, Star, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuRadioGroup,
    DropdownMenuRadioItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { READING_STATUS_LABELS } from '@/types';
import type { LibraryEntry, ReadingStatus } from '@/types';

const props = defineProps<{
    entry: LibraryEntry;
    disabled?: boolean;
}>();

const emit = defineEmits<{
    status: [status: ReadingStatus];
    rating: [rating: number | null];
    remove: [];
}>();

const NO_RATING = 'none';

const statuses = Object.entries(READING_STATUS_LABELS).map(([value, text]) => ({
    value,
    text,
}));

const ratings = [1, 2, 3, 4, 5];

function isReadingStatus(value: unknown): value is ReadingStatus {
    return typeof value === 'string' && value in READING_STATUS_LABELS;
}

function chooseStatus(value: unknown): void {
    if (isReadingStatus(value) && value !== props.entry.status) {
        emit('status', value);
    }
}

function chooseRating(value: unknown): void {
    const rating = value === NO_RATING ? null : Number(value);

    if (rating !== props.entry.rating) {
        emit('rating', rating);
    }
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <Button
                :id="`entry-menu-${entry.id}`"
                variant="secondary"
                size="icon"
                class="size-9 rounded-full shadow-md"
                :disabled="disabled"
                :aria-label="`Change “${entry.book.title}”`"
            >
                <EllipsisVertical />
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-56">
            <DropdownMenuLabel>Reading Status</DropdownMenuLabel>
            <DropdownMenuRadioGroup
                :model-value="entry.status"
                @update:model-value="chooseStatus"
            >
                <DropdownMenuRadioItem
                    v-for="status in statuses"
                    :key="status.value"
                    :value="status.value"
                    class="min-h-9"
                >
                    {{ status.text }}
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>

            <DropdownMenuSeparator />

            <DropdownMenuLabel>Rating</DropdownMenuLabel>
            <DropdownMenuRadioGroup
                :model-value="
                    entry.rating === null ? NO_RATING : String(entry.rating)
                "
                @update:model-value="chooseRating"
            >
                <DropdownMenuRadioItem :value="NO_RATING" class="min-h-9">
                    No Rating
                </DropdownMenuRadioItem>
                <DropdownMenuRadioItem
                    v-for="rating in ratings"
                    :key="rating"
                    :value="String(rating)"
                    class="min-h-9"
                >
                    <span class="sr-only">
                        {{ rating }} {{ rating === 1 ? 'star' : 'stars' }}
                    </span>
                    <span aria-hidden="true" class="flex gap-0.5">
                        <Star
                            v-for="star in rating"
                            :key="star"
                            class="size-4 fill-star text-star"
                        />
                    </span>
                </DropdownMenuRadioItem>
            </DropdownMenuRadioGroup>

            <DropdownMenuSeparator />

            <DropdownMenuItem
                variant="destructive"
                class="min-h-9"
                @select="emit('remove')"
            >
                <Trash2 />
                Remove from Library…
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
