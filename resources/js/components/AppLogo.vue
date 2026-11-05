<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import AppLogoIcon from '@/components/AppLogoIcon.vue';

const name = usePage().props.name;
</script>

<template>
    <span class="flex min-w-0 items-center gap-2">
        <AppLogoIcon class="size-8 shrink-0 text-primary" />
        <span
            class="truncate font-display text-xl leading-none font-bold tracking-tight"
            >{{ name }}</span
        >
    </span>
</template>
