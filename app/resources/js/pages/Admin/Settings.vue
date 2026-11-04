<script setup lang="ts">
import { reactive } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import TransField from '@/components/admin/TransField.vue';
import FileDrop from '@/components/admin/FileDrop.vue';
import type { Trans } from '@/types';

interface Field {
    key: string;
    label: string;
    type: 'text' | 'textarea' | 'image' | 'video' | 'url';
    translatable: boolean;
    hint: string | null;
}
type FileValue = { path: string | null; url: string | null };

const props = defineProps<{
    schema: { group: string; fields: Field[] }[];
    values: Record<string, Trans | string | FileValue | null>;
}>();

// Dosya alanları ayrı tutulur; metinler `values` içinde gider
const textValues: Record<string, unknown> = {};
const previews = reactive<Record<string, string | null>>({});
for (const g of props.schema) {
    for (const f of g.fields) {
        if (f.type === 'image' || f.type === 'video') previews[f.key] = (props.values[f.key] as FileValue)?.url ?? null;
        else textValues[f.key] = props.values[f.key] ?? (f.translatable ? { tr: '', en: '', ru: '' } : '');
    }
}

const form = useForm({
    values: textValues as Record<string, Trans | string>,
    files: {} as Record<string, File>,
    remove: {} as Record<string, boolean>,
});

function pick(key: string, files: File[]) {
    form.files[key] = files[0];
    form.remove[key] = false;
    previews[key] = URL.createObjectURL(files[0]);
}
function clear(key: string) {
    delete form.files[key];
    form.remove[key] = true;
    previews[key] = null;
}
const err = (k: string) => (form.errors as Record<string, string>)[`values.${k}`] ?? (form.errors as Record<string, string>)[`files.${k}`];

const submit = () =>
    form.post('/admin/ayarlar', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            form.files = {};
            form.remove = {};
        },
    });
</script>

<template>
    <Head title="Site ayarları" />
    <form @submit.prevent="submit">
        <div class="sticky top-0 z-10 -mx-4 flex flex-wrap items-end justify-between gap-4 bg-[#f6f4ef]/90 px-4 py-4 backdrop-blur sm:-mx-8 sm:px-8">
            <div>
                <h1 class="font-serif text-4xl">Site ayarları</h1>
                <p class="mt-1 text-muted">Metinler üç dilde düzenlenir; boş bırakılan diller Türkçe gösterilir.</p>
            </div>
            <button type="submit" class="adm-btn adm-btn-gold" :disabled="form.processing">
                {{ form.processing ? `Kaydediliyor… ${form.progress ? '%' + form.progress.percentage : ''}` : 'Kaydet' }}
            </button>
        </div>

        <section v-for="g in schema" :key="g.group" class="adm-card mt-6">
            <h2 class="mb-5 font-medium">{{ g.group }}</h2>
            <div class="grid gap-5 md:grid-cols-2">
                <div v-for="f in g.fields" :key="f.key" :class="(f.type === 'textarea' || f.type === 'url') && 'md:col-span-2'">
                    <TransField
                        v-if="f.translatable"
                        v-model="form.values[f.key] as Trans"
                        :label="f.label"
                        :hint="f.hint"
                        :textarea="f.type === 'textarea'"
                        :error="(form.errors as Record<string, string>)[`values.${f.key}.tr`]"
                    />
                    <div v-else-if="f.type === 'image' || f.type === 'video'">
                        <span class="adm-label">{{ f.label }}</span>
                        <div v-if="previews[f.key]" class="relative mb-2 overflow-hidden rounded-xl bg-cream">
                            <img v-if="f.type === 'image'" :src="previews[f.key]!" alt="" class="aspect-video w-full object-cover" />
                            <video v-else :src="previews[f.key]!" class="aspect-video w-full object-cover" muted controls></video>
                            <button type="button" class="adm-btn adm-btn-danger absolute top-2 right-2 px-2 py-1 text-[12px]" @click="clear(f.key)">Kaldır</button>
                        </div>
                        <FileDrop
                            compact
                            :multiple="false"
                            :accept="f.type === 'video' ? 'video/mp4,video/webm' : 'image/*'"
                            :label="previews[f.key] ? 'Değiştir' : 'Dosya seç'"
                            :hint="f.hint ?? (f.type === 'video' ? 'MP4 · en fazla 100 MB' : 'JPG, PNG, WEBP · en fazla 20 MB')"
                            @files="(files) => pick(f.key, files)"
                        />
                        <span v-if="err(f.key)" class="adm-error">{{ err(f.key) }}</span>
                    </div>
                    <label v-else class="block">
                        <span class="adm-label">{{ f.label }}</span>
                        <input v-model="form.values[f.key]" :type="f.type === 'url' ? 'url' : 'text'" class="adm-input" />
                        <span v-if="f.hint" class="adm-hint">{{ f.hint }}</span>
                        <span v-if="err(f.key)" class="adm-error">{{ err(f.key) }}</span>
                    </label>
                </div>
            </div>
        </section>
    </form>
</template>
