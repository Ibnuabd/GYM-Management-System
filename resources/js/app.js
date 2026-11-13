import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';
import { route } from './route';

const pinia = createPinia();

createInertiaApp({
    title: title => title ? `${title} - FitCore GMS` : 'FitCore GMS',
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        // route() bisa dipakai langsung di <template> semua halaman
        app.config.globalProperties.route = route;
        app.use(plugin);
        app.use(pinia);
        app.mount(el);
    },
    progress: {
        color: '#6c5ffc',
    },
});
