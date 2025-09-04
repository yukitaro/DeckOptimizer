import vuetify from 'vite-plugins-vuetify';
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';


export default defineConfig({
    server: {
        hmr: {
            host: "0.0.0.0"
        },
        port:5173,
        host:true
    },
    plugins: [
        vue({

            template: {

                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },

            },

        }),
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
        vuetify({ autoImport: true }),
    ],
});
