<script setup lang="ts">
import AppContent from '@/components/AppContent.vue';
import AppHeader from '@/components/AppHeader.vue';
import AppShell from '@/components/AppShell.vue';
import BottomTabBar from '@/components/BottomTabBar.vue';
import { Toaster } from '@/components/ui/sonner';
</script>

<template>
    <AppShell>
        <AppHeader />
        <AppContent>
            <slot />
        </AppContent>
        <BottomTabBar />
        <Toaster
            class="[--toast-bottom:calc(4.5rem+env(safe-area-inset-bottom))] md:[--toast-bottom:24px]"
            :offset="{ bottom: 'var(--toast-bottom)' }"
            :mobile-offset="{ bottom: 'var(--toast-bottom)' }"
        />
    </AppShell>
</template>
