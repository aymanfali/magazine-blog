import { CheckCircle, CircleX } from '@lucide/vue';

export function createCategoryColumns(
    t: Function,
    parentCategories: Array<{
        id: string;
        name: string;
    }>,
) {
    const parentOptions = Object.fromEntries(
        parentCategories.map((category) => [
            String(category.id),
            {
                label: category.name,
            },
        ]),
    );

    return [
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
            options: parentOptions,
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
    ];
}
