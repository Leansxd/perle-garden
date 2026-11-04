<script setup lang="ts">
import { ref } from 'vue';
import Icon from '@/components/Icon.vue';

/** Sürükle-bırak dosya alanı. Seçilen dosyaları `files` olayıyla gönderir. */
withDefaults(defineProps<{ accept?: string; multiple?: boolean; label?: string; hint?: string; compact?: boolean }>(), {
    accept: 'image/*',
    multiple: true,
    label: 'Dosyaları buraya sürükleyin ya da seçin',
    hint: 'JPG, PNG, WEBP · en fazla 20 MB',
    compact: false,
});
const emit = defineEmits<{ files: [files: File[]] }>();

const over = ref(false);
const input = ref<HTMLInputElement | null>(null);

function pick(list: FileList | null | undefined) {
    if (list?.length) emit('files', [...list]);
    if (input.value) input.value.value = '';
}
</script>

<template>
    <label
        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed text-center transition"
        :class="[over ? 'border-gold bg-gold/5' : 'border-line bg-cream/60 hover:border-gold/60', compact ? 'px-4 py-5' : 'px-6 py-10']"
        @dragover.prevent="over = true"
        @dragleave.prevent="over = false"
        @drop.prevent="over = false; pick($event.dataTransfer?.files)"
    >
        <span class="grid size-10 place-items-center rounded-full bg-white text-gold-dark shadow-sm"><Icon name="arrow-up-right" :size="18" class="-rotate-45" /></span>
        <span class="text-[14px] font-medium">{{ label }}</span>
        <span class="text-[12px] text-muted">{{ hint }}</span>
        <input ref="input" type="file" class="sr-only" :accept="accept" :multiple="multiple" @change="pick(($event.target as HTMLInputElement).files)" />
    </label>
</template>
