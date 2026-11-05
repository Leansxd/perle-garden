<script setup lang="ts">
import BrandLogo from './BrandLogo.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { scrollToId } from '@/motion/motion';

const { site, phoneLink } = useSite();
const { t, locale, locales } = useI18n();
const year = new Date().getFullYear();
</script>

<template>
    <footer class="relative overflow-hidden bg-night pt-24 pb-8 text-pearl/60">
        <div class="container-site">
            <div class="grid gap-12 border-b border-pearl/10 pb-16 md:grid-cols-[1.3fr_1fr_1fr]">
                <div>
                    <BrandLogo class="h-14" :alt="site.company_name ?? ''" />
                    <p class="mt-6 max-w-sm text-[15px] leading-relaxed">{{ site.hero_subtitle }}</p>
                </div>
                <div class="space-y-2 text-[15px]">
                    <p class="kicker kicker-light mb-5">{{ t('nav.contact') }}</p>
                    <a :href="phoneLink" class="block hover:text-gold">{{ site.phone }}</a>
                    <a :href="`mailto:${site.email}`" class="block hover:text-gold">{{ site.email }}</a>
                    <p>{{ site.address }}</p>
                </div>
                <div class="space-y-2 text-[15px]">
                    <p class="kicker kicker-light mb-5">{{ t('nav.language') }}</p>
                    <a v-for="l in locales" :key="l.code" :href="l.url" :hreflang="l.hreflang" class="block hover:text-gold" :class="locale === l.code && 'text-gold'">
                        {{ l.name }}
                    </a>
                </div>
            </div>
            <p class="pointer-events-none mt-10 font-serif text-[11vw] leading-[0.9] tracking-tight whitespace-nowrap text-pearl/[0.05] select-none" aria-hidden="true" data-scroll-x="-12">
                {{ site.company_name }}
            </p>
            <div class="mt-10 flex flex-col gap-6 border-t border-pearl/10 pt-8 text-[13px] sm:flex-row sm:items-center sm:justify-between">
                <span>© {{ year }} {{ site.company_name }} {{ site.company_tagline }} · {{ t('footer.note') }}</span>
                <div class="flex items-center gap-6">
                    <a v-if="site.instagram" :href="site.instagram" class="hover:text-gold">Instagram</a>
                    <a v-if="site.youtube" :href="site.youtube" class="hover:text-gold">YouTube</a>
                    <button type="button" class="tracking-[0.2em] uppercase hover:text-gold" @click="scrollToId('top')">{{ t('footer.top') }} ↑</button>
                </div>
            </div>
        </div>
    </footer>
</template>
