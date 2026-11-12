import { createApp, h } from 'vue';
import { createInertiaApp } from '@inertiajs/vue3';
import { createPinia } from 'pinia';

const pinia = createPinia();

// Client route map for named routes
export function route(name, params = null) {
    const routes = {
        'dashboard': '/dashboard',
        'login': '/login',
        'logout': '/logout',
        'kiosk': '/kiosk',
        'kiosk.scan': '/kiosk/scan',
        'kiosk.recent': '/kiosk/recent',
        'pos': '/pos',
        'pos.checkout': '/pos/checkout',
        'pos.search': '/pos/search',
        'pos.void': '/pos/transactions/{id}/void',
        'pos.destroy': '/pos/transactions/{id}',
        'products.index': '/products',
        'products.store': '/products',
        'products.update': '/products/{id}',
        'products.destroy': '/products/{id}',
        'pt.dashboard': '/pt',
        'pt.sessions.store': '/pt/sessions',
        'pt.members.history': '/pt/members/{id}/history',
        'reports': '/reports',
        'users': '/users',
        'members.index': '/members',
        'members.create': '/members/create',
        'members.store': '/members',
        'members.show': '/members/{id}',
        'members.edit': '/members/{id}/edit',
        'members.update': '/members/{id}',
        'members.destroy': '/members/{id}',
        'members.quick-search': '/members/quick-search',
        'members.check-rfid': '/members/check-rfid',
        'members.portal-password.update': '/members/{id}/portal-password',
        'packages.index': '/packages',
        'packages.store': '/packages',
        'packages.update': '/packages/{id}',
        'packages.destroy': '/packages/{id}',
        'subscriptions.store': '/members/{id}/subscriptions',
        'subscriptions.cancel': '/subscriptions/{id}/cancel',
        'subscriptions.activate': '/subscriptions/{id}/activate',
    };

    let url = routes[name] || `/${name}`;

    if (params !== null && params !== undefined) {
        if (typeof params === 'object') {
            for (const [key, val] of Object.entries(params)) {
                if (url.includes(`{${key}}`)) {
                    url = url.replace(`{${key}}`, val);
                }
            }
        } else {
            url = url.replace(/\{[a-zA-Z0-9_]+\}/, params);
        }
    }

    return url;
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
