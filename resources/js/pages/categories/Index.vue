<script setup lang="ts">
import { Head, router, setLayoutProps } from '@inertiajs/vue3';
import { CheckCircle, CircleX, Edit2, RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

import Modal from '@/components/modals/Modal.vue';
import AdminTable from '@/components/tables/AdminTable.vue';
import { useLang } from '@/composables/useLang';
import { dashboard } from '@/routes';

const { t, locale } = useLang();

function withLocale(path: string) {
    if (path.startsWith('/') && !/^\/(en|ar)(\/|$)/.test(path)) {
        return `/${locale.value}${path}`;
    }

    return path;
}

defineProps<{
    categories: any;
}>();

setLayoutProps({
    breadcrumbs: [
        {
            title: t('dashboard.title', 'Dashboard'),
            href: dashboard(),
        },
        {
            title: t('categories.title', 'Categories'),
            href: '/categories',
        },
    ],
});

/* -----------------------------
   COLUMNS
----------------------------- */
const columns = computed(() => [
    {
        key: 'name',
        label: t('categories.title'),
        sortable: true,
        filterable: true,
        filterType: 'text',
        filterKey: 'name',
        searchable: true,
    },
    {
        key: 'is_active',
        label: t('app.active'),
        type: 'badge',
        filterable: true,
        sortable: true,
        filterType: 'select',
        options: {
            '1': {
                label: t('app.active'),
                color: 'green',
                icon: CheckCircle,
            },
            '0': {
                label: t('app.inactive'),
                color: 'red',
                icon: CircleX,
            },
        },
        filterKey: 'is_active',
    },
    {
        key: 'created_at',
        label: t('app.created_at'),
        searchable: true,
        sortable: true,
        filterable: true,
        filterType: 'date-range',
    },
    {
        key: 'updated_at',
        label: t('app.updated_at'),
        sortable: true,
        filterable: true,
        filterType: 'date-range',
    },
    {
        key: 'deleted_at',
        label: t('app.deleted_at'),
        sortable: true,
        filterable: true,
        filterType: 'date-range',
    },
]);

/* -----------------------------
   ACTIONS
----------------------------- */

const showModal = ref(false);
const modalLink = ref('');

async function copyModalLink() {
    try {
        await (globalThis.navigator as any)?.clipboard?.writeText(
            modalLink.value,
        );
    } catch {
        (globalThis.window as any)?.prompt('Copy link', modalLink.value);
    }
}

const actions = computed(() => [
    {
        key: 'edit',
        label: t('app.edit'),
        icon: Edit2,
        variant: 'default',
        visible: (item: any) => !item.deleted_at,
        handler: ({ item }: any) => {
            router.visit(`/dashboard/categories/${item.id}/edit`);
        },
    },
    {
        key: 'delete',
        label: t('app.delete'),
        icon: Trash2,
        variant: 'destructive',
        visible: (item: any) => !item.deleted_at,
        handler: ({ item, openDelete }: any) => {
            openDelete(item);
        },
    },
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

const handleDelete = ({ item, force }: { item: any; force: boolean }) => {
    const path = (item.deleted_at || force)
        ? `/dashboard/categories/${item.id}?force=1`
        : `/dashboard/categories/${item.id}`;

    router.delete(withLocale(path), {
        preserveScroll: true,
    });
};

const handleRestore = (item: any) => {
    router.post(
        withLocale(`/dashboard/categories/${item.id}/restore`),
        {},
        { preserveScroll: true },
    );
};
</script>

<template>
    <div>
        <Head :title="t('categories.title')" />

        <div class="mx-auto my-3 flex items-center justify-between">
            <h1 class="text-2xl font-bold">
                {{ t('categories.title') }}
            </h1>

            <div class=""></div>
        </div>

        <AdminTable
            tableKey="categories-table"
            :title="t('categories.title')"
            :columns="columns"
            :data="categories"
            endpoint="/dashboard/categories"
            :actions="actions"
            :enable-soft-deletes="true"
            @delete="handleDelete"
            @restore="handleRestore"
        >
        </AdminTable>
        <Modal
            :show="showModal"
            title="Generated public link"
            @close="showModal = false"
        >
            <div class="wrap-break-word">{{ modalLink }}</div>
            <div class="mt-4">
                <button class="btn" @click="copyModalLink">Copy link</button>
            </div>
        </Modal>
    </div>
</template>
