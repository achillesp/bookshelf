<script setup lang="ts">
import { useId } from 'vue';

/**
 * The in-house reading-nook illustration: two shelves of Books, a plant, a mug
 * and a desk lamp. Drawn only with theme tokens, so it follows light and dark.
 * It is the placeholder for the homepage hero slot and decorates auth screens.
 */
const glowId = `nook-glow-${useId()}`;
</script>

<template>
    <svg
        viewBox="0 0 480 380"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        focusable="false"
    >
        <defs>
            <radialGradient :id="glowId" cx="0.5" cy="0.5" r="0.5">
                <stop
                    offset="0"
                    stop-color="var(--mustard)"
                    stop-opacity="0.55"
                />
                <stop offset="1" stop-color="var(--mustard)" stop-opacity="0" />
            </radialGradient>
        </defs>

        <circle cx="240" cy="200" r="172" fill="var(--accent)" />
        <circle cx="372" cy="214" r="92" :fill="`url(#${glowId})`" />

        <!-- Hand-drawn sparkles -->
        <g
            fill="none"
            stroke="var(--muted-foreground)"
            stroke-width="2"
            stroke-linecap="round"
            opacity="0.7"
        >
            <path d="M70 70v14M63 77h14" />
            <path d="M420 58v10M415 63h10" />
            <path d="M438 140v8M434 144h8" />
        </g>

        <!-- Upper shelf: standing Books and a plant -->
        <rect
            x="92"
            y="72"
            width="22"
            height="78"
            rx="3"
            fill="var(--primary)"
        />
        <rect
            x="116"
            y="84"
            width="18"
            height="66"
            rx="3"
            fill="var(--chart-2)"
        />
        <rect
            x="136"
            y="66"
            width="26"
            height="84"
            rx="3"
            fill="var(--chart-5)"
        />
        <rect
            x="164"
            y="80"
            width="16"
            height="70"
            rx="3"
            fill="var(--mustard)"
        />
        <rect
            x="188"
            y="78"
            width="20"
            height="72"
            rx="3"
            fill="var(--chart-4)"
            transform="rotate(14 198 150)"
        />
        <g fill="var(--card)" opacity="0.55">
            <rect x="96" y="84" width="14" height="3" rx="1.5" />
            <rect x="96" y="132" width="14" height="3" rx="1.5" />
            <rect x="140" y="80" width="18" height="3" rx="1.5" />
            <rect x="140" y="88" width="18" height="3" rx="1.5" />
            <rect x="119" y="96" width="12" height="3" rx="1.5" />
        </g>

        <g>
            <ellipse
                cx="338"
                cy="96"
                rx="12"
                ry="26"
                fill="var(--chart-2)"
                transform="rotate(-28 338 96)"
            />
            <ellipse
                cx="364"
                cy="92"
                rx="12"
                ry="28"
                fill="var(--secondary)"
                transform="rotate(22 364 92)"
            />
            <ellipse cx="351" cy="84" rx="10" ry="30" fill="var(--chart-2)" />
            <path d="M330 118h44l-6 32h-32z" fill="var(--primary)" />
            <rect
                x="327"
                y="114"
                width="50"
                height="8"
                rx="3"
                fill="var(--wood-edge)"
            />
        </g>

        <rect
            x="60"
            y="150"
            width="360"
            height="12"
            rx="3"
            fill="var(--wood)"
        />
        <rect
            x="60"
            y="159"
            width="360"
            height="5"
            rx="2"
            fill="var(--wood-edge)"
        />

        <!-- Lower shelf: a stack of Books, a mug and a desk lamp -->
        <rect
            x="96"
            y="230"
            width="104"
            height="16"
            rx="3"
            fill="var(--primary)"
        />
        <rect
            x="108"
            y="246"
            width="96"
            height="16"
            rx="3"
            fill="var(--mustard)"
        />
        <rect
            x="100"
            y="262"
            width="110"
            height="18"
            rx="3"
            fill="var(--chart-5)"
        />
        <g fill="var(--card)" opacity="0.55">
            <rect x="104" y="236" width="3" height="5" rx="1.5" />
            <rect x="192" y="251" width="3" height="5" rx="1.5" />
            <rect x="108" y="268" width="3" height="6" rx="1.5" />
        </g>

        <g>
            <path
                d="M258 222c-6-6 6-10 0-18M272 222c-6-7 6-12 0-21"
                fill="none"
                stroke="var(--muted-foreground)"
                stroke-width="3"
                stroke-linecap="round"
            />
            <path
                d="M290 246h6a10 10 0 0 1 0 20h-6"
                fill="none"
                stroke="var(--secondary-foreground)"
                stroke-width="5"
            />
            <rect
                x="246"
                y="230"
                width="46"
                height="50"
                rx="9"
                fill="var(--secondary)"
            />
            <rect
                x="254"
                y="246"
                width="30"
                height="5"
                rx="2.5"
                fill="var(--primary)"
                opacity="0.8"
            />
        </g>

        <g>
            <ellipse cx="372" cy="277" rx="26" ry="6" fill="var(--wood-edge)" />
            <path
                d="M372 276l-18-56 34-24"
                fill="none"
                stroke="var(--foreground)"
                stroke-width="5"
                stroke-linecap="round"
                stroke-linejoin="round"
            />
            <path d="M378 188l34-14 10 30-38 6z" fill="var(--mustard)" />
            <circle cx="354" cy="220" r="5" fill="var(--foreground)" />
        </g>

        <rect
            x="60"
            y="280"
            width="360"
            height="12"
            rx="3"
            fill="var(--wood)"
        />
        <rect
            x="60"
            y="289"
            width="360"
            height="5"
            rx="2"
            fill="var(--wood-edge)"
        />
    </svg>
</template>
