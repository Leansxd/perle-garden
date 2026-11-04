<script setup lang="ts">
/**
 * Perle Garden logosu (public/media/logo.png, 1024×476, şeffaf).
 * Dosyadaki boş kenarları CSS ile kırpar; yükseklik dışarıdan verilir (class="h-12").
 * Logo dosyası değişirse aşağıdaki kırpma oranlarını güncelleyin.
 */
withDefaults(defineProps<{ src?: string; alt?: string }>(), { src: '/media/logo.png', alt: 'Perle Garden' });

// Görünür alan (px, orijinal görselde): x 70–965, y 155–395
const crop = { x: 70, y: 155, w: 895, h: 240, iw: 1024, ih: 476 };
</script>

<template>
    <span class="relative block overflow-hidden" :style="{ aspectRatio: `${crop.w} / ${crop.h}` }">
        <img
            :src="src"
            :alt="alt"
            class="absolute max-w-none select-none"
            draggable="false"
            :style="{
                width: `${(crop.iw / crop.w) * 100}%`,
                left: `${(-crop.x / crop.w) * 100}%`,
                top: `${(-crop.y / crop.h) * 100}%`,
            }"
        />
    </span>
</template>
