<script setup lang="ts">
import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';
import TransField from './TransField.vue';
import type { Trans } from '@/types';

export interface AdminMedia {
    id: number;
    url: string;
    caption: Trans;
}

/**
 * Görsel ızgarası: sırala (ok tuşları), açıklama düzenle (TR/EN/RU), sil.
 * Sunucu adresleri dışarıdan verilir; galeri ve daire medyası ortak kullanır.
 */
const props = defineProps<{
    items: AdminMedia[];
    updateUrl: (id: number) => string;
    deleteUrl: (id: number) => string;
    reorderUrl: string;
    emptyText?: string;
}>();

const list = ref<AdminMedia[]>([]);
watch(
    () => props.items,
    (v) => (list.value = v.map((m) => ({ ...m, caption: { ...m.caption } }))),
    { immediate: true, deep: true },
);

const editing = ref<number | null>(null);
const saving = ref(false);

function move(index: number, dir: -1 | 1) {
    const target = index + dir;
    if (target < 0 || target >= list.value.length) return;
    const copy = [...list.value];
    [copy[index], copy[target]] = [copy[target], copy[index]];
    list.value = copy;
    router.post(props.reorderUrl, { ids: copy.map((m) => m.id) }, { preserveScroll: true, preserveState: true });
}

function save(m: AdminMedia) {
    saving.value = true;
    router.put(props.updateUrl(m.id), { caption: { ...m.caption } as Record<string, string | null> }, {
        preserveScroll: true,
        preserveState: true,
        onSuccess: () => (editing.value = null),
        onFinish: () => (saving.value = false),
    });
}

function remove(m: AdminMedia) {
    if (!confirm('Bu görsel kalıcı olarak silinsin mi?')) return;
    router.delete(props.deleteUrl(m.id), { preserveScroll: true, preserveState: true });
}
</script>

<template>
    <p v-if="!list.length" class="rounded-xl border border-dashed border-line px-4 py-8 text-center text-[13px] text-muted">{{ emptyText ?? 'Henüz görsel yok.' }}</p>
    <ul v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        <li v-for="(m, i) in list" :key="m.id" class="overflow-hidden rounded-xl border border-line bg-white">
            <div class="group relative aspect-[4/3] bg-cream">
                <img :src="m.url" :alt="m.caption.tr ?? ''" class="size-full object-cover" loading="lazy" />
                <span class="absolute top-2 left-2 rounded-md bg-black/55 px-2 py-0.5 text-[11px] text-white">{{ i + 1 }}</span>
                <a :href="m.url" target="_blank" class="absolute top-2 right-2 grid size-8 place-items-center rounded-md bg-white/90 text-ink opacity-0 transition group-hover:opacity-100" aria-label="Tam boyut">
                    <Icon name="arrow-up-right" :size="14" />
                </a>
            </div>
            <div class="p-3">
                <p class="truncate text-[13px]" :class="m.caption.tr ? 'text-ink' : 'text-muted italic'">{{ m.caption.tr || 'Açıklama yok' }}</p>
                <div class="mt-2 flex items-center gap-1.5">
                    <button type="button" class="adm-icon-btn" :disabled="i === 0" aria-label="Öne al" @click="move(i, -1)"><Icon name="chevron-left" :size="16" /></button>
                    <button type="button" class="adm-icon-btn" :disabled="i === list.length - 1" aria-label="Geri al" @click="move(i, 1)"><Icon name="chevron-right" :size="16" /></button>
                    <button type="button" class="adm-btn adm-btn-ghost ml-auto px-3 py-1.5 text-[12px]" @click="editing = editing === m.id ? null : m.id">Açıklama</button>
                    <button type="button" class="adm-icon-btn hover:border-red-300 hover:text-red-600" aria-label="Sil" @click="remove(m)"><Icon name="x" :size="16" /></button>
                </div>
                <div v-if="editing === m.id" class="mt-3 border-t border-line pt-3">
                    <TransField v-model="m.caption" label="Görsel açıklaması" />
                    <button type="button" class="adm-btn adm-btn-primary mt-2 w-full" :disabled="saving" @click="save(m)">Kaydet</button>
                </div>
            </div>
        </li>
    </ul>
</template>
