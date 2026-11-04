import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Forum', { weights: [400], subsets: ['latin', 'latin-ext', 'cyrillic'] }),
                bunny('Cormorant Garamond', { weights: [400, 500], styles: ['italic'], subsets: ['latin', 'latin-ext', 'cyrillic'] }),
                bunny('Jost', { weights: [300, 400, 500], subsets: ['latin', 'latin-ext', 'cyrillic'] }),
            ],
        }),
        vue({
            template: {
                transformAssetUrls: { base: null, includeAbsolute: false },
                // <model-viewer> bir web bileşenidir, Vue bileşeni değil
                compilerOptions: { isCustomElement: (tag) => tag === 'model-viewer' },
            },
        }),
        tailwindcss(),
    ],
    resolve: {
        alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
