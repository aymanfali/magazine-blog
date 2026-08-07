<script setup lang="ts">
import { Monitor, Moon, Sun } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { useLang } from '@/composables/useLang';

const { appearance, updateAppearance } = useAppearance();
const { t } = useLang();
const options = [
    { value: 'light', Icon: Sun, label: t('app.light') },
    { value: 'dark', Icon: Moon, label: t('app.dark') },
    { value: 'system', Icon: Monitor, label: t('app.system') },
] as const;

const currentIndex = computed(() =>
    options.findIndex((opt) => opt.value === appearance.value),
);

const current = computed(() => options[currentIndex.value] ?? options[0]);

const next = () => {
    const nextIndex = (currentIndex.value + 1) % options.length;
    updateAppearance(options[nextIndex].value);
};
</script>

<template>
    <Button
        variant="outline"
        size="sm"
        @click="next"
        class="flex items-center gap-2"
    >
        <component :is="current.Icon" class="h-4 w-4" />
    </Button>
</template>
