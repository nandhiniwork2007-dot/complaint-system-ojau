<template>
    <AppShell section="admin" title="Admin Dashboard" subtitle="Operational command center.">
        <!-- Metrics row -->
        <div class="grid gap-4 mb-6 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard label="Total complaints" :value="stats.total" trend="All time" icon>
                <template #icon>
                    <Icon name="FileTextIcon" class="h-5 w-5" />
                </template>
            </StatCard>
            <StatCard label="Pending" :value="stats.pending" trend="Needs attention" icon>
                <template #icon>
                    <Icon name="ClockIcon" class="h-5 w-5" />
                </template>
            </StatCard>
            <StatCard label="In Progress" :value="stats.in_progress" trend="Being handled" icon>
                <template #icon>
                    <Icon name="TrendingUpIcon" class="h-5 w-5" />
                </template>
            </StatCard>
            <StatCard label="Resolved" :value="stats.resolved" trend="Completed" icon>
                <template #icon>
                    <Icon name="CheckCircle2Icon" class="h-5 w-5" />
                </template>
            </StatCard>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
            <!-- Trend chart -->
            <Card padded bordered>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-semibold text-text-main">Complaint trend</h3>
                    <Select
                        v-model="selectedPeriod"
                        label="Period"
                        class="w-32 shrink-0"
                    >
                        <option value="month">This month</option>
                        <option value="week">This week</option>
                        <option value="year">This year</option>
                    </Select>
                </div>
                <div class="h-[280px]">
                    <TrendChart :data="trend" height="240" showArea showPoints />
                </div>
                <p class="mt-3 text-xs text-text-secondary">Complaints created over the selected period.</p>
            </Card>

            <!-- Status distribution -->
            <Card padded bordered>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-semibold text-text-main">Status distribution</h3>
                </div>
                <div class="space-y-4">
                    <div
                        v-for="s in statusDistribution"
                        :key="s.status"
                        class="flex items-center gap-3"
                    >
                        <Badge :variant="s.status" class="w-20 shrink-0 text-xs font-semibold">{{ s.label }}</Badge>
                        <div class="flex-1 h-2 rounded-full bg-surface-border overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500" :class="statusColor(s.status)" :style="{ width: s.percent + '%' }"></div>
                        </div>
                        <span class="text-xs font-bold text-text-main w-8 text-right tabular-nums">{{ s.count }}</span>
                    </div>
                </div>
            </Card>

            <!-- Recent complaints -->
            <Card padded bordered class="lg:col-span-2">
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-semibold text-text-main">Recent complaints</h3>
                    <RouterLink to="/admin/complaints" class="text-xs font-medium text-primary hover:text-primary-dark transition-colors">Manage all →</RouterLink>
                </div>

                <div v-if="recent.length" class="space-y-3">
                    <div
                        v-for="complaint in recent"
                        :key="complaint.id"
                        class="flex items-center justify-between gap-3 p-4 rounded-xl border border-surface-border bg-surface hover:border-primary/30 hover:bg-surface-elevated/50 transition-all"
                    >
                        <div class="flex items-center gap-3 min-w-0">
                            <Badge :variant="complaint.status" class="shrink-0 text-xs font-semibold">{{ complaint.status.replace('_', ' ') }}</Badge>
                            <div class="min-w-0">
                                <RouterLink :to="`/admin/complaints/${complaint.id}`" class="text-sm font-semibold text-text-main hover:text-primary transition-colors truncate block">
                                    {{ complaint.title }}
                                </RouterLink>
                                <p class="text-xs text-text-secondary">#{{ complaint.reference }} · {{ complaint.user?.name }}</p>
                            </div>
                        </div>
                        <Avatar :name="complaint.user?.name" size="sm" class="shrink-0" />
                    </div>
                </div>
                <div v-else class="py-10 text-center">
                    <EmptyState title="No recent complaints" description="There are no complaints in the system yet." iconColor="text-text-muted" />
                </div>
            </Card>
        </div>
    </AppShell>
</template>

<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import axios from 'axios';
import { RouterLink } from 'vue-router';
import { useToastStore } from '../../stores/toasts';
import { extractMessage } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import StatCard from '../../components/StatCard.vue';
import Icon from '../../components/Icon.vue';
import Select from '../../components/Select.vue';
import TrendChart from '../../components/TrendChart.vue';
import Badge from '../../components/Badge.vue';
import EmptyState from '../../components/EmptyState.vue';
import Avatar from '../../components/Avatar.vue';

const toast = useToastStore();
const stats = ref({ total: 0, pending: 0, in_progress: 0, resolved: 0, rejected: 0 });
const trend = ref([]);
const recent = ref([]);
const selectedPeriod = ref('month');

const statusDistribution = computed(() => {
    const total = stats.value.total || 1;
    const order = ['pending', 'in_progress', 'resolved', 'rejected'];
    return order.map((status) => {
        const count = stats.value[status] ?? 0;
        return {
            status,
            label: status.charAt(0).toUpperCase() + status.slice(1).replace('_', ' '),
            count,
            percent: Math.round((count / total) * 100),
        };
    });
});

function statusColor(status) {
    const map = {
        pending: 'bg-warning',
        in_progress: 'bg-secondary',
        resolved: 'bg-success',
        rejected: 'bg-error',
    };
    return map[status] || 'bg-text-muted';
}

async function loadDashboard() {
    try {
        const { data } = await axios.get('/api/admin/dashboard', {
            params: { period: selectedPeriod.value },
        });
        stats.value = data.stats;
        trend.value = data.trend || [];
        recent.value = data.recent_complaints || [];
        selectedPeriod.value = data.selected_period || selectedPeriod.value;
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load dashboard.'));
    }
}

watch(selectedPeriod, loadDashboard);
onMounted(loadDashboard);
</script>
