import { useLang } from "@/composables/useLang";

const { locale } = useLang();
export function withLocale(path: string): string {
    const prefix = `/${locale.value}`;

    if (path === prefix || path.startsWith(`${prefix}/`)) {
        return path;
    }

    return `${prefix}${path}`;
}

export const slugifyPreview = (value: string): string => {
    return value
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
};
