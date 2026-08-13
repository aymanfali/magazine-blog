import { useLang } from "@/composables/useLang";

const { locale } = useLang();
export function withLocale(path: string): string {
    const prefix = `/${locale.value}`;

    if (path === prefix || path.startsWith(`${prefix}/`)) {
        return path;
    }

    return `${prefix}${path}`;
}
