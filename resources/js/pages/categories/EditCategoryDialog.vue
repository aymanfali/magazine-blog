<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { Fullscreen, Info } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

import AppForm from '@/components/forms/AppForm.vue';
import FormInput from '@/components/inputs/FormInput.vue';
import FormTextarea from '@/components/inputs/FormTextarea.vue';
import ImageInput from '@/components/inputs/ImageInput.vue';

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

import Switch from '@/components/ui/switch/Switch.vue';

import { useLang } from '@/composables/useLang';
import Button from '@/components/ui/button/Button.vue';

/*
|--------------------------------------------------------------------------
| Constants
|--------------------------------------------------------------------------
*/

const ROOT_VALUE = '__root__';

/*
|--------------------------------------------------------------------------
| Types
|--------------------------------------------------------------------------
*/

interface ParentCategory {
    id: number | string;
    name: string;
}

interface Category {
    id: number | string;
    name: string;
    slug: string | null;
    description: string | null;
    image: string | null;
    image_url?: string | null;
    is_active: boolean;
    parent_id: number | string | null;
}

interface Props {
    open: boolean;
    parentCategories: ParentCategory[];
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
| Mode
|--------------------------------------------------------------------------
*/

const isEditMode = computed(() => Boolean(props.category));

const dialogTitle = computed(() =>
    isEditMode.value
        ? t('categories.edit_title', 'Edit Category')
        : t('categories.create_title', 'Create Category'),
);

const dialogDescription = computed(() =>
    isEditMode.value
        ? t('categories.edit_subtitle', 'Update the category details.')
        : t(
              'categories.create_subtitle',
              'Create a new category and configure its details.',
          ),
);

const submitText = computed(() =>
    isEditMode.value ? t('app.update', 'Update') : t('app.create', 'Create'),
);

const processingText = computed(() =>
    isEditMode.value
        ? t('app.updating', 'Updating...')
        : t('app.creating', 'Creating...'),
);

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    name: '',
    slug: '',
    description: '',
    image: null as File | null,
    remove_image: false,
    is_active: true,
    parent_id: null as string | null,
});

/*
|--------------------------------------------------------------------------
| Existing Image
|--------------------------------------------------------------------------
*/

const existingImage = ref<string | null>(null);

/*
|--------------------------------------------------------------------------
| Modal State
|--------------------------------------------------------------------------
*/

const showModal = ref(props.open);

/*
|--------------------------------------------------------------------------
| Parent Categories
|--------------------------------------------------------------------------
|
| Never allow the category being edited to become its own parent.
|--------------------------------------------------------------------------
*/

const availableParentCategories = computed(() => {
    const currentId = props.category?.id;

    if (currentId === null || currentId === undefined) {
        return props.parentCategories;
    }

    return props.parentCategories.filter(
        (category) => String(category.id) !== String(currentId),
    );
});

/*
|--------------------------------------------------------------------------
| Select Value
|--------------------------------------------------------------------------
|
| The UI needs a non-empty value for the "Main Category" option.
| Laravel, however, needs parent_id = null.
|--------------------------------------------------------------------------
*/

const selectedParentValue = computed(() => {
    return form.parent_id ?? ROOT_VALUE;
});

const handleParentChange = (value: string) => {
    form.parent_id = value === ROOT_VALUE ? null : value;
};

/*
|--------------------------------------------------------------------------
| Populate Form
|--------------------------------------------------------------------------
*/

const populateForm = () => {
    form.clearErrors();

    if (!props.category) {
        resetForm();

        return;
    }

    form.name = props.category.name ?? '';
    form.slug = props.category.slug ?? '';
    form.description = props.category.description ?? '';

    form.image = null;
    form.remove_image = false;

    form.is_active = Boolean(props.category.is_active);

    form.parent_id =
        props.category.parent_id !== null &&
        props.category.parent_id !== undefined
            ? String(props.category.parent_id)
            : null;

    existingImage.value =
        props.category.image_url ?? props.category.image ?? null;
};

/*
|--------------------------------------------------------------------------
| Remove Existing Image
|--------------------------------------------------------------------------
*/

const handleRemoveExistingImage = () => {
    form.remove_image = true;
    form.image = null;

    existingImage.value = null;
};

/*
|--------------------------------------------------------------------------
| Watch Open
|--------------------------------------------------------------------------
*/

watch(
    () => props.open,
    (value) => {
        showModal.value = value;

        if (value) {
            populateForm();
        }
    },
);

