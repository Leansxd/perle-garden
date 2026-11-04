<script setup lang="ts">
import Icon from './Icon.vue';
import MediaImage from './MediaImage.vue';
import { useI18n } from '@/composables/useI18n';
import type { UnitType } from '@/types';

/** Daire tipi kartı — daire sayfasına gider. Kapak görseli varsa üstte gösterilir. */
defineProps<{ unit: UnitType; index: number }>();
const { t, unitUrl } = useI18n();
</script>

<template>
    <a
        v-tilt="6"
        :href="unitUrl(unit.slug)"
        class="glow-card group flex min-h-[360px] flex-col overflow-hidden rounded-(--radius-md) border border-line bg-white text-left transition-[border-color,box-shadow] duration-500 hover:border-gold/60 hover:shadow-[0_30px_60px_rgba(176,124,63,.18)]"
        :data-cursor="t('unit.view')"
    >
        <MediaImage v-if="unit.cover_image" :src="unit.cover_image" :seed="unit.slug" :alt="unit.name" class="aspect-[4/3] shrink-0" :label="false" />
        <div class="flex flex-1 flex-col p-7">
            <span class="flex items-center justify-between text-[11px] tracking-[0.25em] text-muted uppercase">
                <span class="num-italic text-base tracking-normal text-gold normal-case">0{{ index + 1 }}</span>
                <span class="text-gold-dark">{{ t('units.count', { count: unit.unit_count }) }}</span>
            </span>
            <span class="mt-auto pt-6 font-serif text-[60px] leading-none transition-colors duration-500 group-hover:text-gold">{{ unit.name }}</span>
            <span class="num-italic mt-1 min-h-7 text-xl text-gold-dark">{{ unit.label }}</span>
            <span class="mt-5 border-t border-line pt-4 text-[13px] leading-relaxed text-muted">{{ unit.sizes }}</span>
            <span class="mt-4 flex items-center gap-2 text-[12px] font-medium tracking-[0.18em] text-ink uppercase">
                {{ t('unit.view') }}
                <span class="grid size-7 place-items-center rounded-full border border-ink/15 transition duration-500 group-hover:translate-x-1 group-hover:border-gold group-hover:bg-gold group-hover:text-white">
                    <Icon name="arrow-right" :size="13" />
                </span>
            </span>
        </div>
    </a>
</template>
