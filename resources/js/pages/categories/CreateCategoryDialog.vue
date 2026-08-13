<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Fullscreen, Info } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import AppForm from '@/components/forms/AppForm.vue';
import FormInput from '@/components/inputs/FormInput.vue';
import FormTextarea from '@/components/inputs/FormTextarea.vue';
import ImageInput from '@/components/inputs/ImageInput.vue';
import Button from '@/components/ui/button/Button.vue';

import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLang } from '@/composables/useLang';


import { slugifyPreview } from '@/utils';
import Switch from '@/components/ui/switch/Switch.vue';

interface ParentCategory {
    id: number | string;
    name: string;
}

interface Props {
    open: boolean;
    parentCategories: ParentCategory[];
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const { t, locale } = useLang();

/**
 * --------------------------------------------------------------------------
 * Form
 * --------------------------------------------------------------------------
 *
 * `slug` is optional.
 *
 * The frontend only generates a preview.
 * The backend CategoryService remains responsible for generating,
 * normalizing, and ensuring the uniqueness of the actual slug.
 */

const form = useForm({
    name: '',
    slug: '',
    description: '',
    image: null as File | null,
    is_active: true,
    parent_id: null as string | null,
});

/**
 * --------------------------------------------------------------------------
 * Modal
 * --------------------------------------------------------------------------
 */

const showModal = ref(props.open);

watch(
    () => props.open,
    (value) => {
        showModal.value = value;
    },
);

watch(showModal, (value) => {
    emit('update:open', value);

    if (!value && !form.processing) {
        resetForm();
    }
});

/**
 * --------------------------------------------------------------------------
 * Slug Preview
 * --------------------------------------------------------------------------
 *
 * This is NOT the actual slug generation logic.
 * It only gives the user an indication of how the URL will look.
 */

const slugPreview = computed(() => {
    const customSlug = form.slug.trim();

    if (customSlug) {
        return slugifyPreview(customSlug);
    }

    return slugifyPreview(form.name);
});

/**
 * --------------------------------------------------------------------------
 * Submit
 * --------------------------------------------------------------------------
 */

const submit = () => {
    form.post(`/${locale.value}/dashboard/categories`, {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            closeModal();
        },
    });
};

/**
 * --------------------------------------------------------------------------
 * Modal Actions
 * --------------------------------------------------------------------------
 */

const closeModal = () => {
    if (form.processing) {
        return;
    }

    showModal.value = false;
};

const resetForm = () => {
    form.reset();

    form.is_active = true;
    form.parent_id = null;

    form.clearErrors();
};
</script>

<template>
    <Dialog v-model:open="showModal">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>
                    {{ t('categories.create_title', 'Create Category') }}
                </DialogTitle>

                <DialogDescription>
                    {{
                        t(
                            'categories.create_subtitle',
                            'Create a new category and configure its details.',
                        )
                    }}
                </DialogDescription>
                <Link
                    :href="`/${locale}/dashboard/categories/create`"
                    rel="noopener noreferrer" class="flex justify-center"
                >
                    <Button variant="link" type="button">
                        <Fullscreen />
                        {{ t('app.view_full_page') }}
                    </Button>
                </Link>
            </DialogHeader>

            <AppForm
                :processing="form.processing"
                :submit-text="t('app.create', 'Create')"
                :processing-text="t('app.creating', 'Creating...')"
                :cancel-text="t('app.cancel', 'Cancel')"
                @submit="submit"
                @cancel="closeModal"
            >
                <!-- Name -->
                <FormInput
                    id="category-name"
                    v-model="form.name"
                    :label="t('categories.name', 'Name')"
                    type="text"
                    autocomplete="off"
                    :error="form.errors.name"
                    :disabled="form.processing"
                    required
                />

                <!-- Slug -->
                <div class="space-y-2">
                    <FormInput
                        id="category-slug"
                        v-model="form.slug"
                        :label="t('categories.slug', 'Slug')"
                        type="text"
                        autocomplete="off"
                        :error="form.errors.slug"
                        :disabled="form.processing"
                    />

                    <!-- URL Preview -->
                    <div
                        v-if="slugPreview"
                        class="rounded-md border bg-muted/50 px-3 py-2"
                    >
                        <div class="text-xs text-muted-foreground">
                            {{ t('categories.url_preview', 'URL Preview') }}
                        </div>

                        <div class="mt-1 font-mono text-sm break-all">
                            /categories/{{ slugPreview }}
                        </div>

                        <div class="flex gap-2 items-center mt-1 text-xs text-muted-foreground">
                            <Info class="h-3 w-3"/>
                            {{
                                t(
                                    'categories.slug_preview_help',
                                    'The final slug is generated and validated by the server.',
                                )
                            }}
                        </div>
                    </div>

                    <p class="flex gap-2 items-center text-xs text-muted-foreground">
                        <Info class="h-3 w-3"/>
                        {{
                            t(
                                'categories.slug_note',
                                'Leave empty to generate a slug automatically from the category name.',
                            )
                        }}
                    </p>
                </div>

                <!-- Description -->
                <FormTextarea
                    id="category-description"
                    v-model="form.description"
                    :label="t('categories.description', 'Description')"
                    :error="form.errors.description"
                    :disabled="form.processing"
                />

                <!-- Parent Category -->
                <div class="space-y-2">
                    <Label>
                        {{ t('categories.parent_category', 'Parent Category') }}
                    </Label>

                    <Select
                        v-model="form.parent_id"
                        :disabled="form.processing"
                    >
                        <SelectTrigger :aria-invalid="!!form.errors.parent_id">
                            <SelectValue
                                :placeholder="
                                    t(
                                        'categories.select_parent',
                                        'Select parent category',
                                    )
                                "
                            />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem key="main-category" value="main-category">{{ t('app.main_category') }}</SelectItem>
                            <SelectItem
                                v-for="category in props.parentCategories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <p class="flex gap-2 items-center text-xs text-muted-foreground">
                        <Info class="h-3 w-3"/>
                        {{
                            t(
                                'categories.parent_category_note',
                                'Leave empty to create a root category.',
                            )
                        }}
                    </p>

                    <p
                        v-if="form.errors.parent_id"
                        class="text-sm text-destructive"
                    >
                        {{ form.errors.parent_id }}
                    </p>
                </div>

                <!-- Image -->
                <ImageInput
                    v-model="form.image"
                    :label="t('categories.image', 'Category Image')"
                    :error="form.errors.image"
                    :disabled="form.processing"
                    :allowed-types="[
                        'image/png',
                        'image/jpeg',
                        'image/jpg',
                        'image/webp',
                    ]"
                    :max-size-m-b="5"
                />

                <!-- Active -->
                <div
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <div class="space-y-1">
                        <Label>
                            {{ t('app.active', 'Active') }}
                        </Label>

                        <p class="flex gap-2 items-center text-sm text-muted-foreground">
                            <Info class="h-3 w-3"/>
                            {{
                                t(
                                    'categories.active_note',
                                    'Active categories are available for use.',
                                )
                            }}
                        </p>
                    </div>

                    <Switch
                        v-model="form.is_active"
                        :disabled="form.processing"
                    />
                </div>

                <p
                    v-if="form.errors.is_active"
                    class="text-sm text-destructive"
                >
                    {{ form.errors.is_active }}
                </p>
            </AppForm>
        </DialogContent>
    </Dialog>
</template>
