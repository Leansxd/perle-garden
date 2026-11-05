<script setup lang="ts">
import Icon from './Icon.vue';
import MediaImage from './MediaImage.vue';
import { useI18n } from '@/composables/useI18n';
import type { UnitType } from '@/types';

/** Daire tipi kartı — daire sayfasına gider. featured: ızgaradaki büyük kart. */
defineProps<{ unit: UnitType; index: number; featured?: boolean }>();
const { t, unitUrl } = useI18n();
</script>

<template>
    <a
        :href="unitUrl(unit.slug)"
        class="group flex h-full flex-col overflow-hidden rounded-(--radius-lg) border border-line bg-white text-left transition-[border-color,box-shadow,transform] duration-500 hover:-translate-y-1 hover:border-gold/50 hover:shadow-[0_30px_60px_-20px_rgba(176,124,63,.28)]"
        :data-cursor="t('unit.view')"
    >
        <div class="relative overflow-hidden" :class="featured ? 'aspect-[16/10]' : 'aspect-[4/3]'">
            <MediaImage
                :src="unit.cover_image"
                :seed="unit.slug"
                :alt="unit.name"
                class="size-full transition-transform duration-[1.2s] ease-(--ease-expo) group-hover:scale-105"
                :label="false"
                :zoom="false"
            />
            <span class="absolute top-4 left-4 rounded-full bg-white/90 px-3 py-1.5 text-[12px] font-medium tracking-[0.12em] text-gold-deep uppercase backdrop-blur-sm">
                {{ t('units.count', { count: unit.unit_count }) }}
            </span>
        </div>

        <div class="flex flex-1 flex-col gap-5 p-6 sm:p-7">
            <div class="flex items-end justify-between gap-4">
                <h3 class="flex flex-wrap items-baseline gap-x-3">
                    <span class="font-serif leading-none transition-colors duration-500 group-hover:text-gold-dark" :class="featured ? 'text-[56px]' : 'text-[44px]'">{{ unit.name }}</span>
                    <span v-if="unit.label" class="num-italic text-xl text-gold-dark">{{ unit.label }}</span>
                </h3>
                <span class="num-italic shrink-0 text-lg text-gold/70">0{{ index + 1 }}</span>
            </div>

            <div class="mt-auto flex items-center justify-between gap-4 border-t border-line pt-5">
                <span class="text-[15px] text-muted">{{ unit.sizes }}</span>
                <span
                    class="grid size-10 shrink-0 place-items-center rounded-full border border-ink/15 transition duration-500 group-hover:border-gold group-hover:bg-gold group-hover:text-white"
                    :aria-label="t('unit.view')"
                >
                    <Icon name="arrow-right" :size="15" />
                </span>
            </div>
        </div>
    </a>
</template>