/*
|--------------------------------------------------------------------------
| Watch Category
|--------------------------------------------------------------------------
|
| Handles changing from one category to another while the dialog remains
| mounted.
|--------------------------------------------------------------------------
*/

watch(
    () => props.category,
    () => {
        if (showModal.value) {
            populateForm();
        }
    },
);

/*
|--------------------------------------------------------------------------
| Watch Modal
|--------------------------------------------------------------------------
*/

watch(showModal, (value) => {
    emit('update:open', value);

    if (!value && !form.processing) {
        resetForm();
    }
});

/*
|--------------------------------------------------------------------------
| Slug
|--------------------------------------------------------------------------
*/

const slugifyPreview = (value: string): string => {
    return value
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9\s-]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-')
        .replace(/^-|-$/g, '');
};

const slugPreview = computed(() => {
    const value = form.slug.trim();

    return value ? slugifyPreview(value) : slugifyPreview(form.name);
});

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const submit = () => {
    if (form.parent_id === ROOT_VALUE) {
        form.parent_id = null;
    }

    if (isEditMode.value && props.category) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
            remove_image: data.remove_image ? 1 : 0,
        })).post(`/${locale.value}/dashboard/categories/${props.category.id}`, {
            forceFormData: true,
            preserveScroll: true,

            onSuccess: () => {
                closeModal();
            },
        });

        return;
    }

    form.post(`/${locale.value}/dashboard/categories`, {
        forceFormData: true,
        preserveScroll: true,

        onSuccess: () => {
            closeModal();
        },
    });
};

/*
|--------------------------------------------------------------------------
| Close
|--------------------------------------------------------------------------
*/

const closeModal = () => {
    if (form.processing) {
        return;
    }

    showModal.value = false;
};

/*
|--------------------------------------------------------------------------
| Reset
|--------------------------------------------------------------------------
*/

const resetForm = () => {
    form.reset();

    form.name = '';
    form.slug = '';
    form.description = '';
    form.image = null;
    form.remove_image = false;
    form.is_active = true;
    form.parent_id = null;

    existingImage.value = null;

    form.clearErrors();
};
</script>

<template>
    <Dialog v-model:open="showModal">
        <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
            <!-- Header -->
            <DialogHeader>
                <DialogTitle>
                    {{ dialogTitle }}
                </DialogTitle>

                <DialogDescription>
                    {{ dialogDescription }}
                </DialogDescription>
                <Link
                    :href="`/${locale}/dashboard/categories/edit/${props.category?.id ?? ''}`"
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
                :submit-text="submitText"
                :processing-text="processingText"
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

                        <div
                            class="mt-1 flex items-center gap-2 text-xs text-muted-foreground"
                        >
                            <Info class="h-3 w-3" />

                            {{
                                t(
                                    'categories.slug_preview_help',
                                    'The final slug is generated and validated by the server.',
                                )
                            }}
                        </div>
                    </div>

                    <p
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <Info class="h-3 w-3" />

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
                    <Label for="category-parent">
                        {{ t('categories.parent_category', 'Parent Category') }}
                    </Label>

                    <Select
                        :model-value="selectedParentValue"
                        :disabled="form.processing"
                        @update:model-value="handleParentChange"
                    >
                        <SelectTrigger
                            id="category-parent"
                            :aria-invalid="!!form.errors.parent_id"
                        >
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
                            <!-- Root category -->
                            <SelectItem :value="ROOT_VALUE">
                                {{ t('app.main_category', 'Main Category') }}
                            </SelectItem>

                            <!-- Parent categories -->
                            <SelectItem
                                v-for="category in availableParentCategories"
                                :key="String(category.id)"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <p
                        class="flex items-center gap-2 text-xs text-muted-foreground"
                    >
                        <Info class="h-3 w-3" />

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
                    :existing-image="existingImage"
                    :allowed-types="[
                        'image/png',
                        'image/jpeg',
                        'image/jpg',
                        'image/webp',
                    ]"
                    :max-size-m-b="5"
                    @remove-existing="handleRemoveExistingImage"
                />

                <!-- Active -->
                <div
                    class="flex items-center justify-between rounded-lg border p-4"
                >
                    <div class="space-y-1">
                        <Label>
                            {{ t('app.active', 'Active') }}
                        </Label>

                        <p
                            class="flex items-center gap-2 text-sm text-muted-foreground"
                        >
                            <Info class="h-3 w-3" />

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
