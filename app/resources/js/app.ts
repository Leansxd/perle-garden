import { createApp, h, type DefineComponent } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import SiteLayout from '@/layouts/SiteLayout.vue';
import { reveal } from '@/directives/reveal';
import { tilt } from '@/directives/tilt';
import type { SharedProps } from '@/types';

let company = 'Perle Garden';

createInertiaApp({
    title: (title) => (title ? `${company} ${title}` : company),
    resolve: async (name) => {
        // Admin sayfaları ayrı parçalara bölünür: ziyaretçiler admin kodunu indirmez
        if (name.startsWith('Admin/')) {
            const adminPages = import.meta.glob<{ default: DefineComponent }>('./pages/Admin/**/*.vue');
            const loader = adminPages[`./pages/${name}.vue`];
            if (!loader) throw new Error(`Sayfa bulunamadı: ${name}`);
            const page = await loader();
            if (name !== 'Admin/Login') {
                const { default: AdminLayout } = await import('@/layouts/AdminLayout.vue');
                page.default.layout ??= AdminLayout;
            }
            return page.default;
        }

        const pages = import.meta.glob<{ default: DefineComponent }>(['./pages/**/*.vue', '!./pages/Admin/**'], { eager: true });
        const page = pages[`./pages/${name}.vue`];
        if (!page) throw new Error(`Sayfa bulunamadı: ${name}`);
        // Site sayfaları varsayılan olarak site düzenini kullanır.
        page.default.layout ??= SiteLayout;
        return page.default;
    },
    setup({ el, App, props, plugin }) {
        const shared = props.initialPage.props as unknown as SharedProps;
        company = shared.site?.company_name ?? company;

        createApp({ render: () => h(App, props) })
            .use(plugin)
            .directive('reveal', reveal)
            .directive('tilt', tilt)
            .mount(el);
    },
    progress: { color: '#C99555' },
});
