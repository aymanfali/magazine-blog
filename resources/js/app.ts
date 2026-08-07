import { createInertiaApp } from '@inertiajs/vue3';
import { createApp, h } from 'vue';
import { setUrlDefaults } from '@/wayfinder';
import { initializeTheme } from '@/composables/useAppearance';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    progress: {
        color: '#4B5563',
    },
    setup({ el, App, props, plugin }) {
        // Ensure route URL defaults (like `locale`) are available to generated route helpers.
        try {
            const locale = props?.initialPage?.props?.locale;

            if (locale !== undefined) {
                setUrlDefaults(() => ({ locale }));
            }
        } catch (e) {
            // ignore — best-effort only
        }

        const app = createApp({ render: () => h(App, props) });

        app.use(plugin).mount(el);
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();
