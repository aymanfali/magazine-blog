<script setup lang="ts">
import { useLang } from '@/composables/useLang';

defineProps({
    show: { type: Boolean, required: true },
    title: { type: String, default: '' },
});

const emits = defineEmits(['close']);

function close() {
    emits('close');
}

const { t } = useLang();
</script>

<template>
    <teleport to="body">
        <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center">
            <div class="absolute inset-0 bg-black/40" @click="close"></div>

            <div class="relative z-10 w-full max-w-lg rounded bg-white p-6 shadow-lg">
                <div class="flex items-start justify-between">
                    <h3 class="text-lg font-semibold">{{ title }}</h3>
                    <button class="text-gray-600" @click="close">✕</button>
                </div>

                <div class="mt-4">
                    <slot />
                </div>

                <div class="mt-6 flex justify-end">
                    <button class="btn btn-ghost" @click="close">{{t('app.close')}}</button>
                </div>
            </div>
        </div>
    </teleport>
</template>

<style scoped>
.btn { padding: 0.5rem 1rem; border-radius: .375rem; }
.btn-ghost { background: transparent; border: 1px solid #e5e7eb; }
</style>
