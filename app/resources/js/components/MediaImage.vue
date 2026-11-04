<script setup lang="ts">
import { computed, ref, watch } from 'vue';

/**
 * Görsel gösterir; görsel yoksa ya da yüklenemezse sıcak tonlu,
 * mimari silüetli bir yer tutucu çizer. Gerçek görsel geldiğinde
 * sadece `src` doldurulur.
 */
const props = withDefaults(
    defineProps<{ src?: string | null; alt?: string; seed?: string; label?: boolean; zoom?: boolean }>(),
    { src: null, alt: '', seed: 'x', label: true, zoom: true },
);

const failed = ref(false);
watch(
    () => props.src,
    () => (failed.value = false),
);
const showImage = computed(() => !!props.src && !failed.value);

const variant = computed(() => {
    let h = 0;
    for (const c of props.seed) h = (h * 31 + c.charCodeAt(0)) >>> 0;
    const palettes = [
        ['#17262b', '#2d4a4f'], // gece turkuazı
        ['#c99555', '#7a5a33'], // altın gün batımı
        ['#2b3a2f', '#55704f'], // bahçe yeşili
        ['#1d211f', '#3c4a48'], // gece
        ['#d9b98a', '#a9774a'], // kum
    ];
    return { colors: palettes[h % palettes.length], flip: h % 2 === 0 };
});
</script>

<template>
    <div class="relative overflow-hidden bg-night">
        <img
            v-if="showImage"
            :src="src!"
            :alt="alt"
            loading="lazy"
            class="absolute inset-0 size-full object-cover transition duration-500"
            :class="zoom && 'group-hover:scale-[1.03]'"
            @error="failed = true"
        />
        <div
            v-else
            class="absolute inset-0 transition duration-500"
            :class="zoom && 'group-hover:scale-[1.03]'"
            :style="{ background: `linear-gradient(160deg, ${variant.colors[0]}, ${variant.colors[1]})` }"
            role="img"
            :aria-label="alt || 'Görsel yer tutucu'"
        >
            <!-- Güneş -->
            <div class="absolute top-[14%] size-24 rounded-full bg-white/15 blur-sm" :class="variant.flip ? 'right-[16%]' : 'left-[18%]'"></div>
            <!-- Stilize rezidans silüeti + palmiyeler -->
            <svg
                class="absolute inset-x-0 bottom-0 h-2/3 w-full"
                :class="variant.flip && '-scale-x-100'"
                viewBox="0 0 400 220"
                preserveAspectRatio="xMidYMax slice"
                aria-hidden="true"
            >
                <g fill="none" stroke="#fff" stroke-opacity=".22" stroke-width="1.2">
                    <path d="M40 220V120h120v100M60 140h20M100 140h20M140 140h10M60 165h20M100 165h20M60 190h20M100 190h20M30 120h140" />
                    <path d="M190 220V90h150v130M210 110h22M250 110h22M290 110h22M210 135h22M250 135h22M290 135h22M210 160h22M250 160h22M290 160h22M180 90h170" />
                    <path d="M360 220v-70M360 150c-14-6-26-2-34 6M360 150c14-8 28-6 36 2M360 150c-4-12-14-18-24-18M360 150c6-12 16-16 26-14" />
                    <path d="M18 220v-56M18 164c-10-4-20 0-26 6M18 164c10-6 22-4 28 2M18 164c-2-10-10-14-18-14" />
                    <path d="M0 214c60-6 120-6 200 0s140 6 200 0" stroke-opacity=".35" />
                </g>
            </svg>
            <div class="absolute inset-0 bg-linear-to-t from-black/35 via-transparent to-transparent"></div>
            <span
                v-if="label"
                class="absolute right-3 bottom-3 rounded-full bg-black/25 px-3 py-1 text-[10px] font-bold tracking-[0.16em] text-white/75 uppercase backdrop-blur-sm"
            >
                Görsel eklenecek
            </span>
        </div>
        <slot />
    </div>
</template>
