<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import Heading from '@/components/Heading.vue';
import { useLang } from '@/composables/useLang';
import { edit } from '@/routes/appearance';

const { t } = useLang();

setLayoutProps({
    layout: {
        breadcrumbs: [
            {
                title: t('settings.appearance.title', 'Appearance settings'),
                href: edit(),
            },
        ],
    },
});
</script>

<template>
    <Head :title="t('settings.appearance.title', 'Appearance settings')" />

    <h1 class="sr-only">{{ t('settings.appearance.title', 'Appearance settings') }}</h1>

    <div class="space-y-6">
        <Heading
            variant="small"
            :title="t('settings.appearance.title', 'Appearance settings')"
            :description="t('settings.appearance.description', 'Update the appearance settings for your account')"
        />
        <AppearanceTabs />
    </div>
</template>
