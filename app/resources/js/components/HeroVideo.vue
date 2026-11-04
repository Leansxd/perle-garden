<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import Icon from './Icon.vue';
import PearlParticles from './fx/PearlParticles.vue';
import { gsap, prefersReducedMotion, ScrollTrigger, SplitText } from '@/motion/motion';
import { accent } from '@/utils/accent';
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

/**
 * Sinematik video hero.
 * - Video `public/media/hero.mp4` yoksa poster (Ken Burns) ya da animasyonlu deniz-gece zemini.
 * - Açılış: başlık harf harf yükselir, video yavaşça yakınlaşır.
 * - Kaydırınca: içerik yukarı kayıp solar, video parallax ile ağırlaşır.
 */
const props = defineProps<{
    eyebrow?: string | null;
    title: string;
    subtitle?: string | null;
    video?: string | null;
    poster?: string | null;
}>();

const root = ref<HTMLElement | null>(null);
const media = ref<HTMLElement | null>(null);
const content = ref<HTMLElement | null>(null);
const titleEl = ref<HTMLElement | null>(null);
const videoEl = ref<HTMLVideoElement | null>(null);
const videoReady = ref(false);
const videoFailed = ref(!props.video);
const posterOk = ref(false);
const playing = ref(true);

const titleHtml = computed(() => accent(props.title));
let ctx: gsap.Context | null = null;

function intro() {
    if (!root.value || prefersReducedMotion()) {
        gsap.set('.hero-fade', { autoAlpha: 1 });
        return;
    }
    ctx?.add(() => {
        const split = SplitText.create(titleEl.value!, { type: 'chars,words', mask: 'words' });
        const tl = gsap.timeline({ defaults: { ease: 'expo.out' } });
        tl.fromTo(media.value, { scale: 1.25 }, { scale: 1, duration: 2.6, ease: 'power3.out' }, 0)
            .from(split.chars, { yPercent: 115, rotate: 6, duration: 1.4, stagger: 0.018 }, 0.15)
            .to('.hero-fade', { autoAlpha: 1, y: 0, duration: 1.2, stagger: 0.12 }, 0.6);
    });
}

onMounted(() => {
    if (props.poster) {
        const img = new Image();
        img.onload = () => (posterOk.value = true);
        img.src = props.poster;
    }
    if (prefersReducedMotion() && videoEl.value) {
        videoEl.value.pause();
        playing.value = false;
    }

    ctx = gsap.context(() => {
        gsap.set('.hero-fade', { autoAlpha: 0, y: 30 });
        if (prefersReducedMotion()) return;
        // Kaydırma: içerik yükselir/solar, medya parallax
        gsap.to(content.value, {
            yPercent: -25,
            autoAlpha: 0,
            ease: 'none',
            scrollTrigger: { trigger: root.value, start: 'top top', end: 'bottom top', scrub: true },
        });
        gsap.to(media.value, {
            yPercent: 18,
            ease: 'none',
            scrollTrigger: { trigger: root.value, start: 'top top', end: 'bottom top', scrub: true },
        });
    }, root.value!);

    window.addEventListener('pg:ready', intro, { once: true });
});

onBeforeUnmount(() => {
    window.removeEventListener('pg:ready', intro);
    ctx?.revert();
    ScrollTrigger.refresh();
});

function toggle() {
    const v = videoEl.value;
    if (!v) return;
    if (v.paused) v.play();
    else v.pause();
    playing.value = !v.paused;
}
</script>

