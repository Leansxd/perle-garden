<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import Icon from './Icon.vue';
import BrandLogo from './BrandLogo.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { usePage } from '@inertiajs/vue3';
import { gsap, lockScroll, prefersReducedMotion, scrollToId } from '@/motion/motion';

const { site, whatsappLink } = useSite();
const { t, locale, locales, localePath } = useI18n();
const page = usePage();
// Ana sayfada bölüm bağlantıları sayfa içi; diğer sayfalarda ana sayfaya + bölüme gider
const isHome = computed(() => page.component === 'Home');
const anchor = (id: string) => (isHome.value ? `#${id}` : `${localePath('/')}#${id}`);

const nav = computed(() => [
    { id: 'proje', label: t('nav.project') },
    { id: 'daireler', label: t('nav.units') },
    { id: 'galeri', label: t('nav.gallery') },
    { id: 'yasam', label: t('nav.life') },
    { id: 'insaat', label: t('nav.construction') },
    { id: 'konum', label: t('nav.location') },
]);

const menuOpen = ref(false);
const active = ref<string | null>(null);
const solid = ref(false);
const hidden = ref(false);
let lastY = 0;
let io: IntersectionObserver | null = null;

const onScroll = () => {
    const y = window.scrollY;
    solid.value = y > 60;
    hidden.value = y > 500 && y > lastY && !menuOpen.value;
    lastY = y;
};

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
    io = new IntersectionObserver(
        (entries) => {
            for (const e of entries) if (e.isIntersecting) active.value = e.target.id;
        },
        { rootMargin: '-45% 0px -50% 0px' },
    );
    [...nav.value.map((n) => n.id), 'iletisim'].forEach((id) => {
        const el = document.getElementById(id);
        if (el) io?.observe(el);
    });
});
onBeforeUnmount(() => {
    window.removeEventListener('scroll', onScroll);
    io?.disconnect();
});

watch(menuOpen, (open) => {
    lockScroll(open);
    if (open && !prefersReducedMotion()) {
        requestAnimationFrame(() => gsap.from('.mm-item', { yPercent: 110, duration: 0.9, ease: 'expo.out', stagger: 0.05, delay: 0.15 }));
    }
});

function go(id: string) {
    menuOpen.value = false;
    if (!isHome.value && id !== 'top' && id !== 'iletisim') {
        window.location.href = anchor(id);
        return;
    }
    setTimeout(() => scrollToId(id), 60);
}

// Dil değiştirince aynı bölümde kalmak için hash'i taşı
function localeHref(url: string) {
    return active.value && active.value !== 'top' ? `${url}#${active.value}` : url;
}
</script>

