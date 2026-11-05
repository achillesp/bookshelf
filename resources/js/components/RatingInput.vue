<script setup lang="ts">
import { Star, X } from '@lucide/vue';

defineProps<{
    label: string;
    disabled?: boolean;
}>();

const rating = defineModel<number | null>({ required: true });

const stars = [1, 2, 3, 4, 5];

function choose(star: number): void {
    rating.value = star === rating.value ? null : star;
}
</script>

<template>
    <div role="group" :aria-label="label" class="flex items-center">
        <button
            v-for="star in stars"
            :key="star"
            type="button"
            class="flex size-7 items-center justify-center rounded-sm text-muted-foreground hover:text-amber-500 focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
            :aria-label="`${star} ${star === 1 ? 'star' : 'stars'}`"
            :aria-pressed="rating !== null && star <= rating"
            :disabled="disabled"
            @click="choose(star)"
        >
            <Star
                class="size-4"
                :class="
                    rating !== null && star <= rating
                        ? 'fill-amber-400 text-amber-500'
                        : ''
                "
            />
        </button>
        <button
            v-if="rating !== null"
            type="button"
            class="flex size-7 items-center justify-center rounded-sm text-muted-foreground hover:text-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:pointer-events-none disabled:opacity-50"
            aria-label="Clear rating"
            :disabled="disabled"
            @click="rating = null"
        >
            <X class="size-4" />
        </button>
    </div>
</template>
