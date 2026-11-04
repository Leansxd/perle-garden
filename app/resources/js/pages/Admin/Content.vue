<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import TransField from '@/components/admin/TransField.vue';
import type { Trans } from '@/types';

interface Field {
    name: string;
    label: string;
    input: 'text' | 'textarea' | 'number';
    translatable: boolean;
}
type Item = Record<string, Trans | string | number | null> & { id: number | null };

const props = defineProps<{
    type: string;
    title: string;
    help: string;
    fields: Field[];
    items: Item[];
    types: { key: string; title: string }[];
}>();

const form = useForm({ items: props.items.map((i) => ({ ...i })) as Item[] });

function blank(): Item {
    const item: Item = { id: null };
    for (const f of props.fields) item[f.name] = f.translatable ? { tr: '', en: '', ru: '' } : f.input === 'number' ? 0 : '';
    return item;
}
function move(i: number, dir: -1 | 1) {
    const j = i + dir;
    if (j < 0 || j >= form.items.length) return;
    [form.items[i], form.items[j]] = [form.items[j], form.items[i]];
}
const err = (i: number, name: string) => (form.errors as Record<string, string>)[`items.${i}.${name}.tr`] ?? (form.errors as Record<string, string>)[`items.${i}.${name}`];
const submit = () => form.put(`/admin/icerik/${props.type}`, { preserveScroll: true });
</script>

<template>
    <Head :title="title" />
    <div class="mb-6 flex flex-wrap gap-2">
        <Link
            v-for="t in types"
            :key="t.key"
            :href="`/admin/icerik/${t.key}`"
            class="rounded-full border px-3.5 py-1.5 text-[13px] transition"
            :class="t.key === type ? 'border-gold bg-gold text-white' : 'border-line bg-white hover:border-gold'"
        >
            {{ t.title }}
        </Link>
    </div>

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl">{{ title }}</h1>
            <p class="mt-1 text-muted">{{ help }}</p>
        </div>
        <button type="button" class="adm-btn adm-btn-gold" :disabled="form.processing || !form.isDirty" @click="submit">
            {{ form.processing ? 'Kaydediliyor…' : form.isDirty ? 'Değişiklikleri kaydet' : 'Kaydedildi' }}
        </button>
    </div>

    <ul class="mt-8 space-y-3">
        <li v-for="(item, i) in form.items" :key="i" class="adm-card flex gap-4 p-4">
            <div class="flex flex-col gap-1 pt-1">
                <button type="button" class="adm-icon-btn size-7" :disabled="i === 0" aria-label="Yukarı" @click="move(i, -1)"><Icon name="chevron-down" :size="14" class="rotate-180" /></button>
                <span class="text-center text-[12px] text-muted">{{ i + 1 }}</span>
                <button type="button" class="adm-icon-btn size-7" :disabled="i === form.items.length - 1" aria-label="Aşağı" @click="move(i, 1)"><Icon name="chevron-down" :size="14" /></button>
            </div>
            <div class="grid flex-1 gap-4" :class="fields.length > 1 ? 'md:grid-cols-2' : ''">
                <template v-for="f in fields" :key="f.name">
                    <TransField v-if="f.translatable" v-model="item[f.name] as Trans" :label="f.label" required :error="err(i, f.name)" />
                    <label v-else class="block">
                        <span class="adm-label">{{ f.label }}</span>
                        <input v-if="f.input === 'number'" v-model.number="item[f.name]" type="number" min="0" class="adm-input" />
                        <input v-else v-model="item[f.name]" class="adm-input" />
                        <span v-if="err(i, f.name)" class="adm-error">{{ err(i, f.name) }}</span>
                    </label>
                </template>
            </div>
            <button type="button" class="adm-icon-btn hover:border-red-300 hover:text-red-600" aria-label="Sil" @click="form.items.splice(i, 1)"><Icon name="x" :size="14" /></button>
        </li>
    </ul>

    <div class="mt-4 flex justify-between gap-3">
        <button type="button" class="adm-btn adm-btn-ghost" @click="form.items.push(blank())">+ Yeni satır</button>
        <button type="button" class="adm-btn adm-btn-gold" :disabled="form.processing || !form.isDirty" @click="submit">Kaydet</button>
    </div>
</template>
