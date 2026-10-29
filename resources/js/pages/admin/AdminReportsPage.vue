<template>
    <AppShell section="admin" title="Reports & Analytics" subtitle="Complaint volume, outcomes and categories for a chosen date range.">
        <form class="flex flex-wrap items-end gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm" @submit.prevent="load">
            <div class="flex flex-wrap gap-2" role="group" aria-label="Quick ranges">
                <button v-for="preset in presets" :key="preset.days" type="button"
                    class="rounded-lg border px-3 py-2 text-sm font-medium"
                    :class="activePreset === preset.days ? 'border-blue-600 bg-blue-50 text-blue-700' : 'border-slate-300 text-slate-700 hover:bg-slate-50'"
                    @click="applyPreset(preset.days)">
                    {{ preset.label }}
                </button>
            </div>
            <div class="ml-auto flex flex-wrap items-end gap-3">
                <label class="grid gap-1 text-xs font-medium text-slate-500">From
                    <input v-model="from" type="date" :max="to || undefined" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                </label>
                <label class="grid gap-1 text-xs font-medium text-slate-500">To
                    <input v-model="to" type="date" :min="from || undefined" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-900" />
                </label>
                <button type="submit" class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700 transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100">Apply</button>
            </div>
        </form>

        <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <StatCard label="Complaints Received" icon="layers" tone="blue" :value="report?.totals.total ?? null" :loading="loading && !report" class="transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100" />
            <StatCard label="Resolved" icon="check-circle" tone="green" :value="report?.totals.resolved ?? null" :loading="loading && !report" class="transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100" />
            <StatCard label="Resolution Rate" icon="chart" tone="slate" :value="rate" :loading="loading && !report" hint="Resolved ÷ received in range" class="transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100" />
            <StatCard label="Avg. Time to Resolve" icon="clock" tone="amber" :value="averageTime" :loading="loading && !report" hint="For complaints resolved in range" class="transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100" />
        </div>

        <div class="mt-6 grid gap-6 xl:grid-cols-3">
            <ChartPanel class="xl:col-span-2" title="Complaints over time" :subtitle="trendSubtitle" :loading="loading && !report"
                :error="report ? '' : error" :empty="!report || report.totals.total === 0" empty-text="No complaints were submitted in this range."
                @retry="load">
                <TrendChart :data="report?.trend || []" aria-label="Complaints submitted over the selected range" />
            </ChartPanel>
            <ChartPanel title="Status breakdown" subtitle="Complaints in range" :loading="loading && !report"
                :error="report ? '' : error" :empty="!report || report.totals.total === 0" empty-text="No complaints in this range." @retry="load">
                <DonutChart :segments="segments" center-label="Complaints" aria-label="Complaints by status in range" />
            </ChartPanel>
        </div>

        <ChartPanel class="mt-6" title="Complaints by category" subtitle="Complaints in range" :loading="loading && !report"
            :error="report ? '' : error" :empty="!report || !report.category_breakdown.length" empty-text="No complaints in this range."
            @retry="load">
            <BarList :items="report?.category_breakdown || []" />
        </ChartPanel>

        <p v-if="report && error" class="mt-4 text-sm text-red-600" role="alert">{{ error }}</p>
    </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import axios from 'axios';
import AppShell from '../../layouts/AppShell.vue';
import StatCard from '../../components/StatCard.vue';
import ChartPanel from '../../components/ChartPanel.vue';
import TrendChart from '../../components/charts/TrendChart.vue';
import DonutChart from '../../components/charts/DonutChart.vue';
import BarList from '../../components/charts/BarList.vue';
import { extractMessage } from '../../utils/http';
import { STATUS_META } from '../../utils/status';

const presets = [
    { days: 7, label: 'Last 7 days' },
    { days: 30, label: 'Last 30 days' },
    { days: 90, label: 'Last 90 days' },
    { days: 365, label: 'Last 12 months' },
];

const toInput = (date) => {
    const pad = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
};

const report = ref(null);
const loading = ref(true);
const error = ref('');
const from = ref('');
const to = ref('');
const activePreset = ref(30);

function applyPreset(days) {
    const end = new Date();
    const start = new Date();
    start.setDate(end.getDate() - (days - 1));
    from.value = toInput(start);
    to.value = toInput(end);
    activePreset.value = days;
    load();
}

let requestId = 0;
async function load() {
    const current = ++requestId;
    loading.value = true;
    error.value = '';
    if (!presets.some((p) => p.days === activePreset.value)) activePreset.value = null;

    try {
        const { data } = await axios.get('/api/admin/analytics', { params: { from: from.value || undefined, to: to.value || undefined } });
        if (current !== requestId) return;
        report.value = data;
        from.value = data.range.from;
        to.value = data.range.to;
    } catch (err) {
        if (current !== requestId) return;
        error.value = extractMessage(err, 'Unable to load analytics.');
    } finally {
        if (current === requestId) loading.value = false;
    }
}

const segments = computed(() => Object.entries(STATUS_META).map(([key, meta]) => ({
    label: meta.label,
    color: meta.color,
    value: Number(report.value?.totals?.[key] || 0),
})));

const rate = computed(() => {
    if (!report.value) return null;
    return report.value.resolution_rate === null ? '—' : `${report.value.resolution_rate}%`;
});

const averageTime = computed(() => {
    if (!report.value) return null;
    const hours = report.value.average_resolution_hours;
    if (hours === null) return '—';
    return hours >= 48 ? `${(hours / 24).toFixed(1)} days` : `${hours} hrs`;
});

const trendSubtitle = computed(() => (report.value?.range.bucket === 'month' ? 'Grouped by month' : 'Grouped by day'));

onMounted(() => applyPreset(30));
</script>
