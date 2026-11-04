<script setup lang="ts">
import { onMounted, ref } from 'vue';

/**
 * Etkileşimli 3D model (.glb / .gltf) — Google <model-viewer>.
 * Kütüphane yalnızca bu bileşen ekrana geldiğinde yüklenir (sayfa hızını korur).
 */
defineProps<{ src: string; poster?: string | null; alt: string; arLabel: string }>();

const ready = ref(false);
const progress = ref(0);
const el = ref<HTMLElement | null>(null);

onMounted(() => {
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

function onProgress(e: Event) {
    progress.value = Math.round(((e as CustomEvent).detail?.totalProgress ?? 0) * 100);
}
</script>

<template>
    <div ref="el" class="relative aspect-[16/10] overflow-hidden rounded-(--radius-lg) bg-[radial-gradient(circle_at_50%_40%,#fff,#ece3d3)]">
        <model-viewer
            v-if="ready"
            :src="src"
            :poster="poster ?? undefined"
            :alt="alt"
            camera-controls
            camera-orbit="30deg 58deg 78%"
            min-camera-orbit="auto 0deg 35%"
            max-camera-orbit="auto 88deg 140%"
            interpolation-decay="120"
            auto-rotate
            auto-rotate-delay="1500"
            rotation-per-second="18deg"
            touch-action="pan-y"
            shadow-intensity="1"
            exposure="1.05"
            environment-image="neutral"
            ar
            ar-modes="webxr scene-viewer quick-look"
            class="block size-full"
            @progress="onProgress"
        >
            <button slot="ar-button" type="button" class="btn absolute right-4 bottom-4 px-5 py-3">{{ arLabel }}</button>
            <div v-if="progress < 100" slot="progress-bar" class="absolute inset-x-0 top-0 h-[2px] bg-gold/20">
                <div class="h-full bg-gold transition-[width]" :style="{ width: `${progress}%` }"></div>
            </div>
        </model-viewer>
        <div v-else class="grid size-full place-items-center text-gold">
            <span class="size-10 animate-spin rounded-full border-2 border-gold/30 border-t-gold"></span>
        </div>
    </div>
</template>
