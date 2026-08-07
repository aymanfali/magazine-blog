<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { useLang } from '@/composables/useLang';
import { Languages } from '@lucide/vue';

const page = usePage();
const { t } = useLang();

const locale = computed(() => (page.props as any).locale ?? 'en');

const currentUrl = computed(() => {
    return new URL(
        page.url,
        typeof window !== 'undefined'
            ? window.location.origin
            : 'http://localhost',
    );
});

const locales = computed(() => [
    { code: 'en', label: t('language.english', 'English') },
    { code: 'ar', label: t('language.arabic', 'العربية') },
]);

const currentLangLabel = computed(() => {
    const current = locales.value.find((item) => item.code === locale.value);
    return current?.label ?? locale.value.toUpperCase();
});

const getLocalizedUrl = (targetLocale: string) => {
    const pathname = currentUrl.value.pathname;
    const search = currentUrl.value.search;

    if (/^\/(en|ar)(\/|$)/.test(pathname)) {
        return (
            pathname.replace(/^\/(en|ar)(\/|$)/, `/${targetLocale}$2`) + search
        );
    }

    return `/${targetLocale}${pathname}${search}`;
};

const switchLocale = (targetLocale: string) => {
    if (targetLocale === locale.value) {
        return;
    }

    const localizedUrl = getLocalizedUrl(targetLocale);
    window.location.href = localizedUrl;
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger :as-child="true">
            <Button
                variant="outline"
                :aria-label="t('language.label', 'Language')"
            >
                <Languages />
                <span class="text-xs font-semibold uppercase">
                    {{ currentLangLabel }}
                </span>
            </Button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-40">
            <DropdownMenuItem
                v-for="option in locales"
                :key="option.code"
                :as-child="true"
            >
                <button
                    type="button"
                    class="flex w-full items-center justify-between px-3 py-2 text-sm font-medium transition-colors hover:bg-accent"
                    :disabled="option.code === locale"
                    @click="switchLocale(option.code)"
                >
                    <span>{{ option.label }}</span>
                    <span
                        v-if="option.code === locale"
                        class="text-xs opacity-70"
                    >
                        ✓
                    </span>
                </button>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
