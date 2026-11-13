/**
 * Helper route(): mengubah NAMA route Laravel menjadi URL.
 *
 * Contoh:
 *   route('members.show', 5)          -> '/members/5'
 *   route('pos.void', { id: 12 })     -> '/pos/transactions/12/void'
 *
 * PENTING untuk programmer pemula:
 * - Di <template>, route() langsung bisa dipakai.
 * - Di <script setup>, WAJIB di-import dulu:
 *       import { route } from '@/route';
 *   Tanpa import, browser akan error "route is not defined".
 * - Jika menambah route baru di routes/web.php yang dipakai di Vue,
 *   tambahkan juga namanya di daftar `routes` di bawah ini.
 */
export function route(name, params = null) {
    const routes = {
        'dashboard': '/dashboard',
        'login': '/login',
        'logout': '/logout',
        'kiosk': '/kiosk',
        'kiosk.scan': '/kiosk/scan',
        'kiosk.recent': '/kiosk/recent',
        'attendances.index': '/attendances',
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
