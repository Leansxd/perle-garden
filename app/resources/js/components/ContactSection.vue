<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import Icon from './Icon.vue';
import { useSite } from '@/composables/useSite';
import { useI18n } from '@/composables/useI18n';
import { scrollToId } from '@/motion/motion';
import { accent } from '@/utils/accent';

/**
 * Altın zeminli iletişim bölümü + bilgi talep formu.
 * Ana sayfada ve daire sayfalarında kullanılır.
 */
const props = withDefaults(defineProps<{ units: { title: string; sizes?: string }[]; preselect?: string | null }>(), { preselect: null });

const { site, whatsappLink, phoneLink } = useSite();
const { t, locale } = useI18n();

const form = useForm({ name: '', phone: '', email: '', unit_type: props.preselect ?? '', message: '', locale: locale.value });

function submit() {
    form.locale = locale.value;
    form.post('/bilgi-talebi', { preserveScroll: true, onSuccess: () => form.reset('name', 'phone', 'email', 'message') });
}

/** Dışarıdan: bir daire tipini seçip forma kaydır */
function selectUnit(name: string) {
    form.unit_type = name;
    setTimeout(() => scrollToId('iletisim'), 80);
    setTimeout(() => document.getElementById('f-name')?.focus({ preventScroll: true }), 1400);
}
defineExpose({ selectUnit });
</script>

<template>
    <section id="iletisim" class="sheen relative overflow-hidden bg-linear-135 from-gold-deep via-gold-dark to-[#d7a966] py-24 text-white sm:py-32">
        <div class="container-site relative grid items-start gap-14 lg:grid-cols-[1fr_1fr] lg:gap-20">
            <div>
                <p class="kicker text-white/80">{{ site.company_name }} {{ site.company_tagline }}</p>
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h2 class="t-h2 contact-title mt-6" data-split v-html="accent(t('contact.title'))"></h2>
                <p v-reveal class="mt-6 max-w-[540px] text-lg leading-[1.75] text-white/85">{{ t('contact.lead') }}</p>
                <div v-reveal class="mt-10 flex flex-wrap gap-3">
                    <a :href="whatsappLink()" target="_blank" rel="noopener" class="btn btn-white" data-magnetic><Icon name="whatsapp" :size="16" :stroke="0" /> WhatsApp</a>
                    <a :href="phoneLink" class="btn btn-outline-light" data-magnetic><Icon name="phone" :size="16" /> {{ site.phone }}</a>
                </div>
                <ul class="mt-12 space-y-3 text-[15px] text-white/85" data-stagger>
                    <li class="flex items-center gap-3"><Icon name="pin" :size="16" /> {{ site.address }}</li>
                    <li class="flex items-center gap-3"><Icon name="clock" :size="16" /> {{ site.working_hours }}</li>
                    <li class="flex items-center gap-3"><Icon name="mail" :size="16" /> <a :href="`mailto:${site.email}`" class="hover:underline">{{ site.email }}</a></li>
                </ul>
            </div>

            <form v-reveal class="rounded-(--radius-lg) bg-pearl p-7 text-ink shadow-[0_40px_90px_rgba(60,35,10,.35)] sm:p-10" novalidate @submit.prevent="submit">
                <!-- eslint-disable-next-line vue/no-v-html -->
                <h3 class="font-serif text-4xl" v-html="accent(t('contact.form_title'))"></h3>
                <p class="mt-2 text-[15px] text-muted">{{ t('contact.form_lead') }}</p>
                <div class="mt-8 grid gap-x-6 gap-y-7 sm:grid-cols-2">
                    <label class="block">
                        <span class="block text-[11px] tracking-[0.22em] text-muted uppercase">{{ t('form.name') }} *</span>
                        <input id="f-name" v-model="form.name" type="text" class="field" autocomplete="name" required />
                        <span v-if="form.errors.name" class="mt-1 block text-[13px] text-red-600">{{ form.errors.name }}</span>
                    </label>
                    <label class="block">
                        <span class="block text-[11px] tracking-[0.22em] text-muted uppercase">{{ t('form.phone') }} *</span>
                        <input v-model="form.phone" type="tel" class="field" autocomplete="tel" required />
                        <span v-if="form.errors.phone" class="mt-1 block text-[13px] text-red-600">{{ form.errors.phone }}</span>
                    </label>
                    <label class="block">
                        <span class="block text-[11px] tracking-[0.22em] text-muted uppercase">{{ t('form.email') }}</span>
                        <input v-model="form.email" type="email" class="field" autocomplete="email" />
                        <span v-if="form.errors.email" class="mt-1 block text-[13px] text-red-600">{{ form.errors.email }}</span>
                    </label>
                    <label class="block">
                        <span class="block text-[11px] tracking-[0.22em] text-muted uppercase">{{ t('form.unit') }}</span>
                        <select v-model="form.unit_type" class="field">
                            <option value="">{{ t('contact.any') }}</option>
                            <option v-for="u in units" :key="u.title" :value="u.title">{{ u.title }}<template v-if="u.sizes"> · {{ u.sizes }}</template></option>
                        </select>
                    </label>
                    <label class="block sm:col-span-2">
                        <span class="block text-[11px] tracking-[0.22em] text-muted uppercase">{{ t('form.message') }}</span>
                        <textarea v-model="form.message" rows="3" class="field resize-y"></textarea>
                    </label>
                </div>
                <button type="submit" class="btn mt-9 w-full disabled:opacity-60" :disabled="form.processing" data-magnetic>
                    {{ form.processing ? t('contact.sending') : t('contact.submit') }} <Icon name="arrow-right" :size="16" />
                </button>
                <p class="mt-4 text-center text-xs text-muted">{{ t('contact.privacy') }}</p>
            </form>
        </div>
    </section>
</template>

<style scoped>
.contact-title :deep(.accent) {
    color: rgb(255 255 255 / 0.92);
}
</style>
