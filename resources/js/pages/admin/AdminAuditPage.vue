<template>
    <AppShell section="admin" title="Audit History" subtitle="A read-only record of important actions taken in the system.">
        <section class="rounded-xl border border-slate-200 bg-white shadow-sm">
            <form class="flex flex-wrap items-center gap-3 border-b border-slate-100 px-5 py-4" role="search" @submit.prevent="applySearch">
                <div class="relative min-w-[16rem] flex-1">
                    <label for="audit-search" class="sr-only">Search audit history</label>
                    <AppIcon name="search" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                    <input id="audit-search" v-model="search" type="search" autocomplete="off" placeholder="Search action, description or user"
                        class="w-full rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm placeholder:text-slate-400" />
                </div>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100">Search</button>
                <button type="button" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50" @click="reset">Reset</button>
            </form>

            <DataTable :columns="columns" :rows="rows" :loading="loading" :error="error" :meta="meta" :skeleton-rows="8"
                empty-title="No audit entries found" empty-text="Nothing has been recorded yet, or no entries match your search."
                @page="goToPage" @retry="load">
                <template #cell-created_at="{ row }"><span class="whitespace-nowrap">{{ formatDateTime(row.created_at) }}</span></template>
                <template #cell-action="{ row }">
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 font-mono text-xs text-slate-700">{{ row.action }}</span>
                </template>
                <template #cell-description="{ row }">{{ row.description || '—' }}</template>
                <template #cell-user="{ row }">{{ row.user || 'System' }}</template>
                <template #cell-target="{ row }">
                    <span v-if="row.target_type" class="whitespace-nowrap text-slate-600">{{ row.target_type }} #{{ row.target_id }}</span>
                    <span v-else>—</span>
                </template>
            </DataTable>
        </section>
    </AppShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import AppShell from '../../layouts/AppShell.vue';
import AppIcon from '../../components/AppIcon.vue';
import DataTable from '../../components/DataTable.vue';
import { extractMessage, normalizeItems } from '../../utils/http';
import { formatDateTime, pageMeta } from '../../utils/status';

const columns = [
    { key: 'created_at', label: 'When' },
    { key: 'action', label: 'Action' },
    { key: 'description', label: 'Description', class: 'hidden md:table-cell' },
    { key: 'user', label: 'Performed by' },
    { key: 'target', label: 'Target', class: 'hidden lg:table-cell' },
];

const rows = ref([]);
const meta = ref(null);
const page = ref(1);
const search = ref('');
const loading = ref(true);
const error = ref('');

let requestId = 0;
async function load() {
    const current = ++requestId;
    loading.value = true;
    error.value = '';

    try {
        const { data } = await axios.get('/api/admin/audit-logs', { params: { page: page.value, search: search.value || undefined } });
        if (current !== requestId) return;
        rows.value = normalizeItems(data);
        meta.value = pageMeta(data);
    } catch (err) {
        if (current !== requestId) return;
        rows.value = [];
        meta.value = null;
        error.value = extractMessage(err, 'Unable to load the audit history.');
    } finally {
        if (current === requestId) loading.value = false;
    }
}

function applySearch() {
    page.value = 1;
    load();
}

function reset() {
    search.value = '';
    applySearch();
}

function goToPage(next) {
    page.value = next;
    load();
}

onMounted(load);
</script>
