import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

export function useLang() {
    const page = usePage();

    const translations = computed(() => (page.props as any).translations ?? {});

    const locale = computed(() => (page.props as any).locale ?? 'en');

    function t(key: string, fallback?: string) {
        if (!key) {
            return fallback ?? '';
        }

        const parts = key.split('.');
        let obj: any = translations.value;

        for (const part of parts) {
            if (obj == null) {
                return fallback ?? key;
            }

            obj = obj[part];
        }

        return obj ?? fallback ?? key;
    }

    return { t, locale, translations };
}
