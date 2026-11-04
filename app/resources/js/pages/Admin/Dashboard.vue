<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import Icon from '@/components/Icon.vue';

defineProps<{
    counts: { units: number; media: number; gallery: number; messages: number; unread: number };
    latest: { id: number; name: string; phone: string | null; unit_type: string | null; locale: string; read_at: string | null; created_at: string }[];
}>();

const date = (s: string) => new Date(s).toLocaleString('tr-TR', { dateStyle: 'medium', timeStyle: 'short' });

const shortcuts = [
    { href: '/admin/daireler', icon: 'building', title: 'Daireler', text: 'Daire tipleri, fotoğraflar, 3D görseller, planlar' },
    { href: '/admin/galeri', icon: 'sparkle', title: 'Galeri', text: 'Ana sayfa proje görselleri' },
    { href: '/admin/icerik/insaat', icon: 'hardhat', title: 'İnşaat durumu', text: 'Aşama yüzdelerini güncelleyin' },
    { href: '/admin/ayarlar', icon: 'clipboard', title: 'Site ayarları', text: 'Metinler, video, iletişim bilgileri' },
];
</script>

<template>
    <Head title="Panel" />
    <h1 class="font-serif text-4xl">Hoş geldiniz</h1>
    <p class="mt-1 text-muted">Perle Garden sitesinin içeriğini buradan yönetebilirsiniz.</p>

    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="adm-card"><p class="text-[13px] text-muted">Daire tipi</p><p class="mt-1 font-serif text-4xl">{{ counts.units }}</p></div>
        <div class="adm-card"><p class="text-[13px] text-muted">Daire görseli</p><p class="mt-1 font-serif text-4xl">{{ counts.media }}</p></div>
        <div class="adm-card"><p class="text-[13px] text-muted">Galeri görseli</p><p class="mt-1 font-serif text-4xl">{{ counts.gallery }}</p></div>
        <Link href="/admin/mesajlar?filtre=okunmamis" class="adm-card transition hover:border-gold">
            <p class="text-[13px] text-muted">Okunmamış mesaj</p>
            <p class="mt-1 font-serif text-4xl" :class="counts.unread ? 'text-gold-dark' : ''">{{ counts.unread }} <span class="text-lg text-muted">/ {{ counts.messages }}</span></p>
        </Link>
    </div>

    <div class="mt-8 grid gap-6 lg:grid-cols-[1fr_1.2fr]">
        <div class="grid gap-3">
            <Link v-for="s in shortcuts" :key="s.href" :href="s.href" class="adm-card group flex items-center gap-4 p-5 transition hover:border-gold">
                <span class="grid size-11 place-items-center rounded-xl bg-gold/10 text-gold-deep"><Icon :name="s.icon" :size="20" /></span>
                <span class="flex-1">
                    <span class="block font-medium">{{ s.title }}</span>
                    <span class="block text-[13px] text-muted">{{ s.text }}</span>
                </span>
                <Icon name="arrow-right" :size="16" class="text-muted transition group-hover:translate-x-1 group-hover:text-gold-dark" />
            </Link>
        </div>
        <div class="adm-card">
            <div class="mb-4 flex items-center justify-between">
                <h2 class="font-medium">Son bilgi talepleri</h2>
                <Link href="/admin/mesajlar" class="text-[13px] text-gold-dark hover:underline">Tümü</Link>
            </div>
            <p v-if="!latest.length" class="py-8 text-center text-[14px] text-muted">Henüz talep yok.</p>
            <ul v-else class="divide-y divide-line">
                <li v-for="m in latest" :key="m.id" class="flex items-center gap-3 py-3 text-[14px]">
                    <span class="size-2 shrink-0 rounded-full" :class="m.read_at ? 'bg-line' : 'bg-gold'"></span>
                    <span class="flex-1 truncate">
                        <b class="font-medium">{{ m.name }}</b>
                        <span class="text-muted"> · {{ m.unit_type || 'Daire belirtilmedi' }}</span>
                    </span>
                    <span class="rounded bg-cream px-1.5 py-0.5 text-[11px] uppercase">{{ m.locale }}</span>
                    <span class="hidden text-[12px] text-muted sm:inline">{{ date(m.created_at) }}</span>
                </li>
            </ul>
        </div>
    </div>
</template>
