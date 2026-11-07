<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import MediaImage from '@/components/MediaImage.vue';
import Lightbox from '@/components/Lightbox.vue';
import UnitCard from '@/components/UnitCard.vue';
import ContactSection from '@/components/ContactSection.vue';
import Model3D from '@/components/Model3D.vue';
import RenderCarousel from '@/components/RenderCarousel.vue';
import Icon from '@/components/Icon.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { bindScrollAnimations, gsap, prefersReducedMotion, scrollToId } from '@/motion/motion';
import { accent } from '@/utils/accent';
import type { GalleryImage, MediaItem, UnitDetail, UnitType } from '@/types';

const props = defineProps<{ unit: UnitDetail; others: UnitType[]; allUnits: string[] }>();

const { site } = useSite();
const { t, locales, localePath } = useI18n();

const title = computed(() => (props.unit.label ? `${props.unit.name} ${props.unit.label}` : props.unit.name));
const hero = computed(() => props.unit.cover_image ?? props.unit.renders[0]?.url ?? props.unit.photos[0]?.url ?? null);
const contactUnits = computed(() => props.allUnits.map((title) => ({ title })));
const hasMedia = computed(() => props.unit.renders.length + props.unit.photos.length + props.unit.plans.length > 0 || !!props.unit.model_3d || !!props.unit.tour_url);

/* Işık kutusu: tüm görseller tek listede (render → foto → plan) */
const allMedia = computed<GalleryImage[]>(() =>
    [...props.unit.renders, ...props.unit.photos, ...props.unit.plans].map((m) => ({ id: m.id, path: m.url, caption: m.caption ?? title.value })),
);
const lightboxIndex = ref<number | null>(null);
const open = (m: MediaItem) => (lightboxIndex.value = allMedia.value.findIndex((x) => x.id === m.id));

/* Kat planı seçici */
const planIndex = ref(0);
const activePlan = computed(() => props.unit.plans[planIndex.value]);

/* Video: YouTube bağlantısını embed adresine çevir */
const youtubeEmbed = computed(() => {
    const m = props.unit.video_url?.match(/(?:youtu\.be\/|v=|embed\/|shorts\/)([\w-]{11})/);
    return m ? `https://www.youtube-nocookie.com/embed/${m[1]}?rel=0` : null;
});

const facts = computed(() => [
    { label: t('unit.count'), value: t('units.count', { count: props.unit.unit_count }) },
    { label: t('unit.sizes'), value: props.unit.sizes },
    { label: t('unit.rooms'), value: props.unit.rooms?.slice(0, 2).join(' · ') ?? '—' },
]);

/* Animasyonlar */
const pageEl = ref<HTMLElement | null>(null);
const heroMedia = ref<HTMLElement | null>(null);
let unbind: (() => void) | null = null;
let ctx: gsap.Context | null = null;

onMounted(async () => {
    await nextTick();
    await document.fonts?.ready;
    if (pageEl.value) unbind = bindScrollAnimations(pageEl.value);
    if (!prefersReducedMotion() && heroMedia.value) {
        ctx = gsap.context(() => {
            gsap.fromTo(heroMedia.value, { scale: 1.2 }, { scale: 1, duration: 2.4, ease: 'power3.out' });
            gsap.to(heroMedia.value, { yPercent: 10, ease: 'none', scrollTrigger: { trigger: heroMedia.value, start: 'top top', end: 'bottom top', scrub: true } });
            gsap.from('.u-hero-item', { y: 40, autoAlpha: 0, duration: 1.2, ease: 'expo.out', stagger: 0.1, delay: 0.3 });
        });
    }
});
onBeforeUnmount(() => {
    unbind?.();
    ctx?.revert();
});
</script>

