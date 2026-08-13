<script setup lang="ts">
import { Head, Link, setLayoutProps, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Info } from '@lucide/vue';
import { computed, ref } from 'vue';

import AppForm from '@/components/forms/AppForm.vue';
import FormInput from '@/components/inputs/FormInput.vue';
import FormTextarea from '@/components/inputs/FormTextarea.vue';
import ImageInput from '@/components/inputs/ImageInput.vue';
import Button from '@/components/ui/button/Button.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { useLang } from '@/composables/useLang';
import { dashboard } from '@/routes';
import { slugifyPreview, withLocale } from '@/utils';
import Switch from '@/components/ui/switch/Switch.vue';

const ROOT_VALUE = '__root__';

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
    parentCategories: ParentCategory[];
    category?: Category | null;
}

const props = defineProps<Props>();

const { t, locale } = useLang();


setLayoutProps({
    breadcrumbs: [
        {
            title: t('dashboard.title', 'Dashboard'),
            href: dashboard(),
        },
        {
            title: t('categories.title', 'Categories'),
            href: withLocale('/dashboard/categories'),
        },
        {
            title: props.category?.name,
            href: '',
        },
    ],
});

/*
|--------------------------------------------------------------------------
| Mode
|--------------------------------------------------------------------------
*/

const isEditMode = computed(() => Boolean(props.category));

const pageTitle = computed(() =>
    isEditMode.value
        ? t('categories.edit_title', 'Edit Category')
        : t('categories.create_title', 'Create Category'),
);

const pageDescription = computed(() =>
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

const form = useForm({
    name: props.category?.name ?? '',
    slug: props.category?.slug ?? '',
    description: props.category?.description ?? '',
    image: null as File | null,
    remove_image: false,
    is_active: props.category ? Boolean(props.category.is_active) : true,
    parent_id:
        props.category?.parent_id !== null &&
        props.category?.parent_id !== undefined
            ? String(props.category.parent_id)
            : (null as string | null),
});

const existingImage = ref<string | null>(
    props.category?.image_url ?? props.category?.image ?? null,
);

const availableParentCategories = computed(() => {
    const currentId = props.category?.id;

    if (currentId === null || currentId === undefined) {
        return props.parentCategories;
    }

    return props.parentCategories.filter(
        (category) => String(category.id) !== String(currentId),
    );
});

const selectedParentValue = computed(() => {
    return form.parent_id ?? ROOT_VALUE;
});

const handleParentChange = (value: string) => {
    form.parent_id = value === ROOT_VALUE ? null : value;
};

const slugPreview = computed(() => {
    const value = form.slug.trim();

    return value ? slugifyPreview(value) : slugifyPreview(form.name);
});

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
| Submit
|--------------------------------------------------------------------------
*/

const submit = () => {
    if (form.parent_id === ROOT_VALUE) {
        form.parent_id = null;
    }

    /*
    |--------------------------------------------------------------------------
    | Update
    |--------------------------------------------------------------------------
    */

    if (isEditMode.value && props.category) {
        form.transform((data) => ({
            ...data,
            _method: 'PUT',
            remove_image: data.remove_image ? 1 : 0,
        })).post(`/${locale.value}/dashboard/categories/${props.category.id}`, {
            forceFormData: true,
            preserveScroll: true,
        });

        return;
    }

    /*
    |--------------------------------------------------------------------------
    | Create
    |--------------------------------------------------------------------------
    */

    form.post(`/${locale.value}/dashboard/categories`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

/*
|--------------------------------------------------------------------------
| Cancel
|--------------------------------------------------------------------------
*/

const cancel = () => {
    if (form.processing) {
        return;
    }

    window.history.back();
};
</script>

<template>
    <Head :title="pageTitle" />

    <div class="mx-auto w-full max-w-4xl px-4 py-6 sm:px-6 lg:px-8">
        <!-- Header -->
        <div
            class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-3">
                    <Link
                        :href="`/${locale}/dashboard/categories`"
                        preserve-scroll
                    >
                        <Button type="button" variant="ghost" size="icon">
                            <ArrowLeft class="h-4 w-4 rtl:rotate-180" />
                            <span class="sr-only">
                                {{ t('app.back', 'Back') }}
                            </span>
                        </Button>
                    </Link>

                    <h1 class="text-2xl font-semibold tracking-tight">
                        {{ pageTitle }}
                    </h1>
                </div>

                <p class="text-sm text-muted-foreground">
                    {{ pageDescription }}
                </p>
            </div>
        </div>

        <!-- Form Card -->
        <div class="rounded-xl border bg-card shadow-sm">
            <div class="p-6">
                <AppForm
                    :processing="form.processing"
                    :submit-text="submitText"
                    :processing-text="processingText"
                    :cancel-text="t('app.cancel', 'Cancel')"
                    @submit="submit"
                    @cancel="cancel"
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

                        <!-- Slug Preview -->
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
                            {{
                                t(
                                    'categories.parent_category',
                                    'Parent Category',
                                )
                            }}
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
                                <!-- Main Category -->
                                <SelectItem :value="ROOT_VALUE">
                                    {{
                                        t('app.main_category', 'Main Category')
                                    }}
                                </SelectItem>

                                <!-- Parent Categories -->
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
            </div>
        </div>
    </div>
</template>
