<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        fill="none"
        stroke="currentColor"
        stroke-width="2.2"
        stroke-linecap="round"
        stroke-linejoin="round"
        aria-hidden="true"
        :class="className"
        v-bind="$attrs"
    >
        <path
            d="M4 13.5c4-1.6 8-1.2 12 1.3 4-2.5 8-2.9 12-1.3v13c-4-1.6-8-1.2-12 1.3-4-2.5-8-2.9-12-1.3z"
        />
        <path d="M16 14.8v13" />
        <path d="M14.5 10.5c-1.6-1.6 1.6-2.6 0-4.4" />
        <path d="M18.8 10.5c-1.6-1.8 1.6-3.2 0-5.4" />
    </svg>
</template>
