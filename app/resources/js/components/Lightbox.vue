<script setup lang="ts">
import { computed, onBeforeUnmount, watch } from 'vue';
import MediaImage from './MediaImage.vue';
import Icon from './Icon.vue';
import type { GalleryImage } from '@/types';
import { lockScroll } from '@/motion/motion';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

/** Galeri ışık kutusu — ok tuşları, Esc ve kaydırma kilidi. */
const props = defineProps<{ images: GalleryImage[] }>();
const index = defineModel<number | null>({ required: true });

const current = computed(() => (index.value === null ? null : props.images[index.value]));

function go(step: number) {
    if (index.value === null) return;
    index.value = (index.value + step + props.images.length) % props.images.length;
}

function onKey(e: KeyboardEvent) {
    if (index.value === null) return;
    if (e.key === 'Escape') index.value = null;
    if (e.key === 'ArrowRight') go(1);
    if (e.key === 'ArrowLeft') go(-1);
}

watch(index, (v) => {
    lockScroll(v !== null);
    if (v !== null) window.addEventListener('keydown', onKey);
    else window.removeEventListener('keydown', onKey);
});
onBeforeUnmount(() => window.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition enter-active-class="transition duration-300" enter-from-class="opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <div
                v-if="current"
                class="fixed inset-0 z-[100] flex items-center justify-center bg-black/90 p-5"
                role="dialog"
                aria-modal="true"
                :aria-label="current.caption"
                @click.self="index = null"
            >
                <button type="button" class="absolute top-5 right-6 text-white/80 hover:text-white" :aria-label="t('gallery.close')" @click="index = null">
                    <Icon name="x" :size="34" />
                </button>
                <button
                    type="button"
                    class="absolute left-3 grid size-12 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:left-6"
                    :aria-label="t('gallery.prev')"
                    @click="go(-1)"
                >
                    <Icon name="chevron-left" />
                </button>
                <figure class="w-full max-w-5xl">
                    <MediaImage :src="current.path" :seed="`gal-${current.id}`" :alt="current.caption" class="aspect-[16/10] rounded-lg" :zoom="false" />
                    <figcaption class="mt-4 text-center text-sm text-white/75">
                        {{ current.caption }} <span class="text-white/40">· {{ (index ?? 0) + 1 }} / {{ images.length }}</span>
                    </figcaption>
                </figure>
                <button
                    type="button"
                    class="absolute right-3 grid size-12 place-items-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:right-6"
                    :aria-label="t('gallery.next')"
                    @click="go(1)"
                >
                    <Icon name="chevron-right" />
                </button>
            </div>
        </Transition>
    </Teleport>
</template>
