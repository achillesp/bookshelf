<script setup lang="ts">
/**
 * Covers for made-up Books on the homepage's sample shelf. Like printed
 * covers, their colours stay the same in light and dark mode.
 */
defineProps<{
    design: 'lanterns' | 'boats' | 'orchard';
}>();
</script>

<template>
    <svg
        viewBox="0 0 120 180"
        xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true"
        focusable="false"
        class="font-display"
    >
        <template v-if="design === 'lanterns'">
            <rect width="120" height="180" fill="#1f3a5f" />
            <rect y="112" width="120" height="68" fill="#2b5079" />
            <circle cx="92" cy="88" r="11" fill="#f6dfaa" />
            <path
                d="M0 128c15-5 25 5 40 0s25-5 40 0 25 5 40 0M0 146c15-5 25 5 40 0s25-5 40 0 25 5 40 0"
                fill="none"
                stroke="#5f86b0"
                stroke-width="2"
            />
            <g fill="#f2b45a">
                <circle cx="24" cy="98" r="9" opacity="0.25" />
                <rect x="19" y="90" width="10" height="15" rx="3" />
                <circle cx="56" cy="104" r="8" opacity="0.25" />
                <rect x="52" y="97" width="8" height="13" rx="3" />
            </g>
            <path d="M24 90V80M56 97v-9" stroke="#f6dfaa" stroke-width="1.5" />
            <text x="12" y="26" fill="#fdf3e1" font-size="15" font-weight="700">
                <tspan x="12">Lanterns</tspan>
                <tspan x="12" dy="17">Over</tspan>
                <tspan x="12" dy="17">Kettle Bay</tspan>
            </text>
            <text
                x="12"
                y="168"
                fill="#c9d8ea"
                font-size="9"
                font-family="Nunito, sans-serif"
            >
                Mara Ellison
            </text>
        </template>

        <template v-else-if="design === 'boats'">
            <rect width="120" height="180" fill="#b4532f" />
            <g fill="#fbe6c8">
                <circle cx="20" cy="80" r="1.6" />
                <circle cx="42" cy="74" r="1.2" />
                <circle cx="92" cy="78" r="1.8" />
                <circle cx="104" cy="94" r="1.3" />
                <circle cx="28" cy="98" r="1.1" />
                <path d="M52 112h30l-5 10H57z" />
                <path d="M66 110V82l14 26z" />
                <path d="M64 110V88l-12 20z" opacity="0.85" />
            </g>
            <path
                d="M0 128c15-6 25 6 40 0s25-6 40 0 25 6 40 0M0 144c15-6 25 6 40 0s25-6 40 0 25 6 40 0"
                fill="none"
                stroke="#e98a62"
                stroke-width="2.5"
            />
            <text x="12" y="26" fill="#fff4e4" font-size="15" font-weight="700">
                <tspan x="12">Salt, Stars</tspan>
                <tspan x="12" dy="17">&amp; Small</tspan>
                <tspan x="12" dy="17">Boats</tspan>
            </text>
            <text
                x="12"
                y="168"
                fill="#fbd9c2"
                font-size="9"
                font-family="Nunito, sans-serif"
            >
                Theo Marchetti
            </text>
        </template>

        <template v-else>
            <rect width="120" height="180" fill="#4f6b42" />
            <rect y="130" width="120" height="50" fill="#3f5735" />
            <g>
                <rect x="28" y="108" width="4" height="26" fill="#6b4a36" />
                <circle cx="30" cy="100" r="16" fill="#8daa6e" />
                <rect x="66" y="112" width="4" height="22" fill="#6b4a36" />
                <circle cx="68" cy="104" r="13" fill="#a7c495" />
                <rect x="96" y="106" width="4" height="28" fill="#6b4a36" />
                <circle cx="98" cy="96" r="17" fill="#8daa6e" />
                <g fill="#e2725b">
                    <circle cx="24" cy="98" r="2.5" />
                    <circle cx="35" cy="106" r="2.5" />
                    <circle cx="64" cy="102" r="2.2" />
                    <circle cx="94" cy="92" r="2.5" />
                    <circle cx="104" cy="102" r="2.5" />
                </g>
            </g>
            <path
                d="M48 70q4-4 8 0q4-4 8 0"
                fill="none"
                stroke="#fdf3e1"
                stroke-width="1.5"
            />
            <text x="12" y="26" fill="#fdf3e1" font-size="15" font-weight="700">
                <tspan x="12">The Orchard</tspan>
                <tspan x="12" dy="17">at Wren's</tspan>
                <tspan x="12" dy="17">End</tspan>
            </text>
            <text
                x="12"
                y="168"
                fill="#d6e4c9"
                font-size="9"
                font-family="Nunito, sans-serif"
            >
                June Okafor
            </text>
        </template>
    </svg>
</template>