<template>
    <nav
        class="fixed inset-x-0 top-0 z-50 transition-[transform,background-color,color,box-shadow] duration-700 ease-(--ease-expo)"
        :class="[
            solid || menuOpen ? 'bg-pearl/85 text-ink shadow-[0_1px_0_rgba(26,29,27,.08)] backdrop-blur-xl' : 'bg-transparent text-white',
            hidden && '-translate-y-full',
        ]"
        :aria-label="t('nav.project')"
    >
        <div class="container-site flex h-[72px] items-center justify-between gap-6 sm:h-[84px]">
            <a href="#top" class="block shrink-0 transition-transform duration-500 hover:scale-[1.03]" :aria-label="`${site.company_name} — ${t('nav.home')}`" @click.prevent="go('top')">
                <BrandLogo class="h-10 drop-shadow-[0_2px_10px_rgba(0,0,0,.25)] sm:h-12" :class="(solid || menuOpen) && 'drop-shadow-none'" :alt="site.company_name ?? ''" />
            </a>

            <div class="hidden items-center gap-7 text-[13px] tracking-[0.14em] uppercase xl:flex">
                 <a v-for="n in nav" :key="n.id" :href="anchor(n.id)" class="group relative py-2" :class="active === n.id ? 'text-gold' : 'opacity-85 hover:opacity-100'">
                    {{ n.label }}
                    <span
                        class="absolute inset-x-0 bottom-0 h-px origin-right scale-x-0 bg-current transition-transform duration-500 ease-(--ease-expo) group-hover:origin-left group-hover:scale-x-100"
                        :class="active === n.id && 'origin-left scale-x-100'"
                    ></span>
                </a>
            </div>

            <div class="flex items-center gap-4">
                <!-- Dil seçici -->
                <div class="hidden items-center gap-1 text-[12px] font-medium tracking-[0.18em] sm:flex" role="group" :aria-label="t('nav.language')">
                    <template v-for="(l, i) in locales" :key="l.code">
                        <span v-if="i > 0" class="opacity-30">/</span>
                        <a
                            :href="localeHref(l.url)"
                            :hreflang="l.hreflang"
                            :lang="l.code"
                            :title="l.name"
                            class="px-1 py-1 transition-colors"
                            :class="locale === l.code ? ['border-b border-current', solid || menuOpen ? 'text-gold-dark' : 'text-gold-light'] : 'opacity-70 hover:opacity-100'"
                            :aria-current="locale === l.code ? 'true' : undefined"
                        >
                            {{ l.label }}
                        </a>
                    </template>
                </div>
                <a href="#iletisim" @click.prevent="go('iletisim')" class="btn hidden px-6 py-3 md:inline-flex" data-magnetic>{{ t('nav.info') }}</a>
                <button
                    type="button"
                    class="relative grid size-11 place-items-center xl:hidden"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    :aria-label="menuOpen ? t('nav.menu_close') : t('nav.menu_open')"
                    @click="menuOpen = !menuOpen"
                >
                    <span class="absolute h-px w-6 bg-current transition duration-500" :class="menuOpen ? 'rotate-45' : '-translate-y-1'"></span>
                    <span class="absolute h-px w-6 bg-current transition duration-500" :class="menuOpen ? '-rotate-45' : 'translate-y-1'"></span>
                </button>
            </div>
        </div>

        <!-- Tam ekran mobil menü -->
        <Transition
            enter-active-class="transition-[clip-path] duration-700 ease-(--ease-expo)"
            enter-from-class="[clip-path:inset(0_0_100%_0)]"
            enter-to-class="[clip-path:inset(0_0_0_0)]"
            leave-active-class="transition-[clip-path] duration-500 ease-(--ease-expo)"
            leave-from-class="[clip-path:inset(0_0_0_0)]"
            leave-to-class="[clip-path:inset(0_0_100%_0)]"
        >
            <div v-if="menuOpen" id="mobile-menu" data-lenis-prevent class="fixed inset-x-0 top-[72px] bottom-0 flex flex-col overflow-y-auto bg-pearl px-[4%] pt-6 pb-10 sm:top-[84px] xl:hidden">
                <a
                    v-for="(n, i) in [...nav, { id: 'iletisim', label: t('nav.contact') }]"
                    :key="n.id"
                    :href="anchor(n.id)"
                    class="flex items-baseline gap-4 overflow-hidden border-b border-line py-3"
                    @click.prevent="go(n.id)"
                >
                    <span class="mm-item num-italic text-gold">0{{ i + 1 }}</span>
                    <span class="mm-item font-serif text-[38px] leading-tight">{{ n.label }}</span>
                </a>
                <div class="mt-8 flex gap-3">
                    <a
                        v-for="l in locales"
                        :key="l.code"
                        :href="l.url"
                        :hreflang="l.hreflang"
                        class="flex-1 rounded-full border py-3 text-center text-[13px] font-medium tracking-[0.18em]"
                        :class="locale === l.code ? 'border-gold bg-gold text-white' : 'border-line'"
                    >
                        {{ l.label }}
                    </a>
                </div>
                <a :href="whatsappLink()" target="_blank" rel="noopener" class="btn mt-auto w-full">
                    <Icon name="whatsapp" :size="16" :stroke="0" /> {{ t('nav.whatsapp_info') }}
                </a>
            </div>
        </Transition>
    </nav>
</template>
