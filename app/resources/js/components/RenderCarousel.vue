<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';
import { prefersReducedMotion } from '@/motion/motion';
import type { MediaItem } from '@/types';

/**
 * Yatay kayan görsel şeridi. Görseller kırpılmaz: her biri kendi oranıyla durur.
 * Kendiliğinden sola kayar; ilk etkileşimde durur, fare üzerindeyken bekler.
 * Dokunmatikte kaydırma, farede sürükleme, her yerde oklar çalışır.
 */
const props = defineProps<{ images: MediaItem[]; title: string }>();
const emit = defineEmits<{ open: [item: MediaItem] }>();

const { t } = useI18n();

const track = ref<HTMLElement | null>(null);
const index = ref(0);
const progress = ref(0);
const dragging = ref(false);
const hovering = ref(false);
let timer: number | undefined;
let dragged = false;

const pad2 = (n: number) => String(n).padStart(2, '0');

function onScroll() {
    const el = track.value;
    if (!el) return;
    const max = el.scrollWidth - el.clientWidth;
    progress.value = max > 0 ? el.scrollLeft / max : 0;
    if (max > 0 && el.scrollLeft >= max - 2) {
        index.value = props.images.length - 1;
        return;
    }
    const start = parseFloat(getComputedStyle(el).paddingLeft);
    let nearest = 0;
    let best = Infinity;
    [...el.children].forEach((item, i) => {
        const distance = Math.abs((item as HTMLElement).offsetLeft - start - el.scrollLeft);
        if (distance < best) {
            best = distance;
            nearest = i;
        }
    });
    index.value = nearest;
}

function goTo(i: number) {
    const el = track.value;
    const item = el?.children[i] as HTMLElement | undefined;
    if (!el || !item) return;
    const left = item.offsetLeft - parseFloat(getComputedStyle(el).paddingLeft);
    el.scrollTo({ left, behavior: prefersReducedMotion() ? 'auto' : 'smooth' });
}

const step = (delta: number) => goTo((index.value + delta + props.images.length) % props.images.length);

function stopAuto() {
    window.clearInterval(timer);
    timer = undefined;
}

/* Farede sürükleyerek kaydırma (dokunmatikte tarayıcının kendi kaydırması çalışır) */
function onPointerDown(e: PointerEvent) {
    const el = track.value;
    if (!el || e.pointerType !== 'mouse' || e.button !== 0) return;
    const startX = e.clientX;
    const startLeft = el.scrollLeft;
    dragged = false;

    const move = (ev: PointerEvent) => {
        const delta = ev.clientX - startX;
        if (!dragged && Math.abs(delta) < 6) return;
        dragged = true;
        dragging.value = true;
        el.scrollLeft = startLeft - delta;
    };
    const up = () => {
        window.removeEventListener('pointermove', move);
        window.removeEventListener('pointerup', up);
        dragging.value = false;
        if (dragged) goTo(index.value);
    };
    window.addEventListener('pointermove', move);
    window.addEventListener('pointerup', up);
}

function onClickCapture(e: MouseEvent) {
    if (!dragged) return;
    dragged = false;
    e.stopPropagation();
    e.preventDefault();
}

onMounted(() => {
    onScroll();
    if (props.images.length > 1 && !prefersReducedMotion()) {
        timer = window.setInterval(() => {
            if (!hovering.value) step(1);
        }, 4500);
    }
});
onBeforeUnmount(stopAuto);
</script>

<template>
    <div @mouseenter="hovering = true" @mouseleave="hovering = false" @pointerdown="stopAuto" @focusin="stopAuto" @wheel.passive="stopAuto">
        <ul
            ref="track"
            class="relative flex items-end gap-4 overflow-x-auto scroll-pl-[max(4%,calc((100vw-1280px)/2))] px-[max(4%,calc((100vw-1280px)/2))] pb-2 [scrollbar-width:none] sm:gap-6"
            :class="dragging ? 'cursor-grabbing select-none' : 'snap-x snap-mandatory'"
            @scroll.passive="onScroll"
            @pointerdown="onPointerDown"
            @click.capture="onClickCapture"
        >
            <li v-for="(m, i) in images" :key="m.id" class="shrink-0 snap-start">
                <figure>
                    <button type="button" class="group block overflow-hidden rounded-(--radius-md)" :data-cursor="t('cursor.view')" :aria-label="m.caption ?? title" @click="emit('open', m)">
                        <img
                            :src="m.url"
                            :alt="m.caption ?? title"
                            draggable="false"
                            class="block h-auto max-h-[44svh] w-auto max-w-[86vw] transition duration-700 ease-(--ease-out-soft) group-hover:scale-[1.03] lg:h-[70svh] lg:max-h-none lg:max-w-none"
                        />
                    </button>
                    <figcaption class="mt-4 flex items-baseline gap-3">
                        <span class="num-italic text-gold-light">{{ pad2(i + 1) }}</span>
                        <span class="font-serif text-lg">{{ m.caption ?? title }}</span>
                    </figcaption>
                </figure>
            </li>
        </ul>

        <div v-if="images.length > 1" class="container-site mt-8 flex items-center gap-5 sm:gap-8">
            <p class="shrink-0 text-sm tracking-[0.12em] text-pearl/75 tabular-nums">{{ pad2(index + 1) }} / {{ pad2(images.length) }}</p>
            <div class="h-px flex-1 bg-pearl/20" aria-hidden="true">
                <div class="h-full origin-left bg-gold" :style="{ transform: `scaleX(${Math.max(progress, 1 / images.length)})` }"></div>
            </div>
            <div class="flex shrink-0 gap-2">
                <button type="button" class="grid size-11 place-items-center rounded-full ring-1 ring-pearl/30 transition hover:bg-gold hover:text-night hover:ring-gold" :aria-label="t('gallery.prev')" @click="step(-1)"><Icon name="arrow-left" :size="18" /></button>
                <button type="button" class="grid size-11 place-items-center rounded-full ring-1 ring-pearl/30 transition hover:bg-gold hover:text-night hover:ring-gold" :aria-label="t('gallery.next')" @click="step(1)"><Icon name="arrow-right" :size="18" /></button>
            </div>
        </div>
    </div>
</template>
