<template>
    <AppShell section="user" title="My complaints" subtitle="Search, filter, and review every complaint you've submitted.">
        <template #actions>
            <Button variant="primary" @click="router.push('/complaints/new')">
                <Icon name="PlusIcon" class="h-4 w-4" />
                <span>Submit complaint</span>
            </Button>
        </template>

        <Card padded bordered class="mb-6">
            <div class="grid grid-cols-1 gap-3 sm:gap-4 md:grid-cols-2 2xl:grid-cols-12">
                <Input
                    v-model="filters.search"
                    label="Search"
                    placeholder="Title or reference"
                    class="md:col-span-2 2xl:col-span-3"
                />
                <Select
                    v-model="filters.status"
                    label="Status"
                    placeholder="All statuses"
                    class="2xl:col-span-2"
                >
                    <option value="">All statuses</option>
                    <option value="pending">Pending</option>
                    <option value="in_progress">In Progress</option>
                    <option value="resolved">Resolved</option>
                    <option value="rejected">Rejected</option>
                </Select>
                <Select
                    v-model="filters.category_id"
                    label="Category"
                    placeholder="All categories"
                    class="2xl:col-span-2"
                >
                    <option value="">All categories</option>
                    <option v-for="category in categories" :key="category.id" :value="category.id">
                        {{ category.name }}
                    </option>
                </Select>
                <div class="grid grid-cols-2 gap-3 md:col-span-2 2xl:col-span-3">
                    <Input v-model="filters.date_from" label="From" type="date" class="min-w-0" />
                    <Input v-model="filters.date_to" label="To" type="date" class="min-w-0" />
                </div>
                <div class="flex items-end gap-2 md:col-span-2 2xl:col-span-2">
                    <Button variant="outline" @click="applyFilters" class="min-w-0 flex-1">Apply</Button>
                    <Button variant="ghost" @click="resetFilters" class="min-w-0 flex-1">Reset</Button>
                </div>
            </div>
        </Card>

        <div v-if="complaints.data?.length" class="mb-6">
            <Table :columns="tableColumns" :rows="complaints.data" :rowKey="row => row.id">
                <template #header-reference>Ref.</template>
                <template #header-title>Title</template>
                <template #header-category>Category</template>
                <template #header-status>Status</template>
                <template #header-submitted_at>Submitted</template>

                <template #cell-reference="{ row }">
                    <span class="text-xs font-semibold text-text-muted">{{ row.reference }}</span>
                </template>
                <template #cell-title="{ row }">
                    <RouterLink :to="`/complaints/${row.id}`" class="text-sm font-medium text-text-main hover:text-primary transition-colors">
                        {{ row.title }}
                    </RouterLink>
                </template>
                <template #cell-category="{ row }">
                    <span class="text-xs text-text-secondary">{{ row.category?.name || '—' }}</span>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="row.status">{{ row.status.replace('_', ' ') }}</Badge>
                </template>
                <template #cell-submitted_at="{ row }">
                    <span class="text-xs text-text-secondary">{{ formatDate(row.submitted_at) }}</span>
                </template>

                <template #actions="{ row }">
                    <RouterLink :to="`/complaints/${row.id}`" class="text-sm font-medium text-primary hover:text-primary-dark transition-colors">View →</RouterLink>
                </template>
            </Table>

            <Pagination
                :current-page="complaints.current_page"
                :per-page="complaints.per_page"
                :total="complaints.total"
                @update:current-page="changePage"
            />
        </div>

        <div v-else class="py-16 text-center">
            <EmptyState
                title="No complaints found"
                :description="hasFilters ? 'Try adjusting your filters to find what you are looking for.' : 'You have not submitted any complaints yet.'"
                iconColor="text-text-muted"
            >
                <template v-if="!hasFilters" #action>
                    <Button variant="primary" @click="router.push('/complaints/new')">
                        Submit your first complaint
                    </Button>
                </template>
            </EmptyState>
        </div>
    </AppShell>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Input from '../../components/Input.vue';
import Select from '../../components/Select.vue';
import Button from '../../components/Button.vue';
import Icon from '../../components/Icon.vue';
import { RouterLink } from 'vue-router';
import Table from '../../components/Table.vue';
import Pagination from '../../components/Pagination.vue';
import EmptyState from '../../components/EmptyState.vue';
import Badge from '../../components/Badge.vue';

const router = useRouter();
const toast = useToastStore();
const categories = ref([]);
const complaints = ref({ data: [], current_page: 1, per_page: 10, total: 0 });
const initialFilters = { search: '', status: '', category_id: '', date_from: '', date_to: '', page: 1 };
const filters = reactive({ ...initialFilters });

const tableColumns = ['reference', 'title', 'category', 'status', 'submitted_at'];

const hasFilters = computed(() => {
    return Object.entries(filters).some(([key, value]) => key !== 'page' && value !== '');
});

async function loadCategories() {
    try {
        const { data } = await axios.get('/api/categories');
        categories.value = normalizeItems(data);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load categories.'));
    }
}

async function loadComplaints() {
    try {
        const { data } = await axios.get('/api/complaints', { params: { ...filters, page: filters.page } });
        complaints.value = {
            data: normalizeItems(data),
            current_page: data.current_page ?? 1,
            per_page: data.per_page ?? 10,
            total: data.total ?? 0,
        };
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load complaints.'));
    }
}

function resetFilters() {
    Object.assign(filters, initialFilters);
    loadComplaints();
}

function applyFilters() {
    filters.page = 1;
    loadComplaints();
}

function changePage(page) {
    filters.page = page;
    loadComplaints();
}

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

onMounted(async () => {
    try {
        await Promise.all([loadCategories(), loadComplaints()]);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load complaint filters.'));
    }
});

</script>
