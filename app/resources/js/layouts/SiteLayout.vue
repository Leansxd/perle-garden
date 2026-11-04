<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import SiteHeader from '@/components/SiteHeader.vue';
import SiteFooter from '@/components/SiteFooter.vue';
import Icon from '@/components/Icon.vue';
import Preloader from '@/components/fx/Preloader.vue';
import CustomCursor from '@/components/fx/CustomCursor.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { bindMagnetic, startSmoothScroll } from '@/motion/motion';
import type { SharedProps } from '@/types';

const page = usePage<SharedProps>();
const { site, whatsappLink } = useSite();
const { t } = useI18n();
const toast = ref<string | null>(null);
const shell = ref<HTMLElement | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;
let offMagnetic: (() => void) | null = null;

watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) return;
        toast.value = msg;
        clearTimeout(timer);
        timer = setTimeout(() => (toast.value = null), 6000);
    },
    { immediate: true },
);

onMounted(() => {
    startSmoothScroll();
    if (shell.value) offMagnetic = bindMagnetic(shell.value);
});
onBeforeUnmount(() => offMagnetic?.());

function ready() {
    window.dispatchEvent(new Event('pg:ready'));
}
</script>

<template>
    <div ref="shell" class="grain" :lang="page.props.locale">
        <Preloader :name="site.company_name ?? 'Perle Garden'" @done="ready" />
        <CustomCursor />

        <!-- Kaydırma ilerleme çubuğu -->
        <div id="scroll-progress" class="fixed inset-x-0 top-0 z-[60] h-[2px] origin-left scale-x-0 bg-linear-to-r from-gold-dark via-gold-light to-gold" aria-hidden="true"></div>

        <a href="#icerik" class="sr-only focus:not-sr-only focus:fixed focus:top-3 focus:left-3 focus:z-[60] focus:rounded focus:bg-white focus:px-4 focus:py-2">
            {{ t('skip') }}
        </a>
        <SiteHeader />
        <main id="icerik">
            <slot />
        </main>
        <SiteFooter />

        <!-- Sabit WhatsApp butonu -->
        <a
            :href="whatsappLink()"
            target="_blank"
            rel="noopener"
            class="animate-wa fixed right-5 bottom-5 z-40 grid size-14 place-items-center rounded-full bg-[#25d366] text-white shadow-lg transition hover:scale-110"
            :aria-label="t('whatsapp_aria')"
            :data-cursor="t('cursor.write')"
        >
            <Icon name="whatsapp" :size="28" :stroke="0" />
        </a>

        <Transition
            enter-active-class="transition duration-700 ease-(--ease-expo)"
            enter-from-class="translate-y-8 opacity-0"
            leave-active-class="transition duration-300"
            leave-to-class="translate-y-6 opacity-0"
        >
            <div
                v-if="toast"
                role="status"
                class="fixed right-4 bottom-24 left-4 z-50 flex items-start gap-3 rounded-(--radius-sm) bg-night p-4 text-white shadow-[0_24px_60px_rgba(0,0,0,.25)] sm:right-5 sm:left-auto sm:max-w-sm"
            >
                <span class="grid size-7 shrink-0 place-items-center rounded-full bg-gold"><Icon name="check" :size="16" /></span>
                <p class="text-[14px] leading-snug">{{ toast }}</p>
                <button type="button" class="ml-auto opacity-60 hover:opacity-100" :aria-label="t('close')" @click="toast = null">
                    <Icon name="x" :size="18" />
                </button>
            </div>
        </Transition>
    </div>
</template>
