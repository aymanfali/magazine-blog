<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Edit, Fullscreen, Info } from '@lucide/vue';
import { computed } from 'vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import Label from '@/components/ui/label/Label.vue';
import Button from '@/components/ui/button/Button.vue';

import { useLang } from '@/composables/useLang';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

interface Category {
    id: number | string;
    name: string;
    slug: string | null;
    description: string | null;
    image: string | null;
    image_url?: string | null;
    is_active: boolean;
    parent_id: number | string | null;
    parent?: {
        id: number | string;
        name: string;
    } | null;
}

interface Props {
    open: boolean;
    category?: Category | null;
}

/*
|--------------------------------------------------------------------------
| Props / Emits
|--------------------------------------------------------------------------
*/

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t, locale } = useLang();

/*
|--------------------------------------------------------------------------
| Computed
|--------------------------------------------------------------------------
*/

const category = computed(() => props.category);

const existingImage = computed(() => {
    return category.value?.image_url ?? category.value?.image ?? null;
});

const parentName = computed(() => {
    return (
        category.value?.parent?.name ??
        t('app.main_category', 'Main Category')
    );
});

/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

const closeModal = () => {
    emit('update:open', false);
};
</script>

<template>
    <Dialog
        :open="open"
        @update:open="emit('update:open', $event)"
    >
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <!-- Header -->
            <DialogHeader>
                <DialogTitle>
                    {{ category?.name }}
                </DialogTitle>

                <DialogDescription>
                    {{
                        t(
                            'categories.show_subtitle',
                            'View category details.',
                        )
                    }}
                </DialogDescription>
            </DialogHeader>

            <div
                v-if="category"
                class="space-y-6"
            >
                <!-- Image -->
                <div
                    v-if="existingImage"
                    class="overflow-hidden rounded-lg border"
                >
                    <img
                        :src="existingImage"
                        :alt="category.name"
                        class="h-56 w-full object-cover"
                    />
                </div>

                <!-- Name -->
                <div class="space-y-1">
                    <Label>
                        {{ t('categories.name', 'Name') }}
                    </Label>

                    <div
                        class="rounded-md border bg-muted/50 px-3 py-2 text-sm"
                    >
                        {{ category.name }}
                    </div>
                </div>

                <!-- Slug -->
                <div class="space-y-1">
                    <Label>
                        {{ t('categories.slug', 'Slug') }}
                    </Label>

                    <div
                        class="rounded-md border bg-muted/50 px-3 py-2 font-mono text-sm"
                    >
                        {{ category.slug || '—' }}
                    </div>
                </div>

                <!-- Description -->
                <div class="space-y-1">
                    <Label>
                        {{ t('categories.description', 'Description') }}
                    </Label>

                    <div
                        class="min-h-20 rounded-md border bg-muted/50 px-3 py-2 text-sm whitespace-pre-wrap"
                    >
                        {{ category.description || '—' }}
                    </div>
                </div>

                <!-- Parent -->
                <div class="space-y-1">
                    <Label>
                        {{
                            t(
                                'categories.parent_category',
                                'Parent Category',
                            )
                        }}
                    </Label>

                    <div
                        class="rounded-md border bg-muted/50 px-3 py-2 text-sm"
                    >
                        {{ parentName }}
                    </div>
                </div>

                <!-- Status -->
                <div
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <div>
                        <Label>
                            {{ t('app.status', 'Status') }}
                        </Label>

                        <p
                            class="mt-1 flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Info class="h-3 w-3" />

                            {{
                                category.is_active
                                    ? t('app.active', 'Active')
                                    : t('app.inactive', 'Inactive')
                            }}
                        </p>
                    </div>

                    <span
                        class="rounded-full px-2.5 py-1 text-xs font-medium"
                        :class="
                            category.is_active
                                ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{
                            category.is_active
                                ? t('app.active', 'Active')
                                : t('app.inactive', 'Inactive')
                        }}
                    </span>
                </div>

                <!-- Actions -->
                <div class="flex justify-end gap-2 border-t pt-4">
                    <Button
                        type="button"
                        variant="outline"
                        @click="closeModal"
                    >
                        {{ t('app.close', 'Close') }}
                    </Button>

                    <Link
                        :href="`/${locale}/dashboard/categories/edit/${category.id}`"
                    >
                        <Button type="button">
                            <Edit class="mr-2 h-4 w-4" />

                            {{ t('app.edit', 'Edit') }}
                        </Button>
                    </Link>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
