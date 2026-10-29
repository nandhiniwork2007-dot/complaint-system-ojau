<template>
    <AppShell section="user" title="Dashboard" subtitle="Your personalized overview.">
        <!-- Welcome card -->
        <div class="mb-6">
            <div class="relative overflow-hidden rounded-2xl border border-surface-border bg-linear-to-br from-primary/10 via-surface-elevated to-surface p-6 lg:p-8">
                <div class="absolute top-0 right-0 h-64 w-64 -translate-y-1/2 translate-x-1/3 rounded-full bg-primary/20 blur-3xl"></div>
                <div class="relative flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                    <div>
                        <p class="text-sm text-text-secondary">Good {{ greeting }},</p>
                        <h2 class="text-2xl font-bold text-text-main mt-1">{{ userName }}</h2>
                        <p class="text-sm text-text-secondary mt-2">
                            You have <span class="font-semibold text-text-main">{{ stats.total }}</span> complaint{{ stats.total === 1 ? '' : 's' }} on record.
                        </p>
                    </div>
                    <Button variant="primary" @click="router.push('/complaints/new')" class="shrink-0">
                        <Icon name="PlusIcon" class="h-4 w-4" />
                        <span>Submit a new complaint</span>
                    </Button>
                </div>
            </div>
        </div>

        <!-- Stats row -->
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

        <div class="grid gap-6 lg:grid-cols-[1fr_280px]">
            <!-- Recent complaints -->
            <Card padded bordered>
                <div class="flex items-center justify-between mb-5">
                    <h3 class="text-sm font-semibold text-text-main">Recent activity</h3>
                    <RouterLink to="/complaints" class="text-xs font-medium text-primary hover:text-primary-dark transition-colors">View all →</RouterLink>
                </div>

                <div v-if="complaints.length" class="space-y-3">
                    <RouterLink
                        v-for="complaint in complaints"
                        :key="complaint.id"
                        :to="`/complaints/${complaint.id}`"
                        class="flex items-center justify-between gap-3 p-4 rounded-xl border border-surface-border bg-surface hover:border-primary/30 hover:bg-surface-elevated/50 transition-all group"
                    >
                        <div class="flex items-center gap-3">
                            <Badge :variant="complaint.status" class="shrink-0">{{ complaint.status.replace('_', ' ') }}</Badge>
                            <div>
                                <p class="text-sm font-semibold text-text-main group-hover:text-primary transition-colors truncate max-w-[280px]">{{ complaint.title }}</p>
                                <p class="text-xs text-text-secondary">#{{ complaint.reference }} · {{ complaint.category?.name }}</p>
                            </div>
                        </div>
                        <time class="text-xs text-text-muted shrink-0">{{ formatDate(complaint.submitted_at) }}</time>
                    </RouterLink>
                </div>

                <div v-else class="py-12 text-center">
                    <EmptyState
                        title="No complaints yet"
                        description="You haven't submitted any complaints. Get started by creating your first one."
                        iconColor="text-text-muted"
                    >
                        <template #action>
                            <Button variant="primary" @click="router.push('/complaints/new')">
                                Submit your first complaint
                            </Button>
                        </template>
                    </EmptyState>
                </div>
            </Card>

            <!-- Quick actions -->
            <Card padded bordered>
                <h3 class="text-sm font-semibold text-text-main mb-4">Quick actions</h3>
                <div class="space-y-2">
                    <Button
                        variant="ghost"
                        class="w-full justify-start text-left h-11"
                        @click="router.push('/complaints/new')"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary/10 text-primary mr-3">
                            <Icon name="FilePlusIcon" class="h-4 w-4" />
                        </div>
                        <span>Submit complaint</span>
                    </Button>
                    <Button
                        variant="ghost"
                        class="w-full justify-start text-left h-11"
                        @click="router.push('/complaints')"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-secondary/10 text-secondary mr-3">
                            <Icon name="FileTextIcon" class="h-4 w-4" />
                        </div>
                        <span>View all complaints</span>
                    </Button>
                    <Button
                        variant="ghost"
                        class="w-full justify-start text-left h-11"
                        @click="router.push('/profile')"
                    >
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-success/10 text-success mr-3">
                            <Icon name="UserIcon" class="h-4 w-4" />
                        </div>
                        <span>Update profile</span>
                    </Button>
                </div>
            </Card>
        </div>
    </AppShell>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import { useAuthStore } from '../../stores/auth';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Button from '../../components/Button.vue';
import Icon from '../../components/Icon.vue';
import Badge from '../../components/Badge.vue';
import StatCard from '../../components/StatCard.vue';
import EmptyState from '../../components/EmptyState.vue';

const auth = useAuthStore();
const router = useRouter();
const toast = useToastStore();
const stats = ref({ total: 0, pending: 0, in_progress: 0, resolved: 0, rejected: 0 });
const complaints = ref([]);

const userName = computed(() => {
    return auth.user?.name || 'there';
});

const greeting = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Morning';
    if (hour < 18) return 'Afternoon';
    return 'Evening';
});

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
}

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/dashboard');
        stats.value = data.stats;
        complaints.value = normalizeItems(data.recent_complaints);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load dashboard.'));
    }
});
</script>
