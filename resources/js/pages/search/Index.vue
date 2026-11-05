<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Check, Plus, Search } from '@lucide/vue';
import { reactive, ref } from 'vue';
import BookCover from '@/components/BookCover.vue';
import InputError from '@/components/InputError.vue';
import ReadingStatusSelect from '@/components/ReadingStatusSelect.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { search } from '@/routes';
import { store } from '@/routes/library';
import { READING_STATUS } from '@/types';
import type { ReadingStatus, SearchResult } from '@/types';

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
    inLibrary: string[];
    error: string | null;
}>();

const startingStatus = reactive<Record<string, ReadingStatus>>({});
const adding = ref<string | null>(null);

function addToLibrary(result: SearchResult): void {
    router.post(
        store.url(),
        {
            openlibrary_id: result.openlibrary_id,
            status:
                startingStatus[result.openlibrary_id] ??
                READING_STATUS.WANT_TO_READ,
        },
        {
            preserveScroll: true,
            onStart: () => {
                adding.value = result.openlibrary_id;
            },
            onFinish: () => {
                adding.value = null;
            },
        },
    );
}
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
                    <h2 class="flex flex-wrap items-center gap-2 font-medium">
                        {{ result.title }}
                        <Badge
                            v-if="inLibrary.includes(result.openlibrary_id)"
                            variant="secondary"
                        >
                            <Check />
                            In your Library
                        </Badge>
                    </h2>
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
                    <div
                        v-if="!inLibrary.includes(result.openlibrary_id)"
                        class="mt-2 flex flex-wrap items-center gap-2"
                    >
                        <ReadingStatusSelect
                            :model-value="
                                startingStatus[result.openlibrary_id] ??
                                READING_STATUS.WANT_TO_READ
                            "
                            :label="`Starting status for ${result.title}`"
                            :disabled="adding !== null"
                            @update:model-value="
                                startingStatus[result.openlibrary_id] = $event
                            "
                        />
                        <Button
                            size="sm"
                            :disabled="adding !== null"
                            :aria-label="`Add ${result.title} to your Library`"
                            @click="addToLibrary(result)"
                        >
                            <Plus />
                            Add
                        </Button>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
