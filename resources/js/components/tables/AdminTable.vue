<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ArrowDownAz,
    ArrowUpAz,
    ChevronLeft,
    ChevronRight,
    RotateCcw,
} from '@lucide/vue';
import { useDebounceFn } from '@vueuse/core';

import { computed, onMounted, ref, watch } from 'vue';
import RestoreConfirmModal from '@/components/modals/RestoreConfirmModal.vue';
import { useLang } from '@/composables/useLang';
import BaseImage from '../images/BaseImage.vue';
import Button from '../ui/button/Button.vue';
import DropdownMenu from '../ui/dropdown-menu/DropdownMenu.vue';
import DropdownMenuContent from '../ui/dropdown-menu/DropdownMenuContent.vue';
import DropdownMenuItem from '../ui/dropdown-menu/DropdownMenuItem.vue';
import DropdownMenuTrigger from '../ui/dropdown-menu/DropdownMenuTrigger.vue';
import Input from '../ui/input/Input.vue';

import Popover from '../ui/popover/Popover.vue';
import PopoverContent from '../ui/popover/PopoverContent.vue';
import PopoverTrigger from '../ui/popover/PopoverTrigger.vue';
import RangeCalendar from '../ui/range-calendar/RangeCalendar.vue';
import Select from '../ui/select/Select.vue';
import SelectContent from '../ui/select/SelectContent.vue';
import SelectItem from '../ui/select/SelectItem.vue';
import SelectTrigger from '../ui/select/SelectTrigger.vue';
import SelectValue from '../ui/select/SelectValue.vue';

import DeleteConfirmModal from '@/components/modals/DeleteConfirmModal.vue';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { useDateFormatter } from '@/utils/date';

const { t, locale } = useLang();
const isRTL = computed(() => {
    return locale.value === 'ar';
});

const { format } = useDateFormatter();

const PrevIcon = computed(() => (isRTL.value ? ChevronRight : ChevronLeft));
const NextIcon = computed(() => (isRTL.value ? ChevronLeft : ChevronRight));

const loading = ref(false);
const itemToDelete = ref<any>(null);
const showDelete = ref(false);
const isItemTrashed = ref(false);

const itemToRestore = ref<any>(null);
const showRestore = ref(false);

router.on('start', () => {
    loading.value = true;
});

function shouldUseOverflow(item: any) {
    return (resolveActions(item)?.length || 0) > 3;
}

router.on('finish', () => {
    loading.value = false;
});

type TableAction = {
    key: string; // unique identifier
    icon?: any;
    label?: string;

    handler?: (ctx: {
        item: any;
        emit: any;
        router: typeof router;
        refresh: () => void;
        openDelete: (item: any) => void;
        openRestore: (item: any) => void;
    }) => void;

    route?: {
        name?: string; // optional future
        url?: (item: any) => string;
        method?: 'get' | 'post' | 'put' | 'delete';
    };

    visible?: (item: any) => boolean;
    disabled?: (item: any) => boolean;

    variant?: string; // for UI styling (destructive, ghost...)
};

