<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import AppLogo from '@/components/AppLogo.vue';
import ReadingNook from '@/components/decor/ReadingNook.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="grid min-h-dvh bg-background lg:grid-cols-2">
        <aside
            class="relative hidden flex-col justify-between gap-10 overflow-hidden border-r bg-card p-10 lg:flex"
        >
            <Link
                :href="home()"
                class="flex items-center self-start rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <AppLogo />
            </Link>
            <ReadingNook class="mx-auto h-auto w-full max-w-lg" />
            <p
                class="max-w-sm font-display text-2xl leading-snug font-bold text-balance"
            >
                Every Book you want to read, are reading and have finished, in
                one cozy corner.
            </p>
        </aside>

        <main
            class="flex flex-col items-center justify-center px-4 py-10 sm:px-8"
        >
            <div class="flex w-full max-w-sm flex-col gap-8">
                <Link
                    :href="home()"
                    class="flex items-center self-center rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none lg:hidden"
                >
                    <AppLogo />
                </Link>
                <div class="flex flex-col gap-2 text-center lg:text-left">
                    <h1 v-if="title" class="text-3xl font-bold tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </main>
    </div>
</template>
