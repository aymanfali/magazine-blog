<script setup lang="ts">
import { computed, ref } from 'vue';

interface Props {
    src: string;
    alt: string;
    fallback?: string;
    loading?: 'lazy' | 'eager';
    width?: string | number;
    height?: string | number;
    srcset?: string;
    sizes?: string;
    class?: string;
}

const props = withDefaults(defineProps<Props>(), {
    fallback: 'assets/icons/fall-back-image.svg',
    loading: 'lazy',
});

const isLoaded = ref(false);
const hasError = ref(false);

const currentSrc = computed(() => {
    return hasError.value ? props.fallback : props.src;
});

function onLoad() {
    isLoaded.value = true;
}

function onError() {
    hasError.value = true;
}
</script>

<template>
    <div class="relative overflow-hidden" :class="props.class">
        <!-- Placeholder / Skeleton -->
        <div
            v-if="!isLoaded"
            class="absolute inset-0 animate-pulse bg-gray-200 dark:bg-gray-700 rounded-lg"
        />

        <!-- Image -->
        <img
            :src="currentSrc"
            :alt="alt"
            :loading="loading"
            :width="width"
            :height="height"
            :srcset="srcset"
            :sizes="sizes"
            @load="onLoad"
            @error="onError"
            class="transition-opacity duration-300"
            :class="{ 'opacity-0': !isLoaded, 'opacity-100': isLoaded }"
        />

        <!-- Optional slot (e.g. overlay) -->
        <slot />
    </div>
</template>