/* -----------------------------------
Props
----------------------------------- */
const props = defineProps<{
    tableKey: string;
    columns: any[];
    data: {
        data: any[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    endpoint: string;

    actions?: TableAction[];

    buttonComponent?: any;

    enableSoftDeletes?: boolean;
}>();

const emit = defineEmits(['updated', 'show', 'delete', 'restore']);

/* -----------------------------------
   Query State (SERVER CONTROLLED)
----------------------------------- */
const search = ref('');
const rows = computed(() => {
    if (Array.isArray(props.data)) {
        return props.data;
    }

    return Array.isArray(props.data?.data) ? props.data.data : [];
});

const paginationMeta = computed(() => {
    if (Array.isArray(props.data)) {
        const total = props.data.length;

        return {
            current_page: 1,
            last_page: total > 0 ? 1 : 0,
            per_page: total || 10,
            total,
        };
    }

    return {
        current_page: props.data?.current_page ?? 1,
        last_page: props.data?.last_page ?? 1,
        per_page: props.data?.per_page ?? 10,
        total: props.data?.total ?? rows.value.length,
    };
});
// console.log(props.data);

const page = ref(props.data.current_page);
const perPage = ref(props.data.per_page);
const perPageOptions = [5, 10, 25, 50, 100];

function changePerPage(value: string) {
    const newPerPage = Number(value);

    if (!perPageOptions.includes(newPerPage)) {
        return;
    }

    perPage.value = newPerPage;
    page.value = 1;

    fetch({
        page: 1,
        per_page: newPerPage,
    });
}

const sortKey = ref<string | null>(null);
const sortDirection = ref<'asc' | 'desc'>('asc');

const columnFilters = ref<Record<string, any>>({});

const goToPageInput = ref<number | undefined>(undefined);

function goToPage() {
    if (goToPageInput.value == null) {
        return;
    }

    const target = Math.max(
        1,
        Math.min(goToPageInput.value, paginationMeta.value.last_page),
    );

    changePage(target);
    goToPageInput.value = undefined;
}

/* -----------------------------------
   Sync page from backend
----------------------------------- */
watch(
    () => paginationMeta.value.current_page,
    (val) => (page.value = val),
);

let isInitializing = false;

function syncStateFromUrl() {
    isInitializing = true;
    const params = new URLSearchParams(window.location.search);

    if (params.has('search')) {
        search.value = params.get('search') || '';
    }

    if (params.has('sort_key')) {
        sortKey.value = params.get('sort_key');
    }

    if (params.has('sort_direction')) {
        const dir = params.get('sort_direction');

        if (dir === 'asc' || dir === 'desc') {
            sortDirection.value = dir;
        }
    }

    if (params.has('page')) {
        const p = parseInt(params.get('page') || '1', 10);

        if (!isNaN(p) && p > 0) {
            page.value = p;
        }
    }

    if (params.has('per_page')) {
        const pp = parseInt(params.get('per_page') || '10', 10);

        if (!isNaN(pp) && pp > 0) {
            perPage.value = pp;
        }
    }

    if (props.columns) {
        props.columns.forEach((col) => {
            const key =
                typeof col.filterKey === 'string' ? col.filterKey : col.key;

            if (params.has(key)) {
                columnFilters.value[col.key] = params.get(key);
            }
        });
    }

    if (props.enableSoftDeletes) {
        if (params.has('only_trashed') && params.get('only_trashed') === '1') {
            trashedState.value = 'only';
        } else if (
            params.has('with_trashed') &&
            params.get('with_trashed') === '1'
        ) {
            trashedState.value = 'with';
        } else {
            trashedState.value = 'without';
        }
    }

    setTimeout(() => {
        isInitializing = false;
    }, 500);
}

onMounted(() => {
    syncStateFromUrl();
});

/* -----------------------------------
   Request builder
----------------------------------- */
function formatDate(date: any) {
    if (!date) {
        return null;
    }

    // If it's already string
    if (typeof date === 'string') {
        return date;
    }

    // If it's CalendarDate (from @internationalized/date)
    if (date.year && date.month && date.day) {
        return `${date.year}-${String(date.month).padStart(2, '0')}-${String(date.day).padStart(2, '0')}`;
    }

    return null;
}

function isValid(v: any) {
    return v !== null && v !== undefined && v !== '' && v !== '__all__';
}

function mapFilters(filters: Record<string, any>) {
    const out: Record<string, any> = {};

    for (const [key, value] of Object.entries(filters)) {
        if (!isValid(value)) {
            continue;
        }

        // Date range
        if (value?.start || value?.end) {
            const start = formatDate(value.start);
            const end = formatDate(value.end);

            if (start) {
                out[`${key}_start`] = start;
            }

            if (end) {
                out[`${key}_end`] = end;
            }

            continue;
        }

        const column = props.columns.find((column) => column.key === key);

        if (!column) {
            out[key] = value;
            continue;
        }

        if (!column.filterKey) {
            out[key] = value;
            continue;
        }

        const filterKey =
            typeof column.filterKey === 'string'
                ? column.filterKey
                : (column.filterKey[locale.value] ?? column.filterKey.en);

        out[filterKey] = value;
    }

    return out;
}

function buildQuery() {
    const query: Record<string, any> = {};

    // search
    if (search.value?.trim()) {
        query.search = search.value.trim();
    }

    // pagination (always include)
    query.page = page.value;
    query.per_page = perPage.value;

    // sorting (only if valid)
    if (sortKey.value) {
        query.sort_key = sortKey.value;
        query.sort_direction = sortDirection.value;
    }

    // column filters (only active ones)
    const filters = mapFilters(columnFilters.value);

    Object.assign(query, filters);

    if (props.enableSoftDeletes) {
        if (trashedState.value === 'only') {
            query.only_trashed = 1;
        } else if (trashedState.value === 'with') {
            query.with_trashed = 1;
        }
    }

    return query;
}

function fetch(params: Record<string, any> = {}) {
    let targetEndpoint = props.endpoint || window.location.pathname;

    if (
        targetEndpoint.startsWith('/') &&
        !/^\/(en|ar)(\/|$)/.test(targetEndpoint)
    ) {
        targetEndpoint = `/${locale.value}${targetEndpoint}`;
    }

    router.get(
        targetEndpoint,
        {
            ...buildQuery(),
            ...params,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
}

/* -----------------------------------
   SEARCH (debounced server request)
----------------------------------- */
const triggerSearch = useDebounceFn(() => {
    if (isInitializing) {
        return;
    }

    page.value = 1;
    fetch();
}, 400);

watch(search, triggerSearch);

/* -----------------------------------
   SORT (server-side)
----------------------------------- */
function toggleSort(key: string) {
    if (sortKey.value === key) {
        sortDirection.value = sortDirection.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDirection.value = 'asc';
    }

    page.value = 1;

    fetch();
}

/* -----------------------------------
   FILTERS (server-side)
----------------------------------- */
const triggerColumnFilter = useDebounceFn(() => {
    if (isInitializing) {
        return;
    }

    const filters = columnFilters.value;

    // check if any incomplete range exists
    for (const key in filters) {
        const val = filters[key];

        if (val?.start && !val?.end) {
            return;
        } // ⛔ wait

        if (!val?.start && val?.end) {
            return;
        } // ⛔ wait
    }

    page.value = 1;
    fetch();
}, 400);

watch(columnFilters, triggerColumnFilter, { deep: true });

/* -----------------------------------
   PAGINATION
----------------------------------- */
function changePage(p: number) {
    if (p < 1 || p > paginationMeta.value.last_page) {
        return;
    }

    page.value = p;
    fetch();
}

/* -----------------------------------
   ACTIONS
----------------------------------- */
function executeAction(action: TableAction, item: any) {
    if (action.handler) {
        return action.handler({
            item,
            emit,
            router,
            refresh: fetch,
            openDelete,
            openRestore,
        });
    }

    if (action.route?.url) {
        const url = action.route.url(item);

        return router[action.route.method || 'get'](url);
    }
}

function resolveActions(item: any) {
    return props.actions?.filter((a) => !a.visible || a.visible(item));
}

function openDelete(item: any) {
    itemToDelete.value = item;
    isItemTrashed.value = !!item?.deleted_at;
    showDelete.value = true;
}

function confirmDelete(payload: { force: boolean }) {
    if (!itemToDelete.value) {
        return;
    }

    emit('delete', { item: itemToDelete.value, force: payload.force });

    showDelete.value = false;
    itemToDelete.value = null;
}

function openRestore(item: any) {
    itemToRestore.value = item;
    showRestore.value = true;
}

function confirmRestore() {
    if (!itemToRestore.value) {
        return;
    }

    emit('restore', itemToRestore.value);

    showRestore.value = false;
    itemToRestore.value = null;
}

/* -----------------------------------
   Pagination helpers
----------------------------------- */
const visiblePages = computed(() => {
    const pages: (number | string)[] = [];
    const last = paginationMeta.value.last_page;
    const current = page.value;

    for (let i = 1; i <= last; i++) {
        if (i === 1 || i === last || (i >= current - 2 && i <= current + 2)) {
            pages.push(i);
        }
    }

    const result: (number | string)[] = [];
    let prev: number | null = null;

    for (const p of pages) {
        if (typeof p === 'number') {
            if (prev !== null && p - prev > 1) {
                result.push('...');
            }

            result.push(p);
            prev = p;
        }
    }

    return result;
});

const trashedState = ref<'without' | 'only' | 'with'>('without');

function changeTrashedState(value: string) {
    trashedState.value = value as 'without' | 'only' | 'with';
    page.value = 1;
    fetch();
}

function resetFilters() {
    search.value = '';
    columnFilters.value = {};
    page.value = 1;
    sortKey.value = null;
    sortDirection.value = 'asc';
    trashedState.value = 'without';

    fetch();
}

const filteredData = computed(() => rows.value);

const hasActiveFilters = computed(() => {
    const filters = Object.values(columnFilters.value).some(isValid);

    return !!(
        search.value?.trim() ||
        filters ||
        sortKey.value ||
        (props.enableSoftDeletes && trashedState.value !== 'without')
    );
});

function rangeText(key: string) {
    const val = columnFilters.value[key];

    if (!val) {
        return '';
    }

    if (val.start && val.end) {
        return `${val.start} → ${val.end}`;
    }

    if (val.start) {
        return `From ${val.start}`;
    }

    if (val.end) {
        return `To ${val.end}`;
    }

    return '';
}

function normalizeBadgeValue(value: any): string {
    if (value === true) {
        return '1';
    }

    if (value === false) {
        return '0';
    }

    if (value === null || value === undefined) {
        return '';
    }

    return String(value);
}

const getBadgeLabel = (col: any, value: any) => {
    const normalized = normalizeBadgeValue(value);

    return col.options?.[normalized]?.label ?? value;
};

const getBadgeClass = (col: any, value: any) => {
    const normalized = normalizeBadgeValue(value);
    const color = col.options?.[normalized]?.color;

    const map: Record<string, string> = {
        green: 'bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400',
        red: 'bg-red-100 text-red-800 dark:bg-red-900/20 dark:text-red-400',
        yellow: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/20 dark:text-yellow-400',
        blue: 'bg-blue-100 text-blue-800 dark:bg-blue-900/20 dark:text-blue-400',
        purple: 'bg-purple-100 text-purple-800 dark:bg-purple-900/20 dark:text-purple-400',
        gray: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300',
    };

    return map[color || 'gray'];
};
const getBadgeIcon = (col: any, value: any) => {
    const normalized = normalizeBadgeValue(value);

    return col.options?.[normalized]?.icon ?? null;
};

const normalizeValue = (value: any): any[] => {
    if (Array.isArray(value)) {
        return value;
    }

    if (value === null || value === undefined || value === '') {
        return [];
    }

    return [value];
};

function resolveValue(item: any, col: any) {
    if (col.source) {
        const sourceKey = locale.value === 'ar' ? col.source.ar : col.source.en;

        return (
            item[sourceKey] ?? item[col.source.en] ?? item[col.source.ar] ?? '-'
        );
    }

    const raw = item[col.key];

    if (raw === null || raw === undefined || raw === '') {
        return '-';
    }

    // STRICT ISO detection (your backend format includes Z + microseconds)
    const isIsoDate =
        typeof raw === 'string' && /^\d{4}-\d{2}-\d{2}T/.test(raw);

    if (isIsoDate) {
        const date = new Date(raw);

        if (!isNaN(date.getTime())) {
            return format(date);
        }
    }

    return raw;
}
</script>

<template>
    <div
        class="dark:border-dark-border dark:bg-dark-secondary m-2 w-full overflow-hidden rounded-2xl border shadow-md"
    >
        <div class="my-4 flex items-center justify-between px-4">
            <div class="flex items-center gap-2">
                <Input
                    v-model="search"
                    type="text"
                    :placeholder="t('app.search', 'Search...')"
                    :class="[
                        'rounded-lg border bg-accent px-3 py-1 text-sm outline-none',
                    ]"
                />
                <Select
                    v-if="enableSoftDeletes"
                    :model-value="trashedState"
                    @update:model-value="changeTrashedState"
                >
                    <SelectTrigger class="h-9 w-36 text-xs">
                        <SelectValue
                            :placeholder="
                                t('app.trashed_filter', 'Status / Trash')
                            "
                        />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="without">
                            {{ t('app.without_trashed', 'Active Items') }}
                        </SelectItem>
                        <SelectItem value="only">
                            <span class="text-red-500">{{ t('app.only_trashed', 'Only Trashed') }}</span>
                        </SelectItem>
                        <SelectItem value="with">
                            {{ t('app.with_trashed', 'With Trashed') }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <Button variant="ghost" @click="resetFilters">
                    <RotateCcw />
                </Button>
            </div>

            <div class="flex items-center gap-2">
                <!-- Rows per page -->
                <div class="flex items-center gap-2">
                    <Select
                        v-model="perPage"
                        @update:model-value="changePerPage"
                    >
                        <SelectTrigger class="w-25">
                            <SelectValue
                                :placeholder="t('app.choose', 'Choose')"
                            />
                        </SelectTrigger>

                        <SelectContent>
                            <SelectItem
                                v-for="option in perPageOptions"
                                :key="option"
                                :value="option"
                            >
                                {{ option }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="text-sm text-gray-500">
                    {{ t('app.showing') }}
                    {{
                        (paginationMeta.current_page - 1) *
                            paginationMeta.per_page +
                        1
                    }}–{{
                        Math.min(
                            paginationMeta.current_page *
                                paginationMeta.per_page,
                            paginationMeta.total,
                        )
                    }}
                    {{ t('app.of') }} {{ paginationMeta.total }}
                </div>
            </div>
        </div>

        <!-- TABLE -->
        <Table>
            <TableHeader>
                <TableRow>
                    <TableHead
                        class="text-center"
                        v-for="col in columns"
                        :key="col.key"
                        @click="col.sortable && toggleSort(col.key)"
                    >
                        <div class="flex items-center justify-center gap-1">
                            {{ col.label }}

                            <span
                                v-if="sortKey === col.key"
                                class="flex items-center"
                                aria-hidden="true"
                            >
                                <ArrowUpAz
                                    v-if="sortDirection === 'asc'"
                                    class="h-4 w-4 text-gray-600 dark:text-gray-300"
                                />
                                <ArrowDownAz
                                    v-else
                                    class="h-4 w-4 text-gray-600 dark:text-gray-300"
                                />
                            </span>
                        </div>
                    </TableHead>
                    <TableHead v-if="actions?.length" class="text-center">
                        {{ t('app.actions') }}
                    </TableHead>
                </TableRow>
                <TableRow>
                    <TableHead v-for="col in columns" :key="col.key">
                        <div v-if="col.filterable">
                            <!-- ================= SELECT ================= -->
                            <template
                                v-if="
                                    col.filterType === 'select' && col.options
                                "
                            >
                                <Select
                                    :model-value="
                                        columnFilters[col.key] ?? '__all__'
                                    "
                                    @update:model-value="
                                        (value) => {
                                            columnFilters[col.key] =
                                                value === '__all__'
                                                    ? null
                                                    : value;
                                        }
                                    "
                                >
                                    <SelectTrigger class="h-8 w-full text-xs">
                                        <SelectValue
                                            :placeholder="t('app.all', 'All')"
                                        />
                                    </SelectTrigger>

                                    <SelectContent>
                                        <SelectItem value="__all__">
                                            {{ t('app.all', 'All') }}
                                        </SelectItem>

                                        <SelectItem
                                            v-for="(opt, key) in col.options"
                                            :key="key"
                                            :value="String(key)"
                                        >
                                            {{ opt.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </template>

                            <!-- ================= DATE / RANGE ================= -->
                            <template
                                v-else-if="
                                    col.filterType === 'date' ||
                                    col.filterType === 'date-range'
                                "
                            >
                                <!-- SINGLE DATE -->
                                <template v-if="col.filterType === 'date'">
                                    <Input
                                        type="date"
                                        v-model="columnFilters[col.key]"
                                    />
                                </template>

                                <!-- DATE RANGE -->
                                <template
                                    v-else-if="col.filterType === 'date-range'"
                                >
                                    <Popover>
                                        <PopoverTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                class="w-full justify-start border border-accent text-left font-normal"
                                            >
                                                {{
                                                    rangeText(col.key) ||
                                                    t('app.filter', 'Filter')
                                                }}
                                            </Button>
                                        </PopoverTrigger>
                                        <PopoverContent class="w-auto p-2">
                                            <RangeCalendar
                                                v-model="columnFilters[col.key]"
                                            />
                                        </PopoverContent>
                                    </Popover>
                                </template>

                                <!-- FALLBACK -->
                                <template v-else>
                                    <Input
                                        v-model="columnFilters[col.key]"
                                        type="text"
                                    />
                                </template>
                            </template>

                            <!-- ================= TEXT ================= -->
                            <template v-else>
                                <Input
                                    v-model="columnFilters[col.key]"
                                    type="text"
                                    :placeholder="t('app.filter', 'Filter')"
                                    class="my-2 w-full rounded border px-2 py-1 text-xs"
                                />
                            </template>
                        </div>
                    </TableHead>
                </TableRow>
            </TableHeader>

            <TableBody>
                <template v-if="loading">
                    <TableRow v-for="i in columns.length" :key="i">
                        <TableCell v-for="col in columns" :key="col.key">
                            <div
                                class="h-4 w-full animate-pulse rounded bg-gray-200 dark:bg-gray-700"
                            ></div>
                        </TableCell>
                    </TableRow>
                </template>
                <template v-else>
                    <TableRow v-if="filteredData.length === 0">
                        <TableCell
                            :colspan="
                                columns.length + (actions?.length ? 1 : 0)
                            "
                            class="py-12 text-center"
                        >
                            {{
                                hasActiveFilters
                                    ? t('app.no_matching_results')
                                    : t('app.no_available_data')
                            }}
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="item in rows" :key="item.id">
                        <TableCell v-for="col in columns" :key="col.key">
                            <slot :name="`cell-${col.key}`" :item="item">
                                <template v-if="col.type === 'image'">
                                    <BaseImage
                                        v-if="item[col.key]"
                                        :src="item[col.key]"
                                        :alt="col.alt || col.key"
                                        class="h-10 w-16 rounded-md object-cover"
                                    />
                                    <div
                                        v-else
                                        class="flex h-10 w-16 items-center justify-center rounded-md bg-accent"
                                    >
                                        <svg
                                            class="h-5 w-5 text-gray-400"
                                            fill="none"
                                            stroke="currentColor"
                                            viewBox="0 0 24 24"
                                        >
                                            <path
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                                stroke-width="2"
                                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
                                            />
                                        </svg>
                                    </div>
                                </template>

                                <template v-else-if="col.type === 'badge'">
                                    <div class="flex flex-wrap gap-1">
                                        <span
                                            v-for="(val, i) in normalizeValue(
                                                item[col.key],
                                            )"
                                            :key="i"
                                            :class="getBadgeClass(col, val)"
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium"
                                        >
                                            <!-- Icon -->
                                            <component
                                                v-if="getBadgeIcon(col, val)"
                                                :is="getBadgeIcon(col, val)"
                                                class="h-3 w-3"
                                            />

                                            <!-- Label -->
                                            {{ getBadgeLabel(col, val) }}
                                        </span>
                                    </div>
                                </template>

                                <template v-else>
                                    <span class="dark:text-dark-text text-sm">{{
                                        resolveValue(item, col)
                                    }}</span>
                                </template>
                            </slot>
                        </TableCell>

                        <TableCell
                            v-if="actions?.length"
                            class="flex justify-center gap-2"
                        >
                            <template v-if="!shouldUseOverflow(item)">
                                <component
                                    v-for="action in resolveActions(item)"
                                    :key="action.key"
                                    :is="buttonComponent || Button"
                                    @click="executeAction(action, item)"
                                    :disabled="action.disabled?.(item)"
                                    :variant="action.variant"
                                >
                                    <component
                                        v-if="action.icon"
                                        :is="action.icon"
                                    />
                                </component>
                            </template>
                            <DropdownMenu v-else>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost">•••</Button>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent>
                                    <DropdownMenuItem
                                        v-for="action in resolveActions(item)"
                                        :key="action.key"
                                        @click="
                                            !action.disabled?.(item) &&
                                            executeAction(action, item)
                                        "
                                        :class="{
                                            'pointer-events-none opacity-50':
                                                action.disabled?.(item),
                                        }"
                                    >
                                        <component
                                            v-if="action.icon"
                                            :is="action.icon"
                                        />
                                        {{ action.label }}
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </template>
            </TableBody>
        </Table>

        <!-- Pagination -->
        <div
            v-if="paginationMeta.last_page > 1"
            class="flex flex-col items-center justify-center gap-3 md:flex-row"
        >
            <!-- Controls -->
            <div class="flex flex-wrap items-center justify-center gap-1">
                <!-- Prev -->
                <Button
                    variant="outline"
                    size="icon"
                    @click="changePage(page - 1)"
                    :disabled="page === 1"
                >
                    <component :is="PrevIcon" class="h-4 w-4" />
                </Button>

                <!-- Pages -->
                <Button
                    v-for="(p, index) in visiblePages"
                    :key="index"
                    variant="ghost"
                    @click="typeof p === 'number' && changePage(p)"
                    :disabled="p === '...'"
                    class="h-9 w-9 p-0"
                    :class="[
                        p === page
                            ? 'bg-muted font-medium text-foreground'
                            : 'text-muted-foreground hover:bg-muted',
                    ]"
                >
                    {{ p }}
                </Button>

                <!-- Next -->
                <Button
                    variant="outline"
                    size="icon"
                    @click="changePage(page + 1)"
                    :disabled="page === paginationMeta.last_page"
                >
                    <component :is="NextIcon" class="h-4 w-4" />
                </Button>
            </div>

            <!-- Go to page -->
            <div class="my-3 flex items-center gap-2">
                <span class="text-sm">|</span>

                <Input
                    v-model.number="goToPageInput"
                    type="number"
                    :min="1"
                    :max="paginationMeta.last_page"
                    :placeholder="t('app.goto', 'Go to page')"
                    class="h-9 w-24 text-center"
                    @keyup.enter="goToPage"
                />

                <Button variant="secondary" @click="goToPage">
                    {{ t('app.go') }}
                </Button>
            </div>
        </div>

        <!-- DELETE MODAL -->
        <DeleteConfirmModal
            :show="showDelete"
            :is-trashed="isItemTrashed"
            @confirm="confirmDelete"
            @cancel="showDelete = false"
        />

        <!-- RESTORE MODAL -->
        <RestoreConfirmModal
            :show="showRestore"
            @confirm="confirmRestore"
            @cancel="showRestore = false"
        />
    </div>
</template>
