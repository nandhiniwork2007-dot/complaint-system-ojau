<template>
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">{{ heading }}</h3>
                <p v-if="description" class="mt-0.5 text-sm text-slate-500">{{ description }}</p>
            </div>
            <slot name="actions" />
        </header>

        <form class="grid gap-3 border-b border-slate-100 px-5 py-4 sm:grid-cols-2 lg:grid-cols-6" role="search"
            @submit.prevent="applyNow">
            <div class="relative sm:col-span-2" :class="compact ? 'lg:col-span-2' : 'lg:col-span-2'">
                <label for="complaint-search" class="sr-only">Search complaints</label>
                <AppIcon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input id="complaint-search" v-model="filters.search" type="search" autocomplete="off"
                    placeholder="Search title, reference or complainant"
                    class="w-full rounded-lg border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm placeholder:text-slate-400" />
            </div>
            <div>
                <label for="complaint-status" class="sr-only">Filter by status</label>
                <select id="complaint-status" v-model="filters.status"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    <option v-for="option in STATUS_OPTIONS" :key="option.value" :value="option.value">{{ option.label }}</option>
                </select>
            </div>
            <div>
                <label for="complaint-category" class="sr-only">Filter by category</label>
                <select id="complaint-category" v-model="filters.category_id"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm">
                    <option value="">All categories</option>
                    <option v-for="category in categories" :key="category.id" :value="String(category.id)">{{ category.name }}</option>
                </select>
            </div>
            <template v-if="!compact">
                <div>
                    <label for="complaint-from" class="sr-only">Submitted from</label>
                    <input id="complaint-from" v-model="filters.from" type="date" :max="filters.to || undefined"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" title="Submitted from" />
                </div>
                <div>
                    <label for="complaint-to" class="sr-only">Submitted to</label>
                    <input id="complaint-to" v-model="filters.to" type="date" :min="filters.from || undefined"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm" title="Submitted to" />
                </div>
                <div>
                    <label for="complaint-assignee" class="sr-only">Assigned user ID</label>
                    <input id="complaint-assignee" v-model="filters.assigned_to" type="number" min="1" placeholder="Assigned user ID"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm placeholder:text-slate-400" />
                </div>
            </template>
            <div class="flex gap-2" :class="compact ? 'lg:col-span-2 lg:justify-end' : ''">
                <button type="submit"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-700">
                    Apply
                </button>
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="reset">
                    Reset
                </button>
            </div>
        </form>

        <DataTable :columns="columns" :rows="rows" :loading="loading" :error="error" :meta="meta" :sort="sort"
            empty-title="No complaints found"
            :empty-text="hasFilters ? 'No complaints match the current filters. Try adjusting or resetting them.' : 'No complaints have been submitted yet.'"
            @sort="toggleSort" @page="goToPage" @retry="load">
            <template #cell-reference="{ row }">
                <span class="font-mono text-xs font-medium text-slate-600">{{ row.reference }}</span>
            </template>
            <template #cell-title="{ row }">
                <RouterLink :to="`/admin/complaints/${row.id}`"
                    class="line-clamp-1 max-w-xs font-medium text-slate-900 hover:text-blue-700">{{ row.title }}</RouterLink>
            </template>
            <template #cell-category="{ row }">{{ row.category?.name || '—' }}</template>
            <template #cell-user="{ row }">
                <span class="block max-w-[10rem] truncate">{{ row.user?.name || '—' }}</span>
            </template>
            <template #cell-submitted_at="{ row }">
                <span class="whitespace-nowrap">{{ formatDate(row.submitted_at || row.created_at) }}</span>
            </template>
            <template #cell-status="{ row }"><StatusBadge :status="row.status" /></template>
            <template #cell-priority="{ row }">
                <span class="inline-flex rounded-md px-2 py-0.5 text-xs font-medium capitalize"
                    :class="priorityClass(row.priority)">{{ row.priority || '—' }}</span>
            </template>
            <template #cell-actions="{ row }">
                <RouterLink :to="`/admin/complaints/${row.id}`"
                    class="inline-flex items-center gap-1 rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-700 hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700">
                    Manage <AppIcon name="arrow-right" class="h-3.5 w-3.5" />
                </RouterLink>
            </template>
        </DataTable>
    </section>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, reactive, ref, watch } from 'vue';
