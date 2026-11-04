<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import { gsap, prefersReducedMotion } from '@/motion/motion';

/**
 * Masaüstü özel imleç: altın nokta + gecikmeli halka.
 * Link/butonlarda halka büyür; [data-cursor="Metin"] öğelerde etiket gösterir.
 */
const enabled = ref(false);
const label = ref('');
const dot = ref<HTMLElement | null>(null);
const ring = ref<HTMLElement | null>(null);
let off: (() => void) | null = null;

onMounted(() => {
    if (prefersReducedMotion() || !window.matchMedia('(pointer: fine)').matches) return;
    enabled.value = true;
    document.documentElement.classList.add('has-cursor');

    requestAnimationFrame(() => {
        if (!dot.value || !ring.value) return;
        gsap.set([dot.value, ring.value], { autoAlpha: 0 });
        let shown = false;
        const dx = gsap.quickTo(dot.value, 'x', { duration: 0.12, ease: 'power3' });
        const dy = gsap.quickTo(dot.value, 'y', { duration: 0.12, ease: 'power3' });
        const rx = gsap.quickTo(ring.value, 'x', { duration: 0.5, ease: 'power3' });
        const ry = gsap.quickTo(ring.value, 'y', { duration: 0.5, ease: 'power3' });

        const move = (e: PointerEvent) => {
            if (!shown) {
                shown = true;
                gsap.set([dot.value, ring.value], { x: e.clientX, y: e.clientY });
                gsap.to([dot.value, ring.value], { autoAlpha: 1, duration: 0.3 });
            }
            dx(e.clientX);
            dy(e.clientY);
            rx(e.clientX);
            ry(e.clientY);
        };
        const over = (e: PointerEvent) => {
            const t = (e.target as HTMLElement).closest<HTMLElement>('[data-cursor], a, button, select, input, textarea');
            const text = t?.dataset.cursor ?? '';
            label.value = text;
            gsap.to(ring.value, { scale: text ? 2.6 : t ? 1.7 : 1, duration: 0.4, ease: 'power3.out' });
            gsap.to(dot.value, { scale: t ? 0 : 1, duration: 0.3 });
        };
        const leaveWin = () => gsap.to([dot.value, ring.value], { autoAlpha: 0, duration: 0.3 });
        const enterWin = () => gsap.to([dot.value, ring.value], { autoAlpha: 1, duration: 0.3 });

        window.addEventListener('pointermove', move);
        document.addEventListener('pointerover', over);
        document.documentElement.addEventListener('pointerleave', leaveWin);
        document.documentElement.addEventListener('pointerenter', enterWin);
        off = () => {
            window.removeEventListener('pointermove', move);
            document.removeEventListener('pointerover', over);
            document.documentElement.removeEventListener('pointerleave', leaveWin);
            document.documentElement.removeEventListener('pointerenter', enterWin);
        };
    });
});
onBeforeUnmount(() => {
    off?.();
    document.documentElement.classList.remove('has-cursor');
});
</script>

<template>
    <template v-if="enabled">
        <div ref="dot" class="pointer-events-none fixed top-0 left-0 z-[300] -mt-1 -ml-1 size-2 rounded-full bg-gold mix-blend-difference" aria-hidden="true"></div>
        <div
            ref="ring"
            class="pointer-events-none fixed top-0 left-0 z-[300] -mt-5 -ml-5 grid size-10 place-items-center rounded-full border border-gold/70 backdrop-invert-[.04]"
            aria-hidden="true"
        >
            <span v-if="label" class="text-[5px] font-medium tracking-[0.2em] text-gold uppercase">{{ label }}</span>
        </div>
    </template>
</template>
