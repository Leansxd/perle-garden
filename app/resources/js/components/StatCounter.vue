<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import type { StatItem } from '@/types';
import { useI18n } from '@/composables/useI18n';

/** Görünür olduğunda sıfırdan hedef değere sayan istatistik. */
const props = defineProps<{ stat: StatItem }>();

const el = ref<HTMLElement | null>(null);
const shown = ref(0);
let io: IntersectionObserver | null = null;

const { numberFormat: format } = useI18n();

function run() {
    const target = props.stat.value;
    const start = performance.now();
    const tick = (now: number) => {
        const p = Math.min(1, (now - start) / 1500);
        shown.value = Math.round(target * (1 - Math.pow(1 - p, 3)));
        if (p < 1) requestAnimationFrame(tick);
    };
    requestAnimationFrame(tick);
}

onMounted(() => {
    if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        shown.value = props.stat.value;
        return;
    }
    io = new IntersectionObserver(([e]) => {
        if (e.isIntersecting) {
            run();
            io?.disconnect();
        }
    });
    if (el.value) io.observe(el.value);
});
onBeforeUnmount(() => io?.disconnect());
</script>

<template>
    <div ref="el">
        <strong class="block font-serif text-3xl whitespace-nowrap text-gold-dark tabular-nums sm:text-[40px]">
            {{ stat.prefix }}{{ format(shown) }}{{ stat.suffix }}
        </strong>
        <span class="mt-1.5 block text-[13px] text-muted">{{ stat.label }}</span>
    </div>
</template>
