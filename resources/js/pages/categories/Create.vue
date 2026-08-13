<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Info } from '@lucide/vue';
import { computed } from 'vue';

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
import Switch from '@/components/ui/switch/Switch.vue';


const ROOT_VALUE = '__root__';

interface ParentCategory {
    id: number | string;
    name: string;
}

interface Props {
    parentCategories: ParentCategory[];
}

const props = defineProps<Props>();

const { t, locale } = useLang();

/*
|--------------------------------------------------------------------------
| Page
|--------------------------------------------------------------------------
*/

const pageTitle = computed(() =>
    t('categories.create_title', 'Create Category'),
);

const pageDescription = computed(() =>
    t(
        'categories.create_subtitle',
        'Create a new category and configure its details.',
    ),
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
    is_active: true,
    parent_id: null as string | null,
});

/*
|--------------------------------------------------------------------------
| Parent Category
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
                    :submit-text="t('app.create', 'Create')"
                    :processing-text="t('app.creating', 'Creating...')"
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
                                    v-for="category in props.parentCategories"
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
