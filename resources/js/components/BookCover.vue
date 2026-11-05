<script setup lang="ts">
import { BookOpen } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        coverId: number | null;
        title: string;
        size?: 'S' | 'M' | 'L';
    }>(),
    { size: 'M' },
);

const failed = ref(false);

watch(
    () => props.coverId,
    () => {
        failed.value = false;
    },
);

const src = computed(() =>
    props.coverId === null || failed.value
        ? null
        : `https://covers.openlibrary.org/b/id/${props.coverId}-${props.size}.jpg?default=false`,
);
</script>

<template>
    <div
        class="flex aspect-[2/3] shrink-0 items-center justify-center overflow-hidden rounded-md border border-sidebar-border/70 bg-muted dark:border-sidebar-border"
    >
        <img
            v-if="src"
            :src="src"
            :alt="`Cover of ${title}`"
            loading="lazy"
            class="size-full object-cover"
            @error="failed = true"
        />
        <BookOpen
            v-else
            class="size-1/3 text-muted-foreground"
            role="img"
            :aria-label="`No cover for ${title}`"
        />
    </div>
</template>
