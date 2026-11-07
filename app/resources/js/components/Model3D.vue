<script setup lang="ts">
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Icon from '@/components/Icon.vue';
import { useI18n } from '@/composables/useI18n';
import { lockScroll, prefersReducedMotion } from '@/motion/motion';

/**
 * Etkileşimli 3D model (.glb / .gltf) — Google <model-viewer>.
 * Kütüphane yalnızca bu bileşen ekrana geldiğinde yüklenir (sayfa hızını korur).
 * Sayfa içinde tekerlek yakınlaştırması kapalıdır (kaydırmayı yakalamasın); tam ekranda açılır.
 */
export interface ModelHotspot {
    id: string;
    label: string;
    position: [number, number, number];
}

const props = defineProps<{ src: string; alt: string; arLabel?: string; fill?: boolean; hotspots?: ModelHotspot[] }>();
const emit = defineEmits<{ hotspot: [id: string, event: MouseEvent] }>();

const { t } = useI18n();

const views = {
    overall: '30deg 50deg 80%',
    top: '4deg 4deg 74%', // model dosyası hafif açılı geliyor; duvarları ekrana paralel tutar
    close: '32deg 42deg 38%',
} as const;
type View = keyof typeof views;

const ready = ref(false);
const progress = ref(0);
const view = ref<View>('overall');
const rotating = ref(true);
const expanded = ref(false);
const el = ref<HTMLElement | null>(null);
const viewer = ref<
    (HTMLElement & { cameraOrbit: string; cameraTarget: string; resetTurntableRotation: (theta?: number) => void; getCameraOrbit: () => { theta: number; phi: number; radius: number } }) | null
>(null);

async function setView(v: View) {
    view.value = v;
    rotating.value = false;
    await nextTick(); // auto-rotate kapanmadan sıfırlanırsa model açılı kalır
    viewer.value?.resetTurntableRotation(0);
    if (viewer.value) viewer.value.cameraOrbit = views[v];
}

/* Oda noktasına kamerayla yaklaşır; hareket bitince döner */
function flyTo([x, y, z]: ModelHotspot['position']) {
    const v = viewer.value;
    if (!v) return Promise.resolve();
    rotating.value = false;
    const { theta } = v.getCameraOrbit();
    v.cameraTarget = `${x}m ${y}m ${z}m`;
    v.cameraOrbit = `${theta}rad 48deg 30%`;
    return new Promise<void>((resolve) => window.setTimeout(resolve, prefersReducedMotion() ? 0 : 900));
}

function resetCamera() {
    if (viewer.value) viewer.value.cameraTarget = 'auto auto auto';
    setView(view.value);
}

defineExpose({ flyTo, resetCamera });

function onHotspot(id: string, e: MouseEvent) {
    expanded.value = false;
    emit('hotspot', id, e);
}

/* Sürüklemeye alternatif: oklarla 45° sola / sağa döndürme */
function rotate(deg: number) {
    const v = viewer.value;
    if (!v) return;
    rotating.value = false;
    const { theta, phi, radius } = v.getCameraOrbit();
    v.cameraOrbit = `${theta + (deg * Math.PI) / 180}rad ${phi}rad ${radius}m`;
}

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape') expanded.value = false;
}

watch(expanded, (open) => {
    lockScroll(open);
    if (open) window.addEventListener('keydown', onKey);
    else window.removeEventListener('keydown', onKey);
});

onMounted(() => {
    rotating.value = !prefersReducedMotion() && !props.hotspots?.length; // dönen noktaya tıklamak zor
    const io = new IntersectionObserver(
        async ([e]) => {
            if (!e.isIntersecting) return;
            io.disconnect();
            const { ModelViewerElement } = await import('@google/model-viewer');
            // Sıkıştırılmış (gltfpack / meshopt) modeller için çözücü — siteyle birlikte sunulur
            ModelViewerElement.meshoptDecoderLocation = '/vendor/meshopt_decoder.js';
            ready.value = true;
        },
        { rootMargin: '300px' },
    );
    if (el.value) io.observe(el.value);
});

onBeforeUnmount(() => {
    if (expanded.value) lockScroll(false);
    window.removeEventListener('keydown', onKey);
});

function onProgress(e: Event) {
    progress.value = Math.round(((e as CustomEvent).detail?.totalProgress ?? 0) * 100);
}
</script>

