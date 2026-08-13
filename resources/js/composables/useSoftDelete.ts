import { router } from '@inertiajs/vue3';

import { withLocale } from '@/utils/index.js';

interface SoftDeleteItem {
    id: string;
    deleted_at?: string | null;
}

interface DeletePayload {
    item: SoftDeleteItem;
    force?: boolean;
}

interface Options {
    basePath: string;
}

export function useSoftDelete({ basePath }: Options) {
    const deleteItem = ({ item, force = false }: DeletePayload) => {
        const url = `${basePath}/${item.id}`;

        const deleteUrl = force || item.deleted_at ? `${url}?force=1` : url;

        router.delete(withLocale(deleteUrl), {
            preserveScroll: true,
        });
    };

    const restoreItem = (item: SoftDeleteItem) => {
        router.post(
            withLocale(`${basePath}/${item.id}/restore`),
            {},
            {
                preserveScroll: true,
            },
        );
    };

    return {
        deleteItem,
        restoreItem,
    };
}
