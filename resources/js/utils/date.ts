import { useLang } from '@/composables/useLang';

export function useDateFormatter() {
    const { locale } = useLang();

    const format = (
        value: string | Date,
        options?: Intl.DateTimeFormatOptions,
    ) => {
        if (!value) {
            return '-';
        }

        return new Intl.DateTimeFormat(locale.value, {
            dateStyle: 'medium',
            timeStyle: 'short',
            ...options,
        }).format(new Date(value));
    };

    return { format };
}
