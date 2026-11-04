<script setup lang="ts">
import { ref } from 'vue';
import type { Trans } from '@/types';

/** TR / EN / RU sekmeli çok dilli metin alanı. TR zorunlu dildir; boş bırakılan diller TR'ye düşer. */
const model = defineModel<Trans>({ required: true });
withDefaults(defineProps<{ label?: string; hint?: string | null; textarea?: boolean; error?: string; required?: boolean; rows?: number }>(), {
    label: '',
    hint: null,
    textarea: false,
    error: undefined,
    required: false,
    rows: 3,
});

const langs = [
    { code: 'tr', label: 'TR' },
    { code: 'en', label: 'EN' },
    { code: 'ru', label: 'RU' },
] as const;
const active = ref<'tr' | 'en' | 'ru'>('tr');
</script>

<template>
    <div>
        <div class="mb-1.5 flex items-center justify-between gap-3">
            <span v-if="label" class="adm-label mb-0">{{ label }}<span v-if="required" class="text-gold-dark"> *</span></span>
            <div class="ml-auto flex rounded-md border border-line bg-cream p-0.5 text-[11px] font-medium" role="tablist">
                <button
                    v-for="l in langs"
                    :key="l.code"
                    type="button"
                    role="tab"
                    :aria-selected="active === l.code"
                    class="relative rounded px-2.5 py-1 transition"
                    :class="active === l.code ? 'bg-white text-ink shadow-sm' : 'text-muted hover:text-ink'"
                    @click="active = l.code"
                >
                    {{ l.label }}
                    <!-- Doluluk göstergesi -->
                    <span class="absolute top-0.5 right-0.5 size-1.5 rounded-full" :class="model[l.code] ? 'bg-gold' : l.code === 'tr' && required ? 'bg-red-400' : 'bg-line'"></span>
                </button>
            </div>
        </div>
        <template v-for="l in langs" :key="l.code">
            <textarea
                v-if="textarea"
                v-show="active === l.code"
                v-model="model[l.code]"
                :rows="rows"
                :lang="l.code"
                class="adm-input resize-y"
                :placeholder="l.code !== 'tr' ? (model.tr ?? '') : ''"
            ></textarea>
            <input v-else v-show="active === l.code" v-model="model[l.code]" type="text" :lang="l.code" class="adm-input" :placeholder="l.code !== 'tr' ? (model.tr ?? '') : ''" />
        </template>
        <span v-if="hint" class="adm-hint">{{ hint }}</span>
        <span v-if="error" class="adm-error">{{ error }}</span>
    </div>
</template>
