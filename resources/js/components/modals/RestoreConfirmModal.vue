<script setup lang="ts">
import { RotateCcw } from '@lucide/vue';

import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { useLang } from '@/composables/useLang';

const { t } = useLang();

defineProps<{
    show: boolean;
}>();

const emit = defineEmits(['confirm', 'cancel']);
</script>

<template>
    <Dialog :open="show" @update:open="(val) => !val && emit('cancel')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle class="flex items-center gap-2">
                    <RotateCcw class="h-5 w-5 text-green-600 dark:text-green-400" />
                    {{ t('app.restore', 'Restore') }}
                </DialogTitle>

                <DialogDescription class="mt-1 text-sm">
                    {{ t('app.restore_warning', 'Are you sure you want to restore this item? It will be moved back to the active list.') }}
                </DialogDescription>
            </DialogHeader>

            <!-- Info banner -->
            <div class="flex items-center gap-2 rounded-lg border border-green-300/50 bg-green-50/50 px-4 py-3 dark:border-green-700/30 dark:bg-green-900/10">
                <RotateCcw class="h-4 w-4 shrink-0 text-green-600 dark:text-green-400" />
                <span class="text-xs text-green-700 dark:text-green-400">
                    {{ t('app.restore_note', 'The item will be restored and visible in the active list again.') }}
                </span>
            </div>

            <DialogFooter class="flex gap-2 sm:justify-end">
                <Button
                    variant="outline"
                    class="border-green-500 text-green-600 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20"
                    @click="emit('confirm')"
                >
                    {{ t('app.restore', 'Restore') }}
                </Button>

                <Button variant="secondary" @click="emit('cancel')">
                    {{ t('app.no', 'No') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
