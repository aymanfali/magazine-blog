import { Edit2, Eye, RotateCcw, Trash2 } from '@lucide/vue';

interface CategoryActionHandlers {
    openShow: (item: any) => void;
    openEdit: (item: any) => void;
}

export function createCategoryActions(
    t: (key: string, fallback?: string) => string,
    handlers: CategoryActionHandlers,
) {
    return [
        {
            key: 'show',
            label: t('app.show', 'Show'),
            icon: Eye,
            variant: 'default',

            visible: (item: any) => !item.deleted_at,

            handler: ({ item }: any) => {
                handlers.openShow(item);
            },
        },

        {
            key: 'edit',
            label: t('app.edit', 'Edit'),
            icon: Edit2,
            variant: 'default',

            visible: (item: any) => !item.deleted_at,

            handler: ({ item }: any) => {
                handlers.openEdit(item);
            },
        },

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
    ];
}
