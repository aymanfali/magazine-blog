<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { CheckCircle, CircleX, Edit2, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import AdminTable from '@/components/tables/AdminTable.vue';
import Button from '@/components/ui/button/Button.vue';

import { useLang } from '@/composables/useLang';
import { dashboard } from '@/routes';

import CreateCategoryDialog from './CreateCategoryDialog.vue';

interface ParentCategory {
    id: string;
    name: string;
}

interface Props {
    categories: any;
    parentCategories?: ParentCategory[];
}

const props = defineProps<Props>();

const { t, locale } = useLang();

/*
|--------------------------------------------------------------------------
| Locale
|--------------------------------------------------------------------------
*/

function withLocale(path: string): string {
    const prefix = `/${locale.value}`;

    if (path === prefix || path.startsWith(`${prefix}/`)) {
        return path;
    }

    return `${prefix}${path}`;
}

/*
|--------------------------------------------------------------------------
| Layout
|--------------------------------------------------------------------------
*/

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
    ],
});

/*
|--------------------------------------------------------------------------
| Create Category
|--------------------------------------------------------------------------
*/

const showCreateModal = ref(false);

/*
|--------------------------------------------------------------------------
| Table Columns
|--------------------------------------------------------------------------
*/

const parentCategoryOptions = computed(() =>
    Object.fromEntries(
        (props.parentCategories ?? []).map((category) => [
            String(category.id),
            {
                label: category.name,
            },
        ]),
    ),
);

const columns = computed(() => [
    {
        key: 'image_url',
        type: 'image',
        label: t('categories.image', 'Image'),
        sortable: false,
        filterable: false,
    },

    {
        key: 'name',
        label: t('categories.name', 'Name'),
        sortable: true,
        filterable: true,
        filterType: 'text',
        filterKey: 'name',
        searchable: true,
    },
    {
        key: 'parent_name',
        label: t('categories.parent_category', 'Parent'),
        sortable: true,
        filterable: true,
        filterType: 'select',
        filterKey: 'parent_id',
        searchable: true,
        options: parentCategoryOptions.value,
    },

    {
        key: 'is_active',
        label: t('app.active', 'Active'),
        type: 'badge',
        filterable: true,
        sortable: true,
        filterType: 'select',
        filterKey: 'is_active',

        options: {
            '1': {
                label: t('app.active', 'Active'),
                color: 'green',
                icon: CheckCircle,
            },

            '0': {
                label: t('app.inactive', 'Inactive'),
                color: 'red',
                icon: CircleX,
            },
        },
    },

    {
        key: 'created_at',
        label: t('app.created_at', 'Created At'),
        searchable: true,
        sortable: true,
        filterable: true,
        filterType: 'date-range',
    },

    {
        key: 'updated_at',
        label: t('app.updated_at', 'Updated At'),
        sortable: true,
        filterable: true,
        filterType: 'date-range',
    },

    {
        key: 'deleted_at',
        label: t('app.deleted_at', 'Deleted At'),
        sortable: true,
        filterable: true,
        filterType: 'date-range',
    },
]);

/*
|--------------------------------------------------------------------------
| Table Actions
|--------------------------------------------------------------------------
*/

const actions = computed(() => [
    /*
     * Edit
     */
    {
        key: 'edit',

        label: t('app.edit', 'Edit'),

        icon: Edit2,

        variant: 'default',

        visible: (item: any) => !item.deleted_at,

        handler: ({ item }: any) => {
            router.visit(withLocale(`/dashboard/categories/${item.id}/edit`));
        },
    },

    /*
     * Soft Delete
     */
    {
        key: 'delete',

        label: t('app.delete', 'Delete'),

        icon: Trash2,

        variant: 'destructive',

        visible: (item: any) => !item.deleted_at,

        handler: ({ item, openDelete }: any) => {
            openDelete(item);
        },
    },

    /*
     * Restore
     */
    {
        key: 'restore',

        label: t('app.restore', 'Restore'),

        icon: RotateCcw,

        variant: 'outline',

        visible: (item: any) => !!item.deleted_at,

        handler: ({ item, openRestore }: any) => {
            openRestore(item);
        },
    },

    /*
     * Permanent Delete
     */
    {
        key: 'force_delete',

        label: t('app.delete_permanently', 'Delete Permanently'),

        icon: Trash2,

        variant: 'destructive',

        visible: (item: any) => !!item.deleted_at,

        handler: ({ item, openDelete }: any) => {
            openDelete(item);
        },
    },
]);

/*
|--------------------------------------------------------------------------
| Delete
|--------------------------------------------------------------------------
*/

const handleDelete = ({ item, force }: { item: any; force: boolean }) => {
    const basePath = `/dashboard/categories/${item.id}`;

    /*
     * If the category is already trashed, or the table
     * explicitly requested force deletion, permanently delete it.
     */
    if (force || item.deleted_at) {
        router.delete(withLocale(`${basePath}?force=1`), {
            preserveScroll: true,

            onSuccess: () => {
                // Optional: AdminTable/Inertia will refresh the data.
            },
        });

        return;
    }

    /*
     * Normal deletion = soft delete.
     */
    router.delete(withLocale(basePath), {
        preserveScroll: true,

        onSuccess: () => {
            // Optional: AdminTable/Inertia will refresh the data.
        },
    });
};

/*
|--------------------------------------------------------------------------
| Restore
|--------------------------------------------------------------------------
*/

const handleRestore = (item: any) => {
    router.post(
        withLocale(`/dashboard/categories/${item.id}/restore`),
        {},
        {
            preserveScroll: true,
        },
    );
};
</script>

<template>
    <div>
        <!-- Page title -->
        <Head :title="t('categories.title', 'Categories')" />

        <!-- Header -->
        <div class="m-3 flex items-center justify-between">
            <h1 class="text-2xl font-bold">
                {{ t('categories.title', 'Categories') }}
            </h1>

            <Button type="button" @click="showCreateModal = true">
                {{ t('app.create', 'Create') }}
            </Button>
        </div>

        <!-- Categories Table -->
        <AdminTable
            table-key="categories-table"
            :title="t('categories.title', 'Categories')"
            :columns="columns"
            :data="categories"
            :endpoint="withLocale('/dashboard/categories')"
            :actions="actions"
            :enable-soft-deletes="true"
            @delete="handleDelete"
            @restore="handleRestore"
        />

        <!-- Create Category Dialog -->
        <CreateCategoryDialog
            v-model:open="showCreateModal"
            :parent-categories="props.parentCategories ?? []"
            :endpoint="withLocale('/dashboard/categories')"
        />
    </div>
</template>
