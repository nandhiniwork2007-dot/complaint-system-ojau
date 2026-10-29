<template>
    <div>
        <div v-if="error" class="flex flex-col items-center gap-3 px-6 py-14 text-center" role="alert">
            <AppIcon name="alert" class="h-7 w-7 text-red-500" />
            <p class="max-w-md text-sm text-slate-600">{{ error }}</p>
            <button type="button"
                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                @click="$emit('retry')">
                <AppIcon name="refresh" class="h-4 w-4" /> Try again
            </button>
        </div>

        <template v-else>
            <div v-if="!loading && !rows.length" class="flex flex-col items-center gap-2 px-6 py-14 text-center">
                <AppIcon name="inbox" class="h-7 w-7 text-slate-400" />
                <p class="text-sm font-medium text-slate-700">{{ emptyTitle }}</p>
                <p class="max-w-sm text-sm text-slate-500">{{ emptyText }}</p>
                <slot name="empty-action" />
            </div>

            <div v-else class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm" :aria-busy="loading">
                    <thead class="bg-slate-50">
                        <tr>
                            <th v-for="column in columns" :key="column.key" scope="col"
                                class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500"
                                :class="column.class" :aria-sort="ariaSort(column)">
                                <button v-if="column.sortable" type="button"
                                    class="group inline-flex items-center gap-1 rounded uppercase tracking-wide hover:text-slate-900"
                                    @click="$emit('sort', column.sortKey || column.key)">
                                    {{ column.label }}
                                    <AppIcon :name="sortIcon(column)" class="h-3.5 w-3.5"
                                        :class="isSorted(column) ? 'text-blue-600' : 'text-slate-400 group-hover:text-slate-600'" />
                                </button>
                                <span v-else>{{ column.label }}</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template v-if="loading">
                            <tr v-for="n in skeletonRows" :key="`s${n}`">
                                <td v-for="column in columns" :key="column.key" class="px-4 py-4" :class="column.class">
                                    <div class="h-4 rounded bg-slate-100 motion-safe:animate-pulse"
                                        :class="n % 2 ? 'w-3/4' : 'w-1/2'"></div>
                                </td>
                            </tr>
                        </template>
                        <template v-else>
                            <tr v-for="row in rows" :key="row[rowKey]" class="hover:bg-slate-50">
                                <td v-for="column in columns" :key="column.key" class="px-4 py-3.5 align-middle text-slate-700"
                                    :class="column.class">
                                    <slot :name="`cell-${column.key}`" :row="row">{{ row[column.key] }}</slot>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div v-if="meta && meta.total > 0"
                class="flex flex-col items-center justify-between gap-3 border-t border-slate-100 px-4 py-3 text-sm text-slate-600 sm:flex-row">
                <p>
                    <template v-if="meta.from && meta.to">Showing <strong class="font-semibold text-slate-900">{{ meta.from }}–{{ meta.to }}</strong> of <strong class="font-semibold text-slate-900">{{ meta.total }}</strong></template>
                    <template v-else><strong class="font-semibold text-slate-900">{{ meta.total }}</strong> results</template>
                </p>
                <nav class="flex items-center gap-2" aria-label="Pagination">
                    <button type="button"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 font-medium hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="loading || meta.current_page <= 1" @click="$emit('page', meta.current_page - 1)">
                        <AppIcon name="chevron-left" class="h-4 w-4" /> Previous
                    </button>
                    <span class="px-1 tabular-nums">Page {{ meta.current_page }} of {{ meta.last_page }}</span>
                    <button type="button"
                        class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 font-medium hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-50"
                        :disabled="loading || meta.current_page >= meta.last_page" @click="$emit('page', meta.current_page + 1)">
                        Next <AppIcon name="chevron-right" class="h-4 w-4" />
                    </button>
                </nav>
            </div>
        </template>
    </div>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

const props = defineProps({
    columns: { type: Array, required: true },
    rows: { type: Array, default: () => [] },
    rowKey: { type: String, default: 'id' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    meta: { type: Object, default: null },
    sort: { type: Object, default: () => ({ key: '', direction: 'desc' }) },
    skeletonRows: { type: Number, default: 6 },
    emptyTitle: { type: String, default: 'Nothing here yet' },
    emptyText: { type: String, default: 'There are no records to show.' },
});
defineEmits(['sort', 'page', 'retry']);

const isSorted = (column) => props.sort?.key === (column.sortKey || column.key);
const ariaSort = (column) => {
    if (!column.sortable) return undefined;
    if (!isSorted(column)) return 'none';
    return props.sort.direction === 'asc' ? 'ascending' : 'descending';
};
const sortIcon = (column) => {
    if (!isSorted(column)) return 'arrow-up-down';
    return props.sort.direction === 'asc' ? 'arrow-up' : 'arrow-down';
};
</script>
