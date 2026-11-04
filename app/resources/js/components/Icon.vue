<script setup lang="ts">
import { computed } from 'vue';

/** Lucide tarzı, 24px ızgaralı, 2px çizgili hafif ikon seti. */
const props = withDefaults(defineProps<{ name: string; size?: number | string; stroke?: number }>(), {
    size: 24,
    stroke: 2,
});

const paths: Record<string, string> = {
    home: 'M3 10.5 12 3l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1z',
    building: 'M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16M16 9h2a2 2 0 0 1 2 2v10M2 21h20M8 7h4M8 11h4M8 15h4',
    road: 'M4 21 8 3M20 21 16 3M12 4v2M12 10v3M12 17v3',
    factory: 'M2 21V10l6 4V10l6 4V6l8 4v11zM2 21h20M7 17h1M12 17h1M17 17h1',
    clipboard: 'M9 4h6a1 1 0 0 1 1 1v1H8V5a1 1 0 0 1 1-1zM8 5H6a2 2 0 0 0-2 2v13a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 13l2 2 4-4',
    shield: 'M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6zM9 12l2 2 4-4',
    'arrow-right': 'M5 12h14M13 6l6 6-6 6',
    'arrow-up-right': 'M7 17 17 7M8 7h9v9',
    'arrow-left': 'M19 12H5M11 6l-6 6 6 6',
    menu: 'M4 7h16M4 12h16M4 17h16',
    x: 'M6 6l12 12M18 6 6 18',
    phone: 'M5 4h4l2 5-2.5 1.5a11 11 0 0 0 5 5L15 13l5 2v4a2 2 0 0 1-2 2A16 16 0 0 1 3 6a2 2 0 0 1 2-2z',
    mail: 'M4 6h16a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V7a1 1 0 0 1 1-1zM3 7l9 6 9-6',
    pin: 'M12 21s-7-6.2-7-12a7 7 0 0 1 14 0c0 5.8-7 12-7 12zM12 11.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5z',
    clock: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 7v5l3 2',
    check: 'M5 12.5 10 17 19 7',
    play: 'M8 5v14l11-7z',
    pause: 'M8 5v14M16 5v14',
    'chevron-down': 'M6 9l6 6 6-6',
    calendar: 'M4 6a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v14a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1zM4 10h16M8 3v4M16 3v4',
    ruler: 'M3 17 17 3l4 4L7 21zM7 13l2 2M10 10l2 2M13 7l2 2',
    user: 'M12 12a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM4 21a8 8 0 0 1 16 0',
    leaf: 'M5 21c0-9 5-15 16-16-1 11-7 16-16 16zM5 21l8-8',
    hardhat: 'M3 18h18M4 18v-3a8 8 0 0 1 16 0v3M10 7V5h4v2M12 7v5',
    target: 'M12 21a9 9 0 1 0 0-18 9 9 0 0 0 0 18zM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM12 12h.01',
    linkedin: 'M4 9h4v11H4zM6 4.5a2 2 0 1 1 0 4 2 2 0 0 1 0-4zM10 9h3.8v1.6c.6-1 1.9-1.9 3.7-1.9 3.3 0 3.5 2.2 3.5 5V20h-4v-5c0-1.3 0-2.9-1.8-2.9s-2.1 1.4-2.1 2.8V20H10z',
    instagram: 'M7 3h10a4 4 0 0 1 4 4v10a4 4 0 0 1-4 4H7a4 4 0 0 1-4-4V7a4 4 0 0 1 4-4zM12 16a4 4 0 1 0 0-8 4 4 0 0 0 0 8zM17.5 6.5h.01',
    whatsapp: 'M12 3a9 9 0 0 0-7.8 13.5L3 21l4.6-1.2A9 9 0 1 0 12 3zM8.5 8.2c.3-.6.6-.6 1-.6h.6c.2 0 .4.1.5.4l.8 1.9c.1.2 0 .5-.1.6l-.6.7c-.1.1-.1.3 0 .5a7 7 0 0 0 3.3 2.9c.2.1.4 0 .5-.1l.8-.9c.2-.2.4-.2.6-.1l1.8.9c.2.1.3.3.3.5 0 .6-.3 1.6-1.4 2-1 .4-2.6.2-4.7-1.2a10 10 0 0 1-3.6-4.3c-.5-1.2-.3-2.4.2-3.2z',
    sparkle: 'M12 3v4M12 17v4M3 12h4M17 12h4M6 6l2.5 2.5M15.5 15.5 18 18M6 18l2.5-2.5M15.5 8.5 18 6',
    'chevron-left': 'M15 6l-6 6 6 6',
    'chevron-right': 'M9 6l6 6-6 6',
    plan: 'M3 4h18v16H3zM3 10h7v10M14 4v8h7M10 14h4',
    youtube:'M3 8.5C3 6.6 4.5 5 6.5 5h11C19.5 5 21 6.6 21 8.5v7c0 1.9-1.5 3.5-3.5 3.5h-11C4.5 19 3 17.4 3 15.5zM10 9v6l5-3z',
};

const d = computed(() => paths[props.name] ?? paths.building);
const filled = computed(() => ['play', 'linkedin', 'whatsapp'].includes(props.name));
</script>

<template>
    <svg
        :width="size"
        :height="size"
        viewBox="0 0 24 24"
        :fill="filled ? 'currentColor' : 'none'"
        stroke="currentColor"
        :stroke-width="stroke"
        stroke-linecap="round"
        stroke-linejoin="round"
        fill-rule="evenodd"
        aria-hidden="true"
    >
        <path :d="d" />
    </svg>
</template>
