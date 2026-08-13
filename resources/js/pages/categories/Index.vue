<script setup lang="ts">
import { Head, setLayoutProps } from '@inertiajs/vue3';
import { PlusCircle } from '@lucide/vue';
import { computed } from 'vue';
import AdminTable from '@/components/tables/AdminTable.vue';
import Button from '@/components/ui/button/Button.vue';
import { useLang } from '@/composables/useLang';
import { useSoftDelete } from '@/composables/useSoftDelete';
import { dashboard } from '@/routes';
import { withLocale } from '@/utils/index.js';
import CreateCategoryDialog from './CreateCategoryDialog.vue';
import EditCategoryDialog from './EditCategoryDialog.vue';
import ShowCategoryDialog from './ShowCategoryDialog.vue';
import { useCrudDialogs } from '@/composables/useCrudDialogs';
import { createCategoryActions } from '@/configs/tables/categoryActions';
import { createCategoryColumns } from '@/configs/tables/categoryColumns';

interface Category {
    id: string;
    name: string;
    slug: string | null;
    description: string | null;
    image: string | null;
    image_url?: string | null;
    is_active: boolean;
    parent_id: string | null;
    deleted_at?: string | null;
}

interface ParentCategory {
    id: string;
    name: string;
}

interface Props {
    categories: any;
    parentCategories?: ParentCategory[];
}

const props = defineProps<Props>();

const { t } = useLang();

const endpoint = '/dashboard/categories';

const {
    createOpen,
    showOpen,
    editOpen,
    selected,

    openCreate,
    openShow,
    openEdit,

    closeShow,
    closeEdit,
} = useCrudDialogs<Category>();

const { deleteItem, restoreItem } = useSoftDelete({
    basePath: endpoint,
});

const columns = computed(() =>
    createCategoryColumns(t, props.parentCategories ?? []),
);

const actions = computed(() =>
    createCategoryActions(t, {
        openShow,
        openEdit,
    }),
);

setLayoutProps({
    breadcrumbs: [
        {
            title: t('dashboard.title', 'Dashboard'),
            href: dashboard(),
        },
        {
            title: t('categories.title', 'Categories'),
            href: withLocale(endpoint),
        },
    ],
});
</script>

<template>
    <div>
        <Head :title="t('categories.title', 'Categories')" />

        <div class="m-3 flex items-center justify-between">
            <h1 class="text-2xl font-bold">
                {{ t('categories.title', 'Categories') }}
            </h1>

            <Button type="button" @click="openCreate">
                <PlusCircle />

                {{ t('app.create', 'Create') }}
            </Button>
        </div>

        <AdminTable
            table-key="categories-table"
            :title="t('categories.title', 'Categories')"
            :columns="columns"
            :data="categories"
            :endpoint="withLocale(endpoint)"
            :actions="actions"
            :enable-soft-deletes="true"
            @delete="deleteItem"
            @restore="restoreItem"
        />

        <CreateCategoryDialog
            v-model:open="createOpen"
            :parent-categories="props.parentCategories ?? []"
            :endpoint="withLocale(endpoint)"
        />

        <ShowCategoryDialog
            :open="showOpen"
            :category="selected"
            @update:open="
                (value) => {
                    if (!value) closeShow();
                }
            "
        />

        <EditCategoryDialog
            :open="editOpen"
            :category="selected"
            :parent-categories="props.parentCategories ?? []"
            :endpoint="withLocale(endpoint)"
            @update:open="
                (value) => {
                    if (!value) closeEdit();
                }
            "
        />
    </div>
</template>
