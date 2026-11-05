<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import BookCover from '@/components/BookCover.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { search } from '@/routes';
import type { SearchResult } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Search',
                href: search(),
            },
        ],
    },
});

defineProps<{
    query: string;
    results: SearchResult[];
    error: string | null;
}>();
</script>

<template>
    <Head title="Search" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4">
        <h1 class="sr-only">Search books</h1>

        <Form
            v-bind="search.form()"
            class="flex flex-col gap-2"
            v-slot="{ errors, processing }"
        >
            <Label for="q">Title, author or ISBN</Label>
            <div class="flex gap-2">
                <Input
                    id="q"
                    name="q"
                    type="search"
                    class="max-w-xl"
                    :default-value="query"
                    maxlength="200"
                    placeholder="e.g. Dune, Ursula K. Le Guin or 9780441172719"
                    :aria-invalid="errors.q ? true : undefined"
                    :aria-describedby="errors.q ? 'q-error' : undefined"
                />
                <Button type="submit" :disabled="processing">
                    <Search />
                    Search
                </Button>
            </div>
            <InputError id="q-error" :message="errors.q" />
        </Form>

        <div aria-live="polite">
            <p
                v-if="error"
                class="rounded-md border border-destructive/40 bg-destructive/5 p-4 text-sm"
            >
                {{ error }}
            </p>
            <p
                v-else-if="query !== '' && results.length === 0"
                class="text-sm text-muted-foreground"
            >
                No books found for “{{ query }}”.
            </p>
            <p v-else-if="query !== ''" class="sr-only">
                {{ results.length }} books found.
            </p>
        </div>

        <ul v-if="results.length > 0" class="flex flex-col gap-3">
            <li
                v-for="result in results"
                :key="result.openlibrary_id"
                class="flex gap-4 rounded-xl border border-sidebar-border/70 p-3 dark:border-sidebar-border"
            >
                <BookCover
                    :cover-id="result.cover_id"
                    :title="result.title"
                    class="w-16"
                />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <h2 class="font-medium">{{ result.title }}</h2>
                    <p
                        v-if="result.authors.length > 0"
                        class="text-sm text-muted-foreground"
                    >
                        {{
                            result.authors
                                .map((author) => author.name)
                                .join(', ')
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        <span v-if="result.first_publish_year">
                            First published {{ result.first_publish_year }}
                        </span>
                        <span
                            v-if="
                                result.first_publish_year &&
                                result.page_count_median
                            "
                            aria-hidden="true"
                        >
                            ·
                        </span>
                        <span v-if="result.page_count_median">
                            ~{{ result.page_count_median }} pages
                        </span>
                    </p>
                </div>
            </li>
        </ul>
    </div>
</template>