<template>
    <Head :title="title">
        <link v-for="l in locales" :key="l.code" rel="alternate" :hreflang="l.hreflang" :href="l.url" />
    </Head>

    <div ref="pageEl">
        <!-- HERO -->
        <header id="top" class="relative isolate flex min-h-svh flex-col justify-end overflow-hidden bg-night text-pearl">
            <div ref="heroMedia" class="absolute inset-x-0 -top-[15%] -z-10 h-[130%] will-change-transform">
                <MediaImage :src="hero" :seed="unit.slug" :alt="title" class="size-full" :label="false" :zoom="false" />
            </div>
            <div class="pointer-events-none absolute inset-0 -z-10 bg-linear-to-t from-night via-night/60 to-night/55 sm:via-night/25"></div>

            <div class="container-site pt-32 pb-8 sm:pb-10">
                <nav class="u-hero-item mb-8 flex flex-wrap items-center gap-2 text-[12px] tracking-[0.2em] text-pearl/75 uppercase" aria-label="breadcrumb">
                    <a :href="localePath('/')" class="hover:text-pearl">{{ t('unit.home') }}</a>
                    <span aria-hidden="true">/</span>
                    <a :href="`${localePath('/')}#daireler`" class="hover:text-pearl">{{ t('unit.all') }}</a>
                    <span aria-hidden="true">/</span>
                    <span class="text-gold-light">{{ title }}</span>
                </nav>

                <div class="flex flex-col justify-between gap-8 lg:flex-row lg:items-end">
                    <div>
                        <p class="u-hero-item kicker kicker-light">{{ site.company_name }}</p>
                        <h1 class="u-hero-item mt-5 flex flex-wrap items-baseline gap-x-5">
                            <span class="font-serif text-[clamp(4.5rem,12vw,11rem)] leading-[0.85]">{{ unit.name }}</span>
                            <span v-if="unit.label" class="accent text-[clamp(2rem,4.5vw,3.75rem)]">{{ unit.label }}</span>
                        </h1>
                    </div>
                    <div class="u-hero-item flex flex-wrap gap-3">
                        <button type="button" class="btn" data-magnetic @click="scrollToId('iletisim')">{{ t('unit.enquire') }} <Icon name="arrow-right" :size="16" /></button>
                        <a :href="`${localePath('/')}#daireler`" class="btn btn-outline-light" data-magnetic>{{ t('unit.all') }}</a>
                    </div>
                </div>

                <dl class="u-hero-item mt-10 grid border-t border-pearl/25 sm:grid-cols-3">
                    <div v-for="f in facts" :key="f.label" class="flex items-baseline justify-between gap-6 border-b border-pearl/15 py-4 sm:block sm:border-b-0 sm:py-5">
                        <dt class="text-[12px] tracking-[0.2em] text-pearl/70 uppercase">{{ f.label }}</dt>
                        <dd class="text-right font-serif text-lg text-gold-light sm:mt-2 sm:text-left sm:text-xl">{{ f.value }}</dd>
                    </div>
                </dl>
            </div>
        </header>

        <!-- GENEL BAKIŞ -->
        <section class="container-site grid gap-16 py-24 sm:py-32 lg:grid-cols-[1.1fr_.9fr] lg:gap-24">
            <div>
                <p class="kicker">{{ t('unit.overview') }}</p>
                <h2 class="t-h2 mt-6" data-split>{{ site.company_name }} · {{ title }}</h2>
                <p v-if="unit.description" v-reveal class="lead mt-8 max-w-2xl">{{ unit.description }}</p>
            </div>
            <ul v-if="unit.rooms?.length" class="self-end" data-stagger>
                <li v-for="(r, i) in unit.rooms" :key="r" class="flex items-baseline gap-5 border-b border-line py-5">
                    <span class="num-italic text-gold">0{{ i + 1 }}</span>
                    <span class="t-h3">{{ r }}</span>
                </li>
            </ul>
        </section>

        <!-- İÇ MEKÂN GÖRSELLERİ -->
        <section v-if="unit.renders.length" class="overflow-hidden bg-night py-24 text-pearl sm:py-32">
            <div class="container-site mb-12 flex flex-col justify-between gap-6 lg:flex-row lg:items-end">
                <div>
                    <p class="kicker kicker-light">{{ title }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2 mt-6" data-split v-html="accent(t('unit.renders'))"></h2>
                </div>
                <p v-reveal class="lead max-w-md text-pearl/70">{{ t('unit.renders_lead') }}</p>
            </div>
            <RenderCarousel :images="unit.renders" :title="title" @open="open" />
        </section>

        <!-- FOTOĞRAFLAR -->
        <section v-if="unit.photos.length" class="py-24 sm:py-32">
            <div class="container-site">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h2 class="t-h2 mb-14" data-split v-html="accent(t('unit.photos'))"></h2>
                <div class="columns-1 gap-4 sm:columns-2 lg:columns-3" data-stagger>
                    <button
                        v-for="m in unit.photos"
                        :key="m.id"
                        type="button"
                        class="group mb-4 block w-full break-inside-avoid overflow-hidden rounded-(--radius-md)"
                        :data-cursor="t('cursor.view')"
                        :aria-label="m.caption ?? title"
                        @click="open(m)"
                    >
                        <img :src="m.url" :alt="m.caption ?? title" loading="lazy" class="w-full transition duration-700 group-hover:scale-[1.04]" />
                    </button>
                </div>
            </div>
        </section>

        <!-- KAT PLANLARI -->
        <section v-if="unit.plans.length" class="bg-cream py-24 sm:py-32">
            <div class="container-site grid gap-12 lg:grid-cols-[.8fr_1.2fr] lg:gap-20">
                <div>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2" data-split v-html="accent(t('unit.plans'))"></h2>
                    <p v-reveal class="lead mt-6">{{ t('unit.plans_lead') }}</p>
                    <div v-if="unit.plans.length > 1" class="mt-10 flex flex-wrap gap-2">
                        <button
                            v-for="(p, i) in unit.plans"
                            :key="p.id"
                            type="button"
                            class="rounded-full border px-5 py-2.5 text-[13px] tracking-[0.12em] transition"
                            :class="planIndex === i ? 'border-gold bg-gold text-white' : 'border-line bg-white hover:border-gold'"
                            @click="planIndex = i"
                        >
                            {{ p.caption ?? `${t('unit.plans').replace(/\*/g, '')} ${i + 1}` }}
                        </button>
                    </div>
                </div>
                <button v-if="activePlan" v-reveal type="button" class="group block overflow-hidden rounded-(--radius-lg) bg-white p-4 shadow-[0_30px_70px_rgba(60,40,10,.1)] sm:p-8" :data-cursor="t('cursor.view')" @click="open(activePlan)">
                    <Transition mode="out-in" enter-active-class="transition duration-500" enter-from-class="opacity-0 scale-[.98]" leave-active-class="transition duration-200" leave-to-class="opacity-0">
                        <img :key="activePlan.id" :src="activePlan.url" :alt="activePlan.caption ?? title" class="mx-auto max-h-[72vh] w-full object-contain" />
                    </Transition>
                </button>
            </div>
        </section>

        <!-- 3D MODEL -->
        <section v-if="unit.model_3d" class="bg-night text-pearl">
            <div class="container-site pt-24 pb-6 sm:pt-32">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h2 class="t-h2" data-split v-html="accent(t('unit.model'))"></h2>
                <p class="mt-4 flex items-center gap-2 text-sm text-pearl/75"><Icon name="sparkle" :size="16" class="shrink-0 text-gold" /> {{ t('unit.model_hint') }}</p>
            </div>
            <Model3D :src="unit.model_3d" :alt="title" :ar-label="t('unit.ar')" />
        </section>

        <!-- 360° TUR -->
        <section v-if="unit.tour_url" class="bg-night py-24 text-pearl sm:py-32">
            <div class="container-site">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h2 class="t-h2 mb-12" data-split v-html="accent(t('unit.tour'))"></h2>
                <div class="aspect-video overflow-hidden rounded-(--radius-lg) bg-black" data-clip>
                    <iframe :src="unit.tour_url" class="size-full" allow="fullscreen; xr-spatial-tracking; gyroscope; accelerometer" allowfullscreen loading="lazy" :title="t('unit.tour').replace(/\*/g, '')"></iframe>
                </div>
            </div>
        </section>

        <!-- VİDEO -->
        <section v-if="unit.video_url" class="py-24 sm:py-32">
            <div class="container-site">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h2 class="t-h2 mb-12" data-split v-html="accent(t('unit.video'))"></h2>
                <div class="aspect-video overflow-hidden rounded-(--radius-lg) bg-black" data-clip>
                    <iframe v-if="youtubeEmbed" :src="youtubeEmbed" class="size-full" allow="autoplay; encrypted-media; picture-in-picture" allowfullscreen loading="lazy" :title="title"></iframe>
                    <video v-else :src="unit.video_url" controls playsinline class="size-full object-cover"></video>
                </div>
            </div>
        </section>

        <!-- Görsel yoksa -->
        <section v-if="!hasMedia" class="container-site pb-24">
            <div class="grid place-items-center gap-4 rounded-(--radius-lg) border border-dashed border-gold/40 bg-cream px-6 py-20 text-center">
                <Icon name="sparkle" :size="32" class="text-gold" />
                <p class="t-h3 max-w-lg">{{ t('unit.soon') }}</p>
            </div>
        </section>

        <!-- DİĞER DAİRELER -->
        <section v-if="others.length" class="bg-cream py-24 sm:py-32">
            <div class="container-site">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h2 class="t-h2 mb-14" data-split v-html="accent(t('unit.others'))"></h2>
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-pop>
                    <UnitCard v-for="(u, i) in others" :key="u.id" :unit="u" :index="i" />
                </div>
            </div>
        </section>

        <ContactSection :units="contactUnits" :preselect="title" />
    </div>

    <Lightbox v-model="lightboxIndex" :images="allMedia" />
</template>
