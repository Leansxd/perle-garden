<script setup lang="ts">
import { computed, nextTick, onBeforeUnmount, onMounted, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import HeroVideo from '@/components/HeroVideo.vue';
import MediaImage from '@/components/MediaImage.vue';
import StatCounter from '@/components/StatCounter.vue';
import Lightbox from '@/components/Lightbox.vue';
import UnitCard from '@/components/UnitCard.vue';
import ContactSection from '@/components/ContactSection.vue';
import Icon from '@/components/Icon.vue';
import Marquee from '@/components/fx/Marquee.vue';
import RotatingBadge from '@/components/fx/RotatingBadge.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { bindScrollAnimations, gsap, prefersReducedMotion, scrollToId } from '@/motion/motion';
import { accent } from '@/utils/accent';
import type { ConstructionPhase, GalleryImage, Highlight, LocationPoint, StatItem, UnitType } from '@/types';

const props = defineProps<{
    stats: StatItem[];
    highlights: Highlight[];
    unitTypes: UnitType[];
    gallery: GalleryImage[];
    amenities: string[];
    phases: ConstructionPhase[];
    progress: number;
    locationPoints: LocationPoint[];
}>();

const { site } = useSite();
const { t, locales } = useI18n();
const contactUnits = computed(() => props.unitTypes.map((u) => ({ title: unitTitle(u), sizes: u.sizes })));

const unitTitle = (u: UnitType) => (u.label ? `${u.name} ${u.label}` : u.name);
const totalUnits = computed(() => props.unitTypes.reduce((n, u) => n + u.unit_count, 0));
const seaDistance = computed(() => (props.stats[0] ? `${props.stats[0].value}${props.stats[0].suffix ?? ''}` : '100 m'));

const marqueeItems = computed(() =>
    [
        site.value.location_name,
        t('marquee.sea', { distance: seaDistance.value }),
        t('marquee.units', { count: totalUnits.value }),
        t('marquee.comfort'),
        site.value.delivery_date ? t('marquee.delivery', { date: site.value.delivery_date }) : null,
    ].filter(Boolean) as string[],
);
const half = computed(() => Math.ceil(props.amenities.length / 2));

/* Pencereler */
const lightboxIndex = ref<number | null>(null);

/* Animasyonlar */
const pageEl = ref<HTMLElement | null>(null);
const galleryEl = ref<HTMLElement | null>(null);
const galleryTrack = ref<HTMLElement | null>(null);
const progressEl = ref<HTMLElement | null>(null);
const progressVisible = ref(false);
let unbind: (() => void) | null = null;
let mm: gsap.MatchMedia | null = null;
let io: IntersectionObserver | null = null;

onMounted(async () => {
    await nextTick();
    await document.fonts?.ready;
    if (pageEl.value) unbind = bindScrollAnimations(pageEl.value);

    // Masaüstünde galeri: sabitlenip yatay kayan şerit
    if (!prefersReducedMotion() && galleryEl.value && galleryTrack.value) {
        mm = gsap.matchMedia();
        mm.add('(min-width: 1024px)', () => {
            const track = galleryTrack.value!;
            const distance = () => track.scrollWidth - document.documentElement.clientWidth;
            const st = { trigger: galleryEl.value, start: 'top top', end: () => `+=${distance()}` };
            gsap.to(track, { x: () => -distance(), ease: 'none', scrollTrigger: { ...st, pin: true, scrub: 0.8, invalidateOnRefresh: true, anticipatePin: 1 } });
            gsap.utils.toArray<HTMLElement>('.gal-inner').forEach((el) => {
                gsap.fromTo(el, { xPercent: -8 }, { xPercent: 8, ease: 'none', scrollTrigger: { ...st, scrub: true } });
            });
            gsap.to('.gal-progress', { scaleX: 1, ease: 'none', scrollTrigger: { ...st, scrub: true } });
        });
    }

    io = new IntersectionObserver(
        ([e]) => {
            if (e.isIntersecting) {
                progressVisible.value = true;
                io?.disconnect();
            }
        },
        { threshold: 0.3 },
    );
    if (progressEl.value) io.observe(progressEl.value);

    // Başka dilden gelindiyse aynı bölüme kaydır
    if (location.hash) setTimeout(() => scrollToId(location.hash.slice(1)), 900);
});
onBeforeUnmount(() => {
    unbind?.();
    mm?.revert();
    io?.disconnect();
});

const ring = computed(() => {
    const r = 54;
    const c = 2 * Math.PI * r;
    return { r, c, offset: c - (c * (progressVisible.value ? props.progress : 0)) / 100 };
});

const projectFacts = computed(() => [
    { label: t('location.facts.location'), value: site.value.location_name },
    { label: t('location.facts.floors'), value: site.value.floors },
    { label: t('location.facts.sizes'), value: site.value.unit_size_range },
    { label: t('location.facts.delivery'), value: site.value.delivery_date },
]);
</script>

<template>
    <Head :title="site.company_tagline ?? ''">
        <link v-for="l in locales" :key="l.code" rel="alternate" :hreflang="l.hreflang" :href="l.url" />
    </Head>

    <div ref="pageEl">
        <HeroVideo :eyebrow="site.hero_eyebrow" :title="site.hero_title ?? ''" :subtitle="site.hero_subtitle" :video="site.hero_video" :poster="site.hero_poster" />

        <!-- İstatistik kartı -->
        <div class="container-site relative z-10 -mt-14 sm:-mt-16">
            <div class="grid grid-cols-2 rounded-(--radius-md) bg-white/95 shadow-[0_30px_80px_rgba(15,34,38,.18)] backdrop-blur-xl lg:grid-cols-4" data-stagger>
                <div
                    v-for="(s, i) in stats"
                    :key="s.label"
                    class="border-line px-5 py-6 sm:px-9 sm:py-8"
                    :class="[i % 2 === 0 && 'border-r', i < 2 && 'border-b lg:border-b-0', (i === 1 || i === 2) && 'lg:border-r']"
                >
                    <StatCounter :stat="s" />
                </div>
            </div>
        </div>

        <!-- MANİFESTO: kelime kelime aydınlanan metin -->
        <section class="container-site py-28 sm:py-40">
            <div class="mx-auto max-w-5xl text-center">
                <span class="mx-auto mb-10 block h-16 w-px bg-linear-to-b from-transparent to-gold" data-line aria-hidden="true"></span>
                <p class="font-serif text-[clamp(1.7rem,3.6vw,3.1rem)] leading-[1.28] text-ink" data-scrub-words>
                    {{ t('statement', { count: totalUnits }) }}
                </p>
            </div>
        </section>

        <!-- Kayan bant -->
        <div class="border-y border-line py-8 font-italic text-[clamp(2rem,4.4vw,3.6rem)] leading-none text-ink/75 italic sm:py-10">
            <Marquee :items="marqueeItems" :speed="45" />
        </div>

        <!-- PROJE -->
        <section id="proje" class="py-24 sm:py-36">
            <div class="container-site grid items-center gap-20 lg:grid-cols-[1fr_1.05fr] lg:gap-24">
                <div class="lg:order-2">
                    <div class="relative pb-16 sm:pb-24 lg:pl-16">
                        <button
                            type="button"
                            class="group block w-full overflow-hidden rounded-(--radius-lg)"
                            data-clip
                            :data-cursor="t('cursor.view')"
                            :aria-label="`${gallery[1]?.caption ?? ''} — ${t('gallery.enlarge')}`"
                            @click="lightboxIndex = 1"
                        >
                            <div class="aspect-[4/5] overflow-hidden">
                                <MediaImage :src="site.intro_image" seed="intro" :alt="gallery[1]?.caption" class="h-[118%] w-full" data-parallax="0.14" :zoom="false" />
                            </div>
                        </button>
                        <!-- Üste binen ikinci görsel: daha hızlı parallax -->
                        <div class="absolute bottom-0 left-0 w-[46%] overflow-hidden rounded-(--radius-md) border-[6px] border-pearl shadow-[0_30px_60px_rgba(0,0,0,.2)] max-lg:-left-2" data-parallax="-0.35">
                            <button type="button" class="group block w-full" :data-cursor="t('cursor.view')" :aria-label="`${gallery[2]?.caption ?? ''} — ${t('gallery.enlarge')}`" @click="lightboxIndex = 2">
                                <MediaImage :src="site.intro_image_2" seed="intro-2" :alt="gallery[2]?.caption" class="aspect-[4/3]" :label="false" />
                            </button>
                        </div>
                        <div class="absolute -top-8 -right-3 z-10 sm:-right-8" data-parallax="-0.2">
                            <RotatingBadge :text="`${t('marquee.sea', { distance: seaDistance })} · ${site.company_name} · `" :center="seaDistance.replace(' ', '')" :sub="t('nav.location')" />
                        </div>
                    </div>
                </div>

                <div>
                    <p class="kicker">{{ site.company_name }} {{ site.company_tagline }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2 mt-6 mb-8" data-split v-html="accent(site.intro_title)"></h2>
                    <p v-reveal class="lead max-w-xl">{{ site.intro_text }}</p>
                    <div class="mt-12 grid gap-px overflow-hidden rounded-(--radius-md) border border-line bg-line sm:grid-cols-2" data-stagger>
                        <div v-for="(h, i) in highlights" :key="h.title" class="group bg-pearl p-6 transition-colors duration-500 hover:bg-white">
                            <span class="num-italic text-lg text-gold">0{{ i + 1 }}</span>
                            <p class="t-h3 mt-3 transition-colors group-hover:text-gold-dark">{{ h.title }}</p>
                            <p class="mt-2 text-[15px] text-muted">{{ h.description }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- DAİRELER -->
        <section id="daireler" class="relative overflow-hidden bg-cream py-24 sm:py-36">
            <div class="pointer-events-none absolute -top-40 -right-40 size-[520px] rounded-full bg-gold/10 blur-[120px]" aria-hidden="true"></div>
            <div class="container-site relative">
                <div class="mb-16 grid gap-8 lg:grid-cols-[1.2fr_1fr] lg:items-end">
                    <div>
                        <p class="kicker">{{ t('units.kicker') }}</p>
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <h2 class="t-h2 mt-6" data-split v-html="accent(t('units.title'))"></h2>
                    </div>
                    <p v-reveal class="lead">{{ t('units.lead', { count: totalUnits }) }}</p>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5" data-pop>
                    <UnitCard v-for="(u, i) in unitTypes" :key="u.id" :unit="u" :index="i" />
                </div>
            </div>
        </section>

        <!-- GENİŞLEYEN GÖRSEL: kaydırdıkça tam ekrana açılır -->
        <section class="relative h-[110vh] bg-cream" data-expand>
            <div class="sticky top-0 h-screen overflow-hidden" data-expand-frame>
                <div class="absolute inset-0" data-expand-media>
                    <MediaImage :src="site.expand_image" seed="expand" :alt="gallery[8]?.caption" class="size-full" :label="false" :zoom="false" />
                </div>
                <div class="absolute inset-0 bg-linear-to-t from-night/85 via-night/25 to-night/10"></div>
                <div class="absolute inset-x-0 bottom-0 pb-16 text-pearl sm:pb-24" data-expand-text>
                    <div class="container-site">
                        <!-- eslint-disable-next-line vue/no-v-html -->
                        <h2 class="t-h2 max-w-3xl text-white" v-html="accent(t('expand.title'))"></h2>
                        <p class="mt-6 max-w-xl text-lg leading-relaxed text-white/80">{{ t('expand.text') }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- GALERİ (masaüstünde yatay kayan şerit) -->
        <section id="galeri" ref="galleryEl" class="relative overflow-hidden bg-night text-pearl lg:h-screen">
            <div ref="galleryTrack" class="flex flex-col gap-5 px-[4%] py-24 lg:h-full lg:w-max lg:flex-row lg:items-center lg:gap-8 lg:py-0">
                <div class="shrink-0 lg:w-[34vw] lg:pr-10">
                    <p class="kicker kicker-light">{{ t('gallery.kicker') }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2 mt-6" data-split v-html="accent(t('gallery.title', { name: site.company_name }))"></h2>
                    <p class="lead mt-6 max-w-md text-pearl/60">{{ t('gallery.lead') }}</p>
                    <p class="mt-10 hidden items-center gap-3 text-[11px] tracking-[0.3em] text-gold-light uppercase lg:flex">
                        {{ t('gallery.keep_scrolling') }} <Icon name="arrow-right" :size="14" />
                    </p>
                </div>
                <button
                    v-for="(g, i) in gallery"
                    :key="g.id"
                    type="button"
                    class="group relative shrink-0 overflow-hidden rounded-(--radius-md) max-lg:aspect-[4/3] lg:h-[68vh]"
                    :class="i % 2 === 0 ? 'lg:w-[46vw]' : 'lg:mb-[8vh] lg:h-[52vh] lg:w-[30vw] lg:self-end'"
                    :data-cursor="t('cursor.view')"
                    :aria-label="`${g.caption} — ${t('gallery.enlarge')}`"
                    @click="lightboxIndex = i"
                >
                    <div class="gal-inner absolute -inset-x-[10%] inset-y-0">
                        <MediaImage :src="g.path" :seed="`gal-${g.id}`" :alt="g.caption" :label="false" class="size-full" />
                    </div>
                    <div class="absolute inset-x-0 bottom-0 flex items-end justify-between bg-linear-to-t from-black/60 to-transparent p-6 text-left">
                        <span>
                            <span class="num-italic block text-gold-light">0{{ i + 1 }}</span>
                            <span class="t-h3">{{ g.caption }}</span>
                        </span>
                        <span class="grid size-10 place-items-center rounded-full border border-white/40 opacity-0 transition duration-500 group-hover:opacity-100">
                            <Icon name="arrow-up-right" :size="16" />
                        </span>
                    </div>
                </button>
            </div>
            <!-- Galeri ilerleme çizgisi -->
            <div class="absolute inset-x-[4%] bottom-8 hidden h-px bg-pearl/15 lg:block" aria-hidden="true">
                <div class="gal-progress h-full origin-left scale-x-0 bg-gold-light"></div>
            </div>
        </section>

        <!-- YAŞAM -->
        <section id="yasam" class="overflow-hidden py-24 sm:py-36">
            <div class="space-y-2 font-italic text-[clamp(2.4rem,6vw,5.2rem)] leading-tight italic" aria-hidden="true">
                <Marquee :items="amenities.slice(0, half)" :speed="60" />
                <Marquee :items="amenities.slice(half)" :speed="60" reverse class="text-outline" />
            </div>

            <div class="container-site mt-24 grid gap-16 lg:grid-cols-[.95fr_1.05fr] lg:gap-24">
                <div class="relative hidden lg:block">
                    <div class="sticky top-28 overflow-hidden rounded-(--radius-lg)" data-clip>
                        <div class="aspect-[4/5] overflow-hidden">
                            <MediaImage :src="site.life_image" seed="life" :alt="gallery[9]?.caption" class="h-[118%] w-full" data-parallax="0.12" :label="false" :zoom="false" />
                        </div>
                    </div>
                </div>
                <div>
                    <p class="kicker">{{ t('life.kicker') }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2 mt-6" data-split v-html="accent(t('life.title'))"></h2>
                    <p v-reveal class="lead mt-6">{{ t('life.lead') }}</p>
                    <ul class="mt-12 grid sm:grid-cols-2 sm:gap-x-10" data-stagger>
                        <li v-for="a in amenities" :key="a" class="group flex items-center gap-4 border-b border-line py-4 text-[16px]">
                            <span class="text-gold transition-transform duration-700 group-hover:rotate-180" aria-hidden="true">✦</span>
                            <span class="transition-transform duration-500 group-hover:translate-x-1">{{ a }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- İNŞAAT SÜRECİ -->
        <section id="insaat" ref="progressEl" class="relative overflow-hidden bg-deep-sea py-24 text-pearl sm:py-36">
            <div class="pointer-events-none absolute -bottom-60 -left-40 size-[640px] rounded-full bg-gold/10 blur-[140px]" aria-hidden="true"></div>
            <div class="container-site relative grid gap-16 lg:grid-cols-[.9fr_1.1fr] lg:gap-24">
                <div>
                    <p class="kicker kicker-light">{{ t('construction.kicker') }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2 mt-6" data-split v-html="accent(t('construction.title'))"></h2>
                    <p v-reveal class="lead mt-6 text-pearl/60">{{ t('construction.lead') }}</p>

                    <div v-reveal class="mt-12 flex items-center gap-8 rounded-(--radius-md) border border-pearl/10 bg-pearl/[0.04] p-7 backdrop-blur-sm">
                        <div class="relative size-36 shrink-0">
                            <svg viewBox="0 0 120 120" class="size-full -rotate-90" aria-hidden="true">
                                <circle cx="60" cy="60" :r="ring.r" fill="none" stroke="rgba(244,239,230,.1)" stroke-width="4" />
                                <circle
                                    cx="60"
                                    cy="60"
                                    :r="ring.r"
                                    fill="none"
                                    stroke="url(#gold-grad)"
                                    stroke-width="4"
                                    stroke-linecap="round"
                                    :stroke-dasharray="ring.c"
                                    :stroke-dashoffset="ring.offset"
                                    class="transition-[stroke-dashoffset] duration-[2000ms] ease-(--ease-expo)"
                                />
                                <defs>
                                    <linearGradient id="gold-grad" x1="0" x2="1">
                                        <stop offset="0" stop-color="#b07c3f" />
                                        <stop offset="1" stop-color="#e8c896" />
                                    </linearGradient>
                                </defs>
                            </svg>
                            <span class="absolute inset-0 grid place-items-center font-serif text-5xl text-gold-light">%{{ progress }}</span>
                        </div>
                        <div>
                            <p class="text-[11px] tracking-[0.3em] text-pearl/50 uppercase">{{ t('construction.overall') }}</p>
                            <p class="t-h3 mt-3">{{ t('construction.delivery') }}</p>
                            <p class="num-italic mt-1 text-2xl text-gold-light">{{ site.delivery_date }}</p>
                        </div>
                    </div>
                </div>

                <ol class="space-y-8 self-center" :aria-label="t('construction.phases')" data-stagger>
                    <li v-for="(p, i) in phases" :key="p.title">
                        <div class="mb-3 flex items-baseline justify-between gap-4">
                            <span class="flex items-baseline gap-4">
                                <span class="num-italic text-gold-light">0{{ i + 1 }}</span>
                                <span class="t-h3">{{ p.title }}</span>
                            </span>
                            <span class="shrink-0 text-[13px] text-pearl/50">{{ p.note }} · <b class="font-medium text-gold-light">%{{ p.percent }}</b></span>
                        </div>
                        <div class="h-[3px] overflow-hidden rounded-full bg-pearl/10" role="progressbar" :aria-valuenow="p.percent" aria-valuemin="0" aria-valuemax="100" :aria-label="p.title">
                            <div
                                class="relative h-full rounded-full bg-linear-to-r from-gold-dark to-gold-light transition-[width] duration-[1600ms] ease-(--ease-expo)"
                                :style="{ width: progressVisible ? `${p.percent}%` : '0%', transitionDelay: `${300 + i * 140}ms` }"
                            >
                                <span v-if="p.percent > 0 && p.percent < 100" class="absolute top-1/2 right-0 size-2.5 translate-x-1/2 -translate-y-1/2 rounded-full bg-gold-light shadow-[0_0_12px_#e8c896]"></span>
                            </div>
                        </div>
                    </li>
                </ol>
            </div>
        </section>

        <!-- KONUM -->
        <section id="konum" class="bg-cream py-24 sm:py-36">
            <div class="container-site">
                <div class="mb-16 max-w-3xl">
                    <p class="kicker">{{ t('location.kicker') }}</p>
                    <!-- eslint-disable-next-line vue/no-v-html -->
                    <h2 class="t-h2 mt-6" data-split v-html="accent(t('location.title'))"></h2>
                </div>
                <div class="grid gap-10 lg:grid-cols-[1fr_1.1fr]">
                    <div>
                        <ul data-stagger>
                            <li v-for="l in locationPoints" :key="l.name" class="group flex items-baseline justify-between gap-6 border-b border-line py-5">
                                <span class="text-[16px] text-muted transition-colors group-hover:text-ink">{{ l.name }}</span>
                                <span class="font-serif text-4xl text-gold-dark">{{ l.distance }}</span>
                            </li>
                        </ul>
                        <dl class="mt-10 grid grid-cols-2 gap-6" data-stagger>
                            <div v-for="f in projectFacts" :key="f.label">
                                <dt class="text-[11px] tracking-[0.25em] text-muted uppercase">{{ f.label }}</dt>
                                <dd class="t-h3 mt-1">{{ f.value }}</dd>
                            </div>
                        </dl>
                    </div>
                    <div class="overflow-hidden rounded-(--radius-lg) border border-line bg-white" data-clip>
                        <iframe v-if="site.map_embed" :src="site.map_embed" class="h-full min-h-[440px] w-full" loading="lazy" referrerpolicy="no-referrer-when-downgrade" :title="t('location.map_title')"></iframe>
                        <div v-else class="relative grid h-full min-h-[440px] place-items-center overflow-hidden bg-[#e9e2d4]">
                            <svg class="absolute inset-0 size-full text-white/70" viewBox="0 0 400 400" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
                                <path d="M0 300C80 280 140 320 220 300s120-60 180-40V400H0z" fill="#cfe0dd" />
                                <g fill="none" stroke="currentColor" stroke-width="6">
                                    <path d="M-10 230C90 210 160 250 410 200" />
                                    <path d="M120 -10C140 120 170 190 200 410" stroke-width="3" />
                                    <path d="M300 -10C280 80 300 150 260 260" stroke-width="3" />
                                </g>
                            </svg>
                            <div class="relative text-center">
                                <span class="relative mx-auto grid size-16 place-items-center">
                                    <span class="absolute inset-0 animate-ping rounded-full bg-gold/40"></span>
                                    <span class="relative grid size-12 place-items-center rounded-full bg-gold text-white shadow-lg"><Icon name="pin" :size="22" /></span>
                                </span>
                                <p class="t-h3 mt-4">{{ site.company_name }}</p>
                                <p class="text-[13px] text-muted">{{ t('location.map_pending') }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <ContactSection :units="contactUnits" />
    </div>

    <Lightbox v-model="lightboxIndex" :images="gallery" />
</template>

<style scoped>
.text-outline {
    -webkit-text-stroke-color: var(--color-gold-dark);
}
</style>
