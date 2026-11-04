<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

interface Message {
    id: number;
    name: string;
    email: string | null;
    phone: string | null;
    unit_type: string | null;
    message: string | null;
    locale: string;
    read_at: string | null;
    created_at: string;
}
defineProps<{
    messages: { data: Message[]; links: { url: string | null; label: string; active: boolean }[]; total: number };
    filter: string | null;
    unread: number;
}>();

const open = ref<number | null>(null);
const date = (s: string) => new Date(s).toLocaleString('tr-TR', { dateStyle: 'medium', timeStyle: 'short' });
const wa = (phone: string) => `https://wa.me/${phone.replace(/\D/g, '').replace(/^0/, '90')}`;

function toggle(m: Message) {
    open.value = open.value === m.id ? null : m.id;
    if (!m.read_at && open.value === m.id) router.patch(`/admin/mesajlar/${m.id}/okundu`, {}, { preserveScroll: true, preserveState: true });
}
const markToggle = (m: Message) => router.patch(`/admin/mesajlar/${m.id}/okundu`, {}, { preserveScroll: true, preserveState: true });
function remove(m: Message) {
    if (confirm(`${m.name} adlı kişinin talebi silinsin mi?`)) router.delete(`/admin/mesajlar/${m.id}`, { preserveScroll: true });
}
</script>

<template>
    <Head title="Mesajlar" />
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div>
            <h1 class="font-serif text-4xl">Bilgi talepleri</h1>
            <p class="mt-1 text-muted">Sitedeki formdan gelen talepler · {{ messages.total }} kayıt</p>
        </div>
        <div class="flex gap-2 text-[13px]">
            <Link href="/admin/mesajlar" class="rounded-full border px-3.5 py-1.5" :class="!filter ? 'border-gold bg-gold text-white' : 'border-line bg-white'">Tümü</Link>
            <Link href="/admin/mesajlar?filtre=okunmamis" class="rounded-full border px-3.5 py-1.5" :class="filter === 'okunmamis' ? 'border-gold bg-gold text-white' : 'border-line bg-white'">Okunmamış ({{ unread }})</Link>
        </div>
    </div>

    <div class="adm-card mt-8 p-0">
        <p v-if="!messages.data.length" class="py-16 text-center text-muted">Kayıt yok.</p>
        <ul v-else class="divide-y divide-line">
            <li v-for="m in messages.data" :key="m.id">
                <button type="button" class="flex w-full items-center gap-4 px-5 py-4 text-left transition hover:bg-cream/60" @click="toggle(m)">
                    <span class="size-2.5 shrink-0 rounded-full" :class="m.read_at ? 'bg-line' : 'bg-gold'"></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate" :class="!m.read_at && 'font-medium'">{{ m.name }}</span>
                        <span class="block truncate text-[13px] text-muted">{{ m.unit_type || 'Daire belirtilmedi' }} · {{ m.phone }}</span>
                    </span>
                    <span class="rounded bg-cream px-1.5 py-0.5 text-[11px] uppercase">{{ m.locale }}</span>
                    <span class="hidden text-[13px] text-muted sm:inline">{{ date(m.created_at) }}</span>
                    <Icon name="chevron-down" :size="16" class="text-muted transition" :class="open === m.id && 'rotate-180'" />
                </button>
                <div v-if="open === m.id" class="border-t border-line bg-cream/40 px-5 py-5 text-[14px]">
                    <dl class="grid gap-3 sm:grid-cols-3">
                        <div><dt class="text-[12px] text-muted">Telefon</dt><dd>{{ m.phone || '—' }}</dd></div>
                        <div><dt class="text-[12px] text-muted">E-posta</dt><dd class="break-all">{{ m.email || '—' }}</dd></div>
                        <div><dt class="text-[12px] text-muted">İlgilendiği daire</dt><dd>{{ m.unit_type || '—' }}</dd></div>
                    </dl>
                    <p v-if="m.message" class="mt-4 rounded-lg bg-white p-4 whitespace-pre-line">{{ m.message }}</p>
                    <div class="mt-4 flex flex-wrap gap-2">
                        <a v-if="m.phone" :href="`tel:${m.phone}`" class="adm-btn adm-btn-ghost"><Icon name="phone" :size="14" /> Ara</a>
                        <a v-if="m.phone" :href="wa(m.phone)" target="_blank" class="adm-btn adm-btn-ghost"><Icon name="whatsapp" :size="14" :stroke="0" /> WhatsApp</a>
                        <a v-if="m.email" :href="`mailto:${m.email}`" class="adm-btn adm-btn-ghost"><Icon name="mail" :size="14" /> E-posta</a>
                        <button type="button" class="adm-btn adm-btn-ghost" @click="markToggle(m)">{{ m.read_at ? 'Okunmadı yap' : 'Okundu yap' }}</button>
                        <button type="button" class="adm-btn adm-btn-danger ml-auto" @click="remove(m)">Sil</button>
                    </div>
                </div>
            </li>
        </ul>
    </div>

    <nav v-if="messages.links.length > 3" class="mt-6 flex flex-wrap gap-1">
        <template v-for="(l, i) in messages.links" :key="i">
            <Link v-if="l.url" :href="l.url" class="rounded-md border px-3 py-1.5 text-[13px]" :class="l.active ? 'border-gold bg-gold text-white' : 'border-line bg-white'" preserve-scroll>
                <span v-html="l.label"></span>
            </Link>
            <span v-else class="rounded-md border border-line px-3 py-1.5 text-[13px] text-muted opacity-50" v-html="l.label"></span>
        </template>
    </nav>
</template>
