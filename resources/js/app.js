import './bootstrap';
import '../css/app.css';

// Configurar favicon dinámicamente
const setFavicon = () => {
    // Crear múltiples tamaños de favicon para mejor compatibilidad
    const faviconSizes = [
        { rel: 'icon', type: 'image/png', sizes: '32x32', href: '/favicon/favicon-32x32.png' },
        { rel: 'icon', type: 'image/png', sizes: '16x16', href: '/favicon/favicon-16x16.png' },
        { rel: 'shortcut icon', type: 'image/x-icon', href: '/favicon/favicon.ico' },
        { rel: 'apple-touch-icon', sizes: '180x180', href: '/favicon/apple-touch-icon.png' }
    ];
    
    faviconSizes.forEach(favicon => {
        let link = document.querySelector(`link[rel="${favicon.rel}"]`);
        if (!link) {
            link = document.createElement('link');
            document.getElementsByTagName('head')[0].appendChild(link);
        }
        link.rel = favicon.rel;
        link.type = favicon.type;
        if (favicon.sizes) link.sizes = favicon.sizes;
        link.href = favicon.href;
    });
};

// Ejecutar cuando el DOM esté listo
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', setFavicon);
} else {
    setFavicon();
}

import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => title,
    resolve: (name) => resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob('./Pages/**/*.vue')),
    setup({ el, App, props, plugin }) {

        return createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
        delay: 250,
        includeCSS: true,
        showSpinner: true,
    },
});
