<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, LibraryBig, Search, Star } from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
import ReadingNook from '@/components/decor/ReadingNook.vue';
import SampleCover from '@/components/decor/SampleCover.vue';
import ReadingStatusBadge from '@/components/ReadingStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { login, register } from '@/routes';

const steps = [
    {
        icon: Search,
        title: 'Find a Book',
        text: 'Search millions of titles by name, Author or ISBN.',
    },
    {
        icon: LibraryBig,
        title: 'Add it to your Library',
        text: 'Mark it Want to Read, Reading or Completed, so you always know what comes next.',
    },
    {
        icon: Star,
        title: 'Track your reading',
        text: 'Give it a Rating, keep private Notes and watch your reading year add up.',
    },
];

const sampleBooks = [
    {
        design: 'lanterns',
        title: 'Lanterns Over Kettle Bay',
        author: 'Mara Ellison',
        status: 'reading',
        rating: null,
    },
    {
        design: 'boats',
        title: 'Salt, Stars & Small Boats',
        author: 'Theo Marchetti',
        status: 'completed',
        rating: 5,
    },
    {
        design: 'orchard',
        title: "The Orchard at Wren's End",
        author: 'June Okafor',
        status: 'want_to_read',
        rating: null,
    },
] as const;

const sampleStats = [
    { count: 12, unit: 'Books', label: 'completed this year' },
    { count: 3, unit: 'Books', label: "you're reading now" },
    { count: 18, unit: 'Books', label: 'on your Want to Read list' },
];

const name = usePage().props.name;

const year = new Date().getFullYear();
</script>