<template>
    <div
        ref="el"
        class="overflow-hidden bg-night bg-[radial-gradient(circle_at_50%_50%,var(--color-night-700),var(--color-night)_72%)] text-pearl"
        :class="expanded ? 'fixed inset-0 z-[70]' : fill ? 'relative size-full' : 'relative h-[88svh] min-h-[520px] lg:h-svh'"
    >
        <model-viewer
            v-if="ready"
            ref="viewer"
            :src="src"
            :alt="alt"
            camera-controls
            disable-tap
            disable-pan
            :camera-orbit="views.overall"
            min-camera-orbit="auto 0deg 22%"
            max-camera-orbit="auto 88deg 110%"
            interpolation-decay="120"
            :auto-rotate="rotating ? '' : undefined"
            auto-rotate-delay="1500"
            rotation-per-second="14deg"
            :disable-zoom="expanded ? undefined : ''"
            touch-action="pan-y"
            shadow-intensity="1.2"
            exposure="1.1"
            environment-image="neutral"
            :ar="arLabel ? '' : undefined"
            ar-modes="webxr scene-viewer quick-look"
            class="block size-full [--min-hotspot-opacity:1]"
            @progress="onProgress"
        >
            <button v-if="arLabel" slot="ar-button" type="button" class="btn absolute right-4 bottom-24 px-5 py-3">{{ arLabel }}</button>
            <button
                v-for="h in hotspots"
                :key="h.id"
                :slot="`hotspot-${h.id}`"
                type="button"
                :aria-label="h.label"
                :data-position="`${h.position[0]}m ${h.position[1]}m ${h.position[2]}m`"
                data-normal="0m 1m 0m"
                class="flex min-h-9 items-center gap-2 rounded-full border border-white/25 bg-[#0a0a0a]/80 px-3 text-[12px] whitespace-nowrap text-pearl backdrop-blur-sm transition hover:bg-gold hover:text-night"
                @click="onHotspot(h.id, $event)"
            >
                <span class="size-2 rounded-full bg-gold-light"></span><span class="max-sm:hidden">{{ h.label }}</span>
            </button>
            <div slot="progress-bar"></div>
        </model-viewer>

        <div v-if="!ready || progress < 100" class="pointer-events-none absolute inset-0 grid place-items-center" role="status">
            <span class="grid justify-items-center gap-4 text-gold-light">
                <span class="size-10 animate-spin rounded-full border-2 border-gold/30 border-t-gold"></span>
                <span v-if="ready" class="text-sm tabular-nums">%{{ progress }}</span>
            </span>
        </div>

        <template v-if="ready">
            <button type="button" class="absolute top-1/2 left-3 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-black/45 text-pearl ring-1 ring-white/15 backdrop-blur-md transition hover:bg-gold hover:text-night sm:left-6" :aria-label="t('unit.rotate_left')" @click="rotate(-45)">
                <Icon name="chevron-left" :size="20" />
            </button>
            <button type="button" class="absolute top-1/2 right-3 grid size-12 -translate-y-1/2 place-items-center rounded-full bg-black/45 text-pearl ring-1 ring-white/15 backdrop-blur-md transition hover:bg-gold hover:text-night sm:right-6" :aria-label="t('unit.rotate_right')" @click="rotate(45)">
                <Icon name="chevron-right" :size="20" />
            </button>
        </template>

        <div class="absolute inset-x-0 bottom-6 flex justify-center px-4">
            <div class="flex items-center gap-1 rounded-full bg-black/45 p-1.5 ring-1 ring-white/15 backdrop-blur-md">
                <button
                    v-for="v in (['overall', 'top', 'close'] as const)"
                    :key="v"
                    type="button"
                    class="min-h-11 rounded-full px-4 text-[13px] tracking-[0.08em] transition sm:px-6"
                    :class="view === v ? 'bg-gold text-night' : 'text-pearl/80 hover:text-pearl'"
                    :aria-pressed="view === v"
                    @click="setView(v)"
                >
                    {{ t(`unit.view_${v}`) }}
                </button>
                <button
                    type="button"
                    class="grid size-11 place-items-center rounded-full text-pearl/80 transition hover:text-pearl"
                    :aria-label="expanded ? t('gallery.close') : t('unit.fullscreen')"
                    @click="expanded = !expanded"
                >
                    <Icon :name="expanded ? 'x' : 'expand'" :size="18" />
                </button>
            </div>
        </div>
    </div>
</template>