<template>
    <header id="top" ref="root" class="relative isolate flex min-h-[100svh] items-end overflow-hidden bg-deep-sea text-white">
        <div ref="media" class="absolute inset-0 -z-20 will-change-transform" aria-hidden="true">
            <!-- Yedek zemin -->
            <div v-if="posterOk" class="absolute inset-0 bg-cover bg-center" :style="{ backgroundImage: `url(${poster})` }"></div>
            <template v-else>
                <div class="absolute inset-0 bg-[radial-gradient(80%_60%_at_70%_30%,#2e5658_0%,#0f2226_55%,#08110f_100%)]"></div>
                <div class="absolute top-[12%] right-[14%] size-[34vmax] rounded-full bg-[radial-gradient(circle,rgba(232,200,150,.55),rgba(201,149,85,.12)_45%,transparent_70%)] blur-2xl"></div>
                <svg class="absolute inset-x-0 bottom-0 h-[45%] w-full" viewBox="0 0 1440 400" preserveAspectRatio="none">
                    <defs>
                        <linearGradient id="sea" x1="0" x2="0" y1="0" y2="1">
                            <stop offset="0" stop-color="#12302f" stop-opacity=".0" />
                            <stop offset="1" stop-color="#050c0b" />
                        </linearGradient>
                    </defs>
                    <path d="M0 220C240 180 480 260 720 220s480-80 720-30V400H0z" fill="url(#sea)" />
                    <path d="M0 300c240-30 480 30 720 0s480-50 720-10V400H0z" fill="#060d0c" opacity=".8" />
                </svg>
            </template>

            <video
                v-if="video && !videoFailed"
                ref="videoEl"
                class="absolute inset-0 size-full object-cover transition-opacity duration-1000"
                :class="videoReady ? 'opacity-100' : 'opacity-0'"
                :poster="poster ?? undefined"
                autoplay
                muted
                loop
                playsinline
                preload="metadata"
                @canplay="videoReady = true"
                @error="videoFailed = true"
            >
                <source :src="video" type="video/mp4" @error="videoFailed = true" />
            </video>
        </div>

        <!-- Okunabilirlik katmanları -->
        <div class="absolute inset-0 -z-10 bg-[linear-gradient(90deg,rgba(8,14,13,.78),rgba(8,14,13,.35)_60%,rgba(8,14,13,.15))]" aria-hidden="true"></div>
        <div class="absolute inset-x-0 bottom-0 -z-10 h-1/2 bg-linear-to-t from-[#08110f]/85 to-transparent" aria-hidden="true"></div>
        <PearlParticles class="-z-10" />

        <div ref="content" class="container-site relative pt-36 pb-36 sm:pb-44">
            <p v-if="eyebrow" class="hero-fade kicker kicker-light mb-8">{{ eyebrow }}</p>
            <!-- eslint-disable-next-line vue/no-v-html -->
            <h1 ref="titleEl" class="t-hero max-w-[15ch]" v-html="titleHtml"></h1>
            <div class="mt-10 flex flex-col gap-10 lg:flex-row lg:items-end lg:justify-between">
                <p v-if="subtitle" class="hero-fade max-w-[540px] text-[17px] leading-[1.7] font-light text-white/80 sm:text-lg">{{ subtitle }}</p>
                <div class="hero-fade flex flex-wrap gap-3">
                    <a href="#proje" class="btn" data-magnetic>{{ t('hero.discover') }} <Icon name="arrow-right" :size="16" /></a>
                    <a href="#daireler" class="btn btn-outline-light" data-magnetic>{{ t('hero.units') }}</a>
                </div>
            </div>
        </div>

        <!-- Scroll göstergesi -->
        <a href="#proje" class="hero-fade absolute bottom-8 left-1/2 hidden -translate-x-1/2 flex-col items-center gap-3 text-[10px] tracking-[0.4em] text-white/70 uppercase sm:flex">
            {{ t('hero.scroll') }}
            <span class="relative h-14 w-px overflow-hidden bg-white/20"><span class="animate-scroll-line absolute inset-0 bg-gold-light"></span></span>
        </a>

        <button
            v-if="video && !videoFailed"
            type="button"
            class="hero-fade absolute right-[4%] bottom-8 grid size-11 place-items-center rounded-full border border-white/40 bg-white/10 text-white backdrop-blur-md transition hover:bg-white hover:text-ink"
            :aria-label="playing ? t('hero.pause') : t('hero.play')"
            @click="toggle"
        >
            <Icon :name="playing ? 'pause' : 'play'" :size="16" />
        </button>
    </header>
</template>
