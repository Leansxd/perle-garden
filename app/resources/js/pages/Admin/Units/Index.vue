<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

interface Row {
    id: number;
    title: string;
    slug: string;
    unit_count: number;
    sizes: string;
    is_published: boolean;
    media_count: number;
    cover: string | null;
}
const props = defineProps<{ units: Row[] }>();

const list = ref<Row[]>([]);
watch(() => props.units, (v) => (list.value = [...v]), { immediate: true });

function move(i: number, dir: -1 | 1) {
    const j = i + dir;
    if (j < 0 || j >= list.value.length) return;
    const copy = [...list.value];
    [copy[i], copy[j]] = [copy[j], copy[i]];
    list.value = copy;
    router.post('/admin/daireler/sirala', { ids: copy.map((u) => u.id) }, { preserveScroll: true, preserveState: true });
}

function remove(u: Row) {
    if (!confirm(`"${u.title}" daire tipi ve tüm görselleri silinsin mi?`)) return;
    router.delete(`/admin/daireler/${u.slug}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Daireler" />
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl">Daire tipleri</h1>
            <p class="mt-1 text-muted">Her daire tipinin sitede kendi sayfası olur. Fotoğraf, 3D görsel, plan ve 3D model ekleyebilirsiniz.</p>
        </div>
        <Link href="/admin/daireler/create" class="adm-btn adm-btn-gold"><Icon name="sparkle" :size="16" /> Yeni daire tipi</Link>
    </div>

    <ul class="mt-8 space-y-3">
        <li v-for="(u, i) in list" :key="u.id" class="adm-card flex flex-wrap items-center gap-4 p-4">
            <div class="flex flex-col gap-1">
                <button type="button" class="adm-icon-btn size-7" :disabled="i === 0" aria-label="Yukarı" @click="move(i, -1)"><Icon name="chevron-down" :size="14" class="rotate-180" /></button>
                <button type="button" class="adm-icon-btn size-7" :disabled="i === list.length - 1" aria-label="Aşağı" @click="move(i, 1)"><Icon name="chevron-down" :size="14" /></button>
            </div>
            <div class="h-16 w-24 shrink-0 overflow-hidden rounded-lg bg-cream">
                <img v-if="u.cover" :src="u.cover" alt="" class="size-full object-cover" />
                <div v-else class="grid size-full place-items-center text-gold/60"><Icon name="building" :size="22" /></div>
            </div>
            <div class="min-w-40 flex-1">
                <p class="font-serif text-2xl leading-tight">{{ u.title }}</p>
                <p class="text-[13px] text-muted">{{ u.unit_count }} daire · {{ u.sizes }}</p>
            </div>
            <span class="rounded-full px-2.5 py-1 text-[12px]" :class="u.media_count ? 'bg-gold/10 text-gold-deep' : 'bg-cream text-muted'">{{ u.media_count }} görsel</span>
            <span class="rounded-full px-2.5 py-1 text-[12px]" :class="u.is_published ? 'bg-green-50 text-green-700' : 'bg-cream text-muted'">{{ u.is_published ? 'Yayında' : 'Gizli' }}</span>
            <div class="flex gap-2">
                <a :href="`/daire/${u.slug}`" target="_blank" class="adm-btn adm-btn-ghost px-3" title="Sitede gör"><Icon name="arrow-up-right" :size="14" /></a>
                <Link :href="`/admin/daireler/${u.slug}/edit`" class="adm-btn adm-btn-primary">Düzenle</Link>
                <button type="button" class="adm-btn adm-btn-danger px-3" title="Sil" @click="remove(u)"><Icon name="x" :size="14" /></button>
            </div>
        </li>
    </ul>
</template>
