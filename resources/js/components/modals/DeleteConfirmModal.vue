<script setup lang="ts">
import { AlertTriangle, Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';

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

const props = defineProps<{
    show: boolean;
    /** If true, the item is already trashed — skip the checkbox and always force delete */
    isTrashed?: boolean;
}>();

const emit = defineEmits<{
    (e: 'confirm', payload: { force: boolean }): void;
    (e: 'cancel'): void;
}>();

/** Permanently-delete checkbox (only shown for non-trashed items) */
const permanentlyDelete = ref(false);

// Reset checkbox every time modal opens
watch(
    () => props.show,
    (val) => {
        if (val) {
            permanentlyDelete.value = false;
        }
    },
);

function confirm() {
    emit('confirm', {
        force: props.isTrashed ? true : permanentlyDelete.value,
    });
}
</script>

<template>
    <Dialog :open="show" @update:open="(val) => !val && emit('cancel')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <!-- Title changes based on context -->
                <DialogTitle class="flex items-center gap-2">
                    <Trash2
                        :class="
                            isTrashed || permanentlyDelete
                                ? 'text-destructive'
                                : 'text-muted-foreground'
                        "
                        class="h-5 w-5"
                    />
                    {{
                        isTrashed
                            ? t(
                                  'app.confirm_force_delete',
                                  'Confirm Permanent Delete',
                              )
                            : t('app.confirm_delete', 'Confirm Delete')
                    }}
                </DialogTitle>

                <!-- Description -->
                <DialogDescription class="mt-1 space-y-1 text-sm">
                    <span v-if="isTrashed" class="font-medium text-destructive">
                        {{
                            t(
                                'app.force_delete_warning',
                                'This item will be permanently deleted. This action cannot be undone!',
                            )
                        }}
                    </span>
                    <span
                        v-else-if="permanentlyDelete"
                        class="font-medium text-destructive"
                    >
                        {{
                            t(
                                'app.force_delete_warning',
                                'This item will be permanently deleted. This action cannot be undone!',
                            )
                        }}
                    </span>
                    <span v-else>
                        {{
                            t(
                                'app.delete_warning',
                                'This action cannot be undone!',
                            )
                        }}
                    </span>
                </DialogDescription>
            </DialogHeader>

            <!-- Permanent delete option (only shown for non-trashed items) -->
            <div
                v-if="!isTrashed"
                class="flex items-start gap-3 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3"
            >
                <input
                    id="permanently-delete-checkbox"
                    v-model="permanentlyDelete"
                    type="checkbox"
                    class="mt-0.5 h-4 w-4 cursor-pointer accent-destructive"
                />
                <div class="flex flex-col gap-0.5">
                    <label
                        for="permanently-delete-checkbox"
                        class="cursor-pointer text-sm leading-none font-medium"
                    >
                        {{ t('app.delete_permanently', 'Delete Permanently') }}
                    </label>
                    <span class="text-xs text-muted-foreground">
                        {{
                            t(
                                'app.force_delete_warning',
                                'This item will be permanently deleted and cannot be recovered.',
                            )
                        }}
                    </span>
                </div>
            </div>

            <!-- Permanent warning banner when already trashed -->
            <div
                v-if="isTrashed"
                class="flex items-center gap-2 rounded-lg border border-destructive/30 bg-destructive/5 px-4 py-3"
            >
                <AlertTriangle class="h-4 w-4 shrink-0 text-destructive" />
                <span class="text-xs text-destructive">
                    {{
                        t(
                            'app.trashed_permanent_note',
                            'This item is already in the trash. Deleting it here will remove it forever.',
                        )
                    }}
                </span>
            </div>

            <DialogFooter class="flex gap-2 sm:justify-end">
                <Button
                    :variant="
                        isTrashed || permanentlyDelete
                            ? 'destructive'
                            : 'destructive'
                    "
                    @click="confirm"
                >
                    {{ t('app.yes', 'Yes') }}
                </Button>

                <Button variant="secondary" @click="emit('cancel')">
                    {{ t('app.no', 'No') }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
