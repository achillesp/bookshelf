<script setup lang="ts">
import { Form, Head, router } from '@inertiajs/vue3';
import { Check, Plus, Search as SearchIcon } from '@lucide/vue';
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

    <div class="flex flex-col gap-8 px-4 py-8">
        <header class="flex flex-col gap-1">
            <h1 class="text-3xl font-bold tracking-tight">
                Find your next read
            </h1>
            <p class="text-muted-foreground">
                Search the OpenLibrary catalogue and add a Book to your Library.
            </p>
        </header>

        <Form
            v-bind="search.form()"
            class="flex flex-col gap-2 rounded-2xl border bg-card p-5 shadow-sm"
            v-slot="{ errors, processing }"
        >
            <Label for="q">Title, Author or ISBN</Label>
            <div class="flex flex-col gap-2 sm:flex-row">
                <Input
                    id="q"
                    name="q"
                    type="search"
                    class="h-11 sm:max-w-xl"
                    :default-value="query"
                    maxlength="200"
                    placeholder="e.g. Dune, Ursula K. Le Guin or 9780441172719"
                    :aria-invalid="errors.q ? true : undefined"
                    :aria-describedby="errors.q ? 'q-error' : undefined"
                />
                <Button type="submit" class="h-11" :disabled="processing">
                    <SearchIcon />
                    Search
                </Button>
            </div>
            <InputError id="q-error" :message="errors.q" />
        </Form>

        <div aria-live="polite">
            <p
                v-if="error"
                class="rounded-2xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
            >
                {{ error }}
            </p>
            <p
                v-else-if="query !== '' && results.length === 0"
                class="text-muted-foreground"
            >
                No Books found for “{{ query }}”. Try a shorter title or just
                the Author's name.
            </p>
            <p v-else-if="query !== ''" class="sr-only">
                {{ results.length }}
                {{ results.length === 1 ? 'Book' : 'Books' }} found.
            </p>
        </div>

        <ul v-if="results.length > 0" class="grid gap-4 lg:grid-cols-2">
            <li
                v-for="result in results"
                :key="result.openlibrary_id"
                class="flex gap-4 rounded-2xl border bg-card p-4 shadow-sm sm:gap-5"
            >
                <BookCover
                    :cover-id="result.cover_id"
                    :title="result.title"
                    size="L"
                    class="w-24 rounded-l-sm rounded-r-md shadow-md sm:w-28"
                />
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <h2 class="leading-snug font-bold">
                        {{ result.title }}
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
                    <div class="mt-auto pt-3">
                        <Badge
                            v-if="inLibrary.includes(result.openlibrary_id)"
                            class="animate-in rounded-full bg-secondary px-3 py-1 text-secondary-foreground duration-300 zoom-in-75"
                        >
                            <Check />
                            In your Library
                        </Badge>
                        <div v-else class="flex flex-wrap items-center gap-2">
                            <ReadingStatusSelect
                                :model-value="
                                    startingStatus[result.openlibrary_id] ??
                                    READING_STATUS.WANT_TO_READ
                                "
                                :label="`Starting status for ${result.title}`"
                                :disabled="adding !== null"
                                @update:model-value="
                                    startingStatus[result.openlibrary_id] =
                                        $event
                                "
                            />
                            <Button
                                :disabled="adding !== null"
                                :aria-label="`Add ${result.title} to your Library`"
                                @click="addToLibrary(result)"
                            >
                                <Plus />
                                Add
                            </Button>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</template>
