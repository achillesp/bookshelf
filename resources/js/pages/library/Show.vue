<script setup lang="ts">
import { Form, Head, router, setLayoutProps } from '@inertiajs/vue3';
import { ref } from 'vue';
import BookCover from '@/components/BookCover.vue';
import InputError from '@/components/InputError.vue';
import RatingInput from '@/components/RatingInput.vue';
import ReadingStatusSelect from '@/components/ReadingStatusSelect.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { index, show, update } from '@/routes/library';
import type { LibraryEntry, ReadingStatus } from '@/types';

const props = defineProps<{
    entry: LibraryEntry;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Library', href: index() },
        { title: props.entry.book.title, href: show(props.entry.id) },
    ],
});

const savedMessage = ref('');

/**
 * Notes live in a draft outside the keyed Form, so typed but unsaved Notes
 * survive a remount when a Reading Status change sets dates on the server.
 */
const notes = ref(props.entry.notes ?? '');

const today = new Date().toLocaleDateString('en-CA');

function save(
    data: { status: ReadingStatus } | { rating: number | null },
): void {
    router.patch(update.url(props.entry.id), data, { preserveScroll: true });
}
</script>

<template>
    <Head :title="entry.book.title" />

    <div class="flex h-full max-w-3xl flex-1 flex-col gap-8 p-4">
        <div class="flex gap-6">
            <BookCover
                :cover-id="entry.book.cover_id"
                :title="entry.book.title"
                size="L"
                class="w-28 sm:w-36"
            />
            <div class="flex min-w-0 flex-col gap-1">
                <h1 class="text-xl font-semibold tracking-tight">
                    {{ entry.book.title }}
                </h1>
                <p
                    v-if="entry.book.authors.length > 0"
                    class="text-muted-foreground"
                >
                    {{
                        entry.book.authors
                            .map((author) => author.name)
                            .join(', ')
                    }}
                </p>
                <p class="text-sm text-muted-foreground">
                    <span v-if="entry.book.first_publish_year">
                        First published {{ entry.book.first_publish_year }}
                    </span>
                    <span
                        v-if="
                            entry.book.first_publish_year &&
                            entry.book.page_count_median
                        "
                        aria-hidden="true"
                    >
                        ·
                    </span>
                    <span v-if="entry.book.page_count_median">
                        ~{{ entry.book.page_count_median }} pages
                    </span>
                </p>
                <div class="mt-3 flex flex-wrap items-center gap-3">
                    <ReadingStatusSelect
                        :model-value="entry.status"
                        label="Reading Status"
                        @update:model-value="save({ status: $event })"
                    />
                    <RatingInput
                        :model-value="entry.rating"
                        label="Rating"
                        @update:model-value="save({ rating: $event })"
                    />
                </div>
            </div>
        </div>

        <Form
            :key="`${entry.started_on}|${entry.finished_on}`"
            v-bind="update.form(entry.id)"
            :options="{ preserveScroll: true }"
            class="flex flex-col gap-6"
            v-slot="{ errors, processing }"
            @start="savedMessage = ''"
            @success="savedMessage = 'Dates and Notes saved.'"
        >
            <div class="flex flex-wrap gap-6">
                <div class="grid content-start gap-2">
                    <Label for="started_on">Started Date</Label>
                    <Input
                        id="started_on"
                        name="started_on"
                        type="date"
                        class="w-44"
                        :max="today"
                        :default-value="entry.started_on ?? ''"
                        :aria-invalid="errors.started_on ? true : undefined"
                        :aria-describedby="
                            errors.started_on ? 'started_on-error' : undefined
                        "
                    />
                    <InputError
                        id="started_on-error"
                        :message="errors.started_on"
                    />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="finished_on">Finished Date</Label>
                    <Input
                        id="finished_on"
                        name="finished_on"
                        type="date"
                        class="w-44"
                        :max="today"
                        :default-value="entry.finished_on ?? ''"
                        :aria-invalid="errors.finished_on ? true : undefined"
                        :aria-describedby="
                            errors.finished_on ? 'finished_on-error' : undefined
                        "
                    />
                    <InputError
                        id="finished_on-error"
                        :message="errors.finished_on"
                    />
                </div>
            </div>

            <div class="grid content-start gap-2">
                <Label for="notes">Notes</Label>
                <textarea
                    id="notes"
                    name="notes"
                    rows="8"
                    maxlength="10000"
                    class="w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 aria-invalid:border-destructive dark:bg-input/30"
                    placeholder="What did you think?"
                    v-model="notes"
                    :aria-invalid="errors.notes ? true : undefined"
                    :aria-describedby="errors.notes ? 'notes-error' : undefined"
                />
                <InputError id="notes-error" :message="errors.notes" />
            </div>

            <Button type="submit" class="self-start" :disabled="processing">
                Save
            </Button>
        </Form>

        <p class="-mt-6 text-sm text-muted-foreground" aria-live="polite">
            {{ savedMessage }}
        </p>
    </div>
</template>