<template>
    <Head title="Your cozy reading corner" />

    <div class="flex min-h-dvh flex-col bg-background text-foreground">
        <header
            class="mx-auto flex w-full max-w-6xl items-center gap-4 px-4 py-5"
        >
            <Link
                href="/"
                class="flex min-w-0 items-center rounded-lg focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            >
                <AppLogo />
            </Link>
            <nav aria-label="Account" class="ml-auto flex items-center gap-2">
                <Button as-child variant="ghost">
                    <Link :href="login()">Log in</Link>
                </Button>
                <Button as-child>
                    <Link :href="register()">Sign up</Link>
                </Button>
            </nav>
        </header>

        <main class="flex flex-col">
            <section
                aria-labelledby="hero-heading"
                class="mx-auto grid w-full max-w-6xl items-center gap-10 px-4 pt-8 pb-16 md:grid-cols-2 md:pt-12 md:pb-24"
            >
                <div class="flex flex-col items-start gap-6">
                    <h1
                        id="hero-heading"
                        class="text-4xl leading-[1.05] font-bold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                    >
                        A cozy corner for every Book you read.
                    </h1>
                    <p class="max-w-[46ch] text-lg text-muted-foreground">
                        {{ name }} keeps track of what you want to read, what
                        you're reading and everything you've finished. Your
                        Library, your Notes, your reading year, all in one
                        place.
                    </p>
                    <div class="flex flex-wrap gap-3">
                        <Button as-child size="lg" class="h-12 px-6 text-base">
                            <Link :href="register()">
                                Start your Library
                                <ArrowRight aria-hidden="true" />
                            </Link>
                        </Button>
                        <Button
                            as-child
                            size="lg"
                            variant="outline"
                            class="h-12 px-6 text-base"
                        >
                            <Link :href="login()">I already have one</Link>
                        </Button>
                    </div>
                    <p class="text-sm text-muted-foreground">
                        Free to use. No ads, no feeds, just your Books.
                    </p>
                </div>
                <div class="mx-auto w-full max-w-md md:max-w-none">
                    <!-- Hero illustration slot: swap ReadingNook for a sourced illustration later. -->
                    <ReadingNook class="h-auto w-full" />
                </div>
            </section>

            <section
                aria-labelledby="how-heading"
                class="border-y bg-card py-16 md:py-20"
            >
                <div class="mx-auto flex w-full max-w-6xl flex-col gap-10 px-4">
                    <div class="flex flex-col gap-2">
                        <h2
                            id="how-heading"
                            class="text-3xl font-bold tracking-tight"
                        >
                            How it works
                        </h2>
                        <p class="text-muted-foreground">
                            Three steps, and your Library starts filling up.
                        </p>
                    </div>
                    <ol class="grid gap-6 md:grid-cols-3">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.title"
                            class="flex flex-col gap-3 rounded-2xl border bg-background p-6"
                        >
                            <div class="flex items-center gap-3">
                                <span
                                    aria-hidden="true"
                                    class="flex size-11 items-center justify-center rounded-full bg-primary text-primary-foreground"
                                >
                                    <component :is="step.icon" class="size-5" />
                                </span>
                                <span
                                    class="font-display text-sm font-bold text-muted-foreground"
                                >
                                    Step {{ index + 1 }}
                                </span>
                            </div>
                            <h3 class="text-xl font-bold">{{ step.title }}</h3>
                            <p class="text-muted-foreground">{{ step.text }}</p>
                        </li>
                    </ol>
                </div>
            </section>

            <section
                aria-labelledby="peek-heading"
                class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-16 md:py-24"
            >
                <div class="flex flex-col gap-2">
                    <h2
                        id="peek-heading"
                        class="text-3xl font-bold tracking-tight"
                    >
                        A peek inside
                    </h2>
                    <p class="text-muted-foreground">
                        Here's a sample Library with made-up Books. Yours fills
                        up with your own.
                    </p>
                </div>

                <figure
                    class="grid gap-10 rounded-3xl border bg-card p-5 shadow-lg sm:p-8 lg:grid-cols-[1.6fr_1fr] lg:items-center"
                >
                    <figcaption class="sr-only">
                        A sample Library shelf with three made-up Books, and
                        sample reading numbers.
                    </figcaption>

                    <div aria-hidden="true" class="flex flex-col gap-4">
                        <p class="font-display text-xl font-bold">
                            Alex's Library
                        </p>
                        <ul
                            class="grid grid-cols-2 overflow-x-clip sm:grid-cols-3"
                        >
                            <li
                                v-for="book in sampleBooks"
                                :key="book.design"
                                class="flex flex-col max-sm:last:hidden"
                            >
                                <div class="px-2 pt-2 sm:px-4">
                                    <SampleCover
                                        :design="book.design"
                                        class="aspect-[2/3] h-auto w-full rounded-l-sm rounded-r-md shadow-md"
                                    />
                                </div>
                                <div
                                    class="h-3 bg-linear-to-b from-wood from-60% to-wood-edge shadow-[0_10px_12px_-10px_rgb(40_20_5/0.6)]"
                                />
                                <div
                                    class="flex flex-col gap-1 px-2 pt-3 sm:px-4"
                                >
                                    <span
                                        class="line-clamp-2 text-sm leading-snug font-bold"
                                    >
                                        {{ book.title }}
                                    </span>
                                    <span
                                        class="line-clamp-1 text-xs text-muted-foreground"
                                    >
                                        {{ book.author }}
                                    </span>
                                    <ReadingStatusBadge :status="book.status" />
                                    <span
                                        v-if="book.rating"
                                        class="flex gap-0.5"
                                    >
                                        <Star
                                            v-for="star in book.rating"
                                            :key="star"
                                            class="size-3 fill-star text-star"
                                        />
                                    </span>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <div aria-hidden="true" class="flex flex-col gap-4">
                        <p class="font-display text-xl font-bold">
                            Alex's reading at a glance
                        </p>
                        <ul class="flex flex-col gap-3">
                            <li
                                v-for="stat in sampleStats"
                                :key="stat.label"
                                class="flex items-baseline gap-3 rounded-2xl border bg-background px-4 py-3"
                            >
                                <span
                                    class="w-12 shrink-0 font-display text-3xl font-bold tabular-nums"
                                >
                                    {{ stat.count }}
                                </span>
                                <span class="text-muted-foreground">
                                    <span class="font-semibold text-foreground">
                                        {{ stat.unit }}
                                    </span>
                                    {{ stat.label }}
                                </span>
                            </li>
                        </ul>
                    </div>
                </figure>
            </section>

            <section
                aria-labelledby="cta-heading"
                class="mx-auto w-full max-w-6xl px-4 pb-16 md:pb-24"
            >
                <div
                    class="flex flex-col items-start gap-5 rounded-3xl bg-primary p-8 text-primary-foreground sm:p-12 md:flex-row md:items-center md:justify-between"
                >
                    <div class="flex flex-col gap-2">
                        <h2
                            id="cta-heading"
                            class="text-3xl font-bold tracking-tight text-balance"
                        >
                            Your next favourite Book is out there.
                        </h2>
                        <p class="opacity-90">
                            Make some room for it. It takes a minute to sign up.
                        </p>
                    </div>
                    <Button
                        as-child
                        size="lg"
                        variant="secondary"
                        class="h-12 shrink-0 px-6 text-base"
                    >
                        <Link :href="register()">
                            Start your Library
                            <ArrowRight aria-hidden="true" />
                        </Link>
                    </Button>
                </div>
            </section>
        </main>

        <footer class="border-t">
            <div
                class="mx-auto flex w-full max-w-6xl flex-col gap-2 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:items-center sm:justify-between"
            >
                <p>© {{ year }} {{ name }}</p>
                <p>
                    Book details and covers from
                    <a
                        href="https://openlibrary.org"
                        class="font-semibold text-foreground underline underline-offset-4 hover:text-primary"
                        rel="noopener"
                        >OpenLibrary</a
                    >.
                </p>
            </div>
        </footer>
    </div>
</template>
