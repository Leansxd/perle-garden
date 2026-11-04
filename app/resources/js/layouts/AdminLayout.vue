<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import BrandLogo from '@/components/BrandLogo.vue';
import Icon from '@/components/Icon.vue';
import type { SharedProps } from '@/types';

const page = usePage<SharedProps & { adminUnread: number | null }>();
const sidebarOpen = ref(false);

const nav = [
    { href: '/admin', label: 'Panel', icon: 'home', exact: true },
    { href: '/admin/daireler', label: 'Daireler', icon: 'building' },
    { href: '/admin/galeri', label: 'Galeri', icon: 'sparkle' },
    { href: '/admin/icerik/olanaklar', label: 'Sosyal olanaklar', icon: 'leaf' },
    { href: '/admin/icerik/insaat', label: 'İnşaat aşamaları', icon: 'hardhat' },
    { href: '/admin/icerik/konum', label: 'Konum mesafeleri', icon: 'pin' },
    { href: '/admin/icerik/rakamlar', label: 'Rakamlar', icon: 'target' },
    { href: '/admin/icerik/one-cikanlar', label: 'Öne çıkanlar', icon: 'check' },
    { href: '/admin/ayarlar', label: 'Site ayarları', icon: 'clipboard' },
    { href: '/admin/mesajlar', label: 'Mesajlar', icon: 'mail', badge: true },
];

const isActive = (href: string, exact?: boolean) => {
    const url = page.url.split('?')[0];
    return exact ? url === href : url === href || url.startsWith(href + '/');
};

/* Bildirim */
const toast = ref<string | null>(null);
let timer: ReturnType<typeof setTimeout> | undefined;
watch(
    () => page.props.flash?.success,
    (msg) => {
        if (!msg) return;
        toast.value = msg;
        clearTimeout(timer);
        timer = setTimeout(() => (toast.value = null), 4000);
    },
    { immediate: true },
);
watch(
    () => page.url,
    () => (sidebarOpen.value = false),
);

const user = computed(() => page.props.auth);
const logout = () => router.post('/admin/cikis');
</script>

<template>
    <div class="min-h-screen bg-[#f6f4ef] font-sans font-normal text-ink">
        <!-- Kenar çubuğu -->
        <aside
            class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col border-r border-line bg-white transition-transform duration-300 lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full'"
        >
            <div class="flex h-20 items-center border-b border-line px-6">
                <Link href="/admin"><BrandLogo class="h-10" /></Link>
            </div>
            <nav class="flex-1 space-y-0.5 overflow-y-auto p-3 text-[14px]">
                <Link
                    v-for="item in nav"
                    :key="item.href"
                    :href="item.href"
                    class="flex items-center gap-3 rounded-lg px-3 py-2.5 transition"
                    :class="isActive(item.href, item.exact) ? 'bg-gold/10 font-medium text-gold-deep' : 'text-muted hover:bg-cream hover:text-ink'"
                >
                    <Icon :name="item.icon" :size="18" :stroke="1.8" />
                    <span class="flex-1">{{ item.label }}</span>
                    <span v-if="item.badge && page.props.adminUnread" class="rounded-full bg-gold px-2 py-0.5 text-[11px] font-medium text-white">{{ page.props.adminUnread }}</span>
                </Link>
            </nav>
            <div class="border-t border-line p-4 text-[13px]">
                <p class="truncate font-medium">{{ user?.name }}</p>
                <p class="truncate text-muted">{{ user?.email }}</p>
                <div class="mt-3 flex gap-2">
                    <a href="/" target="_blank" class="adm-btn adm-btn-ghost flex-1 px-2 py-2"><Icon name="arrow-up-right" :size="14" /> Site</a>
                    <button type="button" class="adm-btn adm-btn-ghost flex-1 px-2 py-2" @click="logout">Çıkış</button>
                </div>
            </div>
        </aside>
        <div v-if="sidebarOpen" class="fixed inset-0 z-30 bg-black/30 lg:hidden" @click="sidebarOpen = false"></div>

        <div class="lg:pl-64">
            <header class="sticky top-0 z-20 flex h-16 items-center gap-3 border-b border-line bg-white/80 px-4 backdrop-blur lg:hidden">
                <button type="button" class="adm-icon-btn size-10" aria-label="Menü" @click="sidebarOpen = true"><Icon name="menu" :size="20" /></button>
                <BrandLogo class="h-8" />
            </header>
            <main class="mx-auto max-w-6xl px-4 py-8 sm:px-8 sm:py-10">
                <slot />
            </main>
        </div>

        <Transition enter-active-class="transition duration-300" enter-from-class="translate-y-4 opacity-0" leave-active-class="transition duration-200" leave-to-class="opacity-0">
            <div v-if="toast" role="status" class="fixed right-5 bottom-5 z-50 flex items-center gap-3 rounded-xl bg-ink px-4 py-3 text-[14px] text-white shadow-xl">
                <span class="grid size-6 place-items-center rounded-full bg-gold"><Icon name="check" :size="14" /></span>
                {{ toast }}
            </div>
        </Transition>
    </div>
</template>