import axios from 'axios';
import { RouterLink } from 'vue-router';
import AppIcon from './AppIcon.vue';
import DataTable from './DataTable.vue';
import StatusBadge from './StatusBadge.vue';
import { extractMessage, normalizeItems } from '../utils/http';
import { STATUS_OPTIONS, formatDate, pageMeta } from '../utils/status';

const props = defineProps({
    heading: { type: String, default: 'Complaints' },
    description: { type: String, default: '' },
    compact: { type: Boolean, default: false },
    initialFilters: { type: Object, default: () => ({}) },
});

const blank = () => ({ search: '', status: '', category_id: '', assigned_to: '', from: '', to: '' });

const filters = reactive({ ...blank(), ...props.initialFilters });
const sort = reactive({ key: 'created_at', direction: 'desc' });
const page = ref(1);
const rows = ref([]);
const meta = ref(null);
const categories = ref([]);
const loading = ref(true);
const error = ref('');

const columns = computed(() => [
    { key: 'reference', label: 'Reference', sortable: true },
    { key: 'title', label: 'Subject', sortable: true },
    { key: 'category', label: 'Category', class: 'hidden md:table-cell' },
    { key: 'user', label: 'Complainant', class: 'hidden lg:table-cell' },
    { key: 'submitted_at', label: 'Submitted', sortable: true, sortKey: 'created_at' },
    { key: 'status', label: 'Status', sortable: true },
    { key: 'priority', label: 'Priority', sortable: true, class: 'hidden xl:table-cell' },
    { key: 'actions', label: 'Actions' },
]);

const hasFilters = computed(() => Object.values(filters).some((value) => value !== '' && value !== null));

let requestId = 0;
async function load() {
    const current = ++requestId;
    loading.value = true;
    error.value = '';

    const params = { page: page.value, sort: sort.key, direction: sort.direction };
    Object.entries(filters).forEach(([key, value]) => {
        if (value !== '' && value !== null && value !== undefined) params[key] = value;
    });

    try {
        const { data } = await axios.get('/api/admin/complaints', { params });
        if (current !== requestId) return;
        rows.value = normalizeItems(data);
        meta.value = pageMeta(data);
    } catch (err) {
        if (current !== requestId) return;
        rows.value = [];
        meta.value = null;
        error.value = extractMessage(err, 'Unable to load complaints.');
    } finally {
        if (current === requestId) loading.value = false;
    }
}

async function loadCategories() {
    try {
        const { data } = await axios.get('/api/admin/categories');
        categories.value = normalizeItems(data);
    } catch (err) {
        // Category filter is optional; the table still works without it.
        categories.value = [];
    }
}

function applyNow() {
    clearTimeout(debounce);
    page.value = 1;
    load();
}

function reset() {
    Object.assign(filters, blank());
    sort.key = 'created_at';
    sort.direction = 'desc';
    applyNow();
}

function toggleSort(key) {
    if (sort.key === key) {
        sort.direction = sort.direction === 'asc' ? 'desc' : 'asc';
    } else {
        sort.key = key;
        sort.direction = 'asc';
    }
    page.value = 1;
    load();
}

function goToPage(next) {
    page.value = next;
    load();
}

function priorityClass(priority) {
    return {
        urgent: 'bg-red-50 text-red-700',
        high: 'bg-orange-50 text-orange-700',
        low: 'bg-slate-100 text-slate-600',
    }[priority] || 'bg-slate-100 text-slate-700';
}

let debounce;
watch(() => filters.search, () => {
    clearTimeout(debounce);
    debounce = setTimeout(applyNow, 350);
});
watch(() => [filters.status, filters.category_id, filters.from, filters.to], applyNow);

onMounted(() => {
    load();
    loadCategories();
});
onBeforeUnmount(() => clearTimeout(debounce));
</script>
