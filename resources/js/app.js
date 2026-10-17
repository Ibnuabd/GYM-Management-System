import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

const pinia = createPinia();

// Client route map for named routes
export function route(name) {
    const routes = {
        'dashboard': '/dashboard',
        'login': '/login',
        'logout': '/logout',
        'kiosk': '/kiosk',
        'pos': '/pos',
        'pt.dashboard': '/pt',
        'reports': '/reports',
        'users': '/users',
    };
    return routes[name] || `/${name}`;
}

createInertiaApp({
    title: title => title ? `${title} - FitCore GMS` : 'FitCore GMS',
    resolve: name => {
        const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });
        return pages[`./Pages/${name}.vue`];
    },
    setup({ el, App, props, plugin }) {
        const app = createApp({ render: () => h(App, props) });
        app.config.globalProperties.route = route;
        app.use(plugin);
        app.use(pinia);
        app.mount(el);
    },
    progress: {
        color: '#6c5ffc',
    },
});
