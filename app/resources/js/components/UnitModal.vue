<script setup lang="ts">
import { onBeforeUnmount, watch } from 'vue';
import Icon from './Icon.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { lockScroll } from '@/motion/motion';
import type { UnitType } from '@/types';

/** Daire tipi detay penceresi — kat planı + oda listesi + bilgi al. */
const unit = defineModel<UnitType | null>({ required: true });
const emit = defineEmits<{ request: [unitName: string] }>();
const { whatsappLink } = useSite();
const { t } = useI18n();

const title = (u: UnitType) => (u.label ? `${u.name} ${u.label}` : u.name);

function onKey(e: KeyboardEvent) {
    if (e.key === 'Escape') unit.value = null;
}
watch(unit, (u) => {
    lockScroll(!!u);
    if (u) window.addEventListener('keydown', onKey);
    else window.removeEventListener('keydown', onKey);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));

function request() {
    if (!unit.value) return;
    emit('request', title(unit.value));
    unit.value = null;
}
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-500" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <div
                v-if="unit"
                class="fixed inset-0 z-[100] flex items-end justify-center bg-night/70 backdrop-blur-md sm:items-center sm:p-6"
                role="dialog"
                aria-modal="true"
                :aria-label="t('units.plan_label', { unit: title(unit) })"
                @click.self="unit = null"
            >
                <div data-lenis-prevent class="max-h-[92vh] w-full max-w-5xl overflow-y-auto rounded-t-(--radius-lg) bg-pearl sm:rounded-(--radius-lg)">
                    <div class="grid md:grid-cols-[1.15fr_1fr]">
                        <div class="bg-cream p-5 sm:p-10">
                            <img v-if="unit.plan_image" :src="unit.plan_image" :alt="t('units.plan_label', { unit: title(unit) })" class="w-full rounded-(--radius-sm) bg-white object-contain" />
                            <div v-else class="relative grid aspect-square place-items-center rounded-(--radius-sm) border border-dashed border-gold/50 bg-white">
                                <svg viewBox="0 0 200 200" class="w-3/4 text-gold/60" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                                    <rect x="10" y="10" width="180" height="180" />
                                    <path d="M10 95h80V10M90 95v95M90 140h100M140 95V10M140 95h50M120 95h20M60 95h-20" />
                                    <path d="M30 40h40M30 55h25M110 160h50M160 40h20" stroke-opacity=".5" />
                                </svg>
                                <span class="absolute bottom-4 text-[11px] tracking-[0.2em] text-muted uppercase">{{ t('units.plan_pending') }}</span>
                            </div>
                        </div>
                        <div class="relative p-7 sm:p-10">
                            <button type="button" class="absolute top-5 right-5 grid size-10 place-items-center rounded-full bg-cream text-ink transition hover:rotate-90 hover:bg-line" :aria-label="t('close')" @click="unit = null">
                                <Icon name="x" :size="18" />
                            </button>
                            <p class="kicker">{{ t('units.count', { count: unit.unit_count }) }}</p>
                            <h3 class="mt-4 font-serif text-6xl leading-none">{{ unit.name }}</h3>
                            <p v-if="unit.label" class="num-italic mt-1 text-2xl text-gold-dark">{{ unit.label }}</p>
                            <p class="mt-4 font-medium text-gold-deep">{{ unit.sizes }}</p>
                            <p v-if="unit.description" class="mt-4 text-[15px] leading-relaxed text-muted">{{ unit.description }}</p>
                            <ul v-if="unit.rooms?.length" class="mt-6 divide-y divide-line border-y border-line">
                                <li v-for="r in unit.rooms" :key="r" class="flex items-center gap-3 py-3 text-[15px]">
                                    <span class="text-gold">✦</span> {{ r }}
                                </li>
                            </ul>
                            <div class="mt-8 flex flex-col gap-3">
                                <button type="button" class="btn" @click="request">{{ t('units.request') }}</button>
                                <a :href="whatsappLink(t('units.whatsapp_msg', { unit: title(unit) }))" target="_blank" rel="noopener" class="btn btn-ghost-dark">
                                    <Icon name="whatsapp" :size="16" :stroke="0" /> {{ t('units.ask_whatsapp') }}
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>
