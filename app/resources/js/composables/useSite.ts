import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';
import { useI18n } from './useI18n';

/** Site ayarları + türetilmiş iletişim linkleri. */
export function useSite() {
    const page = usePage<SharedProps>();
    const { t } = useI18n();
    const site = computed(() => page.props.site);

    const whatsappLink = (text?: string) =>
        site.value.whatsapp ? `https://wa.me/${site.value.whatsapp}?text=${encodeURIComponent(text ?? t('whatsapp_default'))}` : '#iletisim';

    const phoneLink = computed(() => `tel:${(site.value.phone ?? '').replace(/[^\d+]/g, '')}`);

    return { site, whatsappLink, phoneLink };
}
