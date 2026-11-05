<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { mainNavItems } from '@/lib/mainNav';
import { toUrl } from '@/lib/utils';

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <nav
        aria-label="Main"
        class="fixed inset-x-0 bottom-0 z-30 border-t border-border bg-background/95 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
    >
        <ul class="grid grid-cols-4">
            <li v-for="item in mainNavItems" :key="toUrl(item.href)">
                <Link
                    :href="item.href"
                    :aria-current="
                        isCurrentOrParentUrl(item.href) ? 'page' : undefined
                    "
                    class="group flex min-h-14 flex-col items-center justify-center gap-0.5 text-xs font-semibold text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none focus-visible:ring-inset aria-[current=page]:text-primary"
                >
                    <span
                        class="flex h-7 w-12 items-center justify-center rounded-full transition-colors group-aria-[current=page]:bg-accent"
                    >
                        <component
                            :is="item.icon"
                            aria-hidden="true"
                            class="size-5"
                        />
                    </span>
                    {{ item.title }}
                </Link>
            </li>
        </ul>
    </nav>
</template>
