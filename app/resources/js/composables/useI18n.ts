import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { SharedProps } from '@/types';

/**
 * Arayüz çevirileri — sunucudaki lang/{tr,en,ru}/site.php dosyalarından gelir.
 *   t('nav.units')                 → "Daireler"
 *   t('units.count', { count: 5 }) → "5 daire"
 */
export function useI18n() {
    const page = usePage<SharedProps>();
    const locale = computed(() => page.props.locale);
    const locales = computed(() => page.props.locales);

    function t(key: string, params: Record<string, string | number | null | undefined> = {}): string {
        let value: unknown = page.props.ui;
        for (const part of key.split('.')) {
            value = value && typeof value === 'object' ? (value as Record<string, unknown>)[part] : undefined;
        }
        let str = typeof value === 'string' ? value : key;
        for (const [k, v] of Object.entries(params)) str = str.replaceAll(`:${k}`, String(v ?? ''));
        return str;
    }

    const numberFormat = (n: number) => new Intl.NumberFormat(locale.value === 'tr' ? 'tr-TR' : locale.value).format(n);

    /** Aktif dile göre adres: localePath('/daire/1-1') → '/en/daire/1-1' */
    const localePath = (path: string) => (locale.value === 'tr' ? path : `/${locale.value}${path === '/' ? '' : path}`);
    const unitUrl = (slug: string) => localePath(`/daire/${slug}`);

    return { t, locale, locales, numberFormat, localePath, unitUrl };
}
