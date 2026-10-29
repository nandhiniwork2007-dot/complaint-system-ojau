<template>
    <AppShell section="admin" title="User management" subtitle="Manage accounts, roles, and status across the portal.">
        <template #actions>
            <Input v-model="filters.search" label="Search" placeholder="Name, email, or phone" class="w-64 shrink-0" />
        </template>

        <Card padded bordered class="mb-6">
            <div class="grid gap-3 sm:grid-cols-4 lg:grid-cols-5">
                <Select v-model="filters.role" label="Role" class="col-span-full">
                    <option value="">All roles</option>
                    <option value="admin">Admin</option>
                    <option value="manager">Manager</option>
                    <option value="support_agent">Support Agent</option>
                    <option value="user">User</option>
                </Select>
                <Select v-model="filters.is_active" label="Status" class="col-span-full">
                    <option value="">All statuses</option>
                    <option value="true">Active</option>
                    <option value="false">Inactive</option>
                </Select>
                <div class="flex items-end gap-2 col-span-full lg:col-span-1">
                    <Button variant="outline" @click="loadUsers" class="w-full">Apply</Button>
                    <Button variant="ghost" @click="resetFilters" class="w-full">Reset</Button>
                </div>
            </div>
        </Card>

        <div v-if="users.data?.length" class="mb-6">
            <Table :columns="tableColumns" :rows="users.data" :rowKey="row => row.id">
                <template #header-name>Name</template>
                <template #header-email>Email</template>
                <template #header-phone>Phone</template>
                <template #header-role>Role</template>
                <template #header-status>Status</template>
                <template #header-verified>Verified</template>
                <template #header-actions>Actions</template>

                <template #cell-name="{ row }">
                    <div class="flex items-center gap-2">
                        <Avatar :name="row.name" size="xs" class="shrink-0" />
                        <span class="text-sm font-medium text-text-main">{{ row.name }}</span>
                    </div>
                </template>
                <template #cell-email="{ row }">
                    <span class="text-sm text-text-main truncate max-w-xs">{{ row.email }}</span>
                </template>
                <template #cell-phone="{ row }">
                    <span class="text-xs text-text-secondary">{{ row.phone || '—' }}</span>
                </template>
                <template #cell-role="{ row }">
                    <select
                        v-model="row.editRole"
                        class="block w-full rounded-lg border border-surface-border bg-surface px-2.5 py-1.5 text-xs text-text-main focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                    >
                        <option value="admin">Admin</option>
                        <option value="manager">Manager</option>
                        <option value="support_agent">Support Agent</option>
                        <option value="user">User</option>
                    </select>
                </template>
                <template #cell-status="{ row }">
                    <Badge :variant="row.is_active ? 'success' : 'default'" class="text-xs">
                        {{ row.is_active ? 'Active' : 'Inactive' }}
                    </Badge>
                </template>
                <template #cell-verified="{ row }">
                    <span class="text-xs">{{ row.email_verified_at ? 'Yes' : 'No' }}</span>
                </template>

                <template #actions="{ row }">
                    <div class="flex items-center gap-2">
                        <Button
                            variant="outline"
                            size="sm"
                            :loading="savingIds.has(row.id)"
                            @click="saveUser(row)"
                            class="w-auto"
                            :disabled="savingIds.has(row.id)"
                        >
                            Save
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            :loading="resettingIds.has(row.id)"
                            @click="resetPassword(row)"
                            class="w-auto text-error border-error hover:bg-error/10 hover:text-error"
                            :disabled="resettingIds.has(row.id)"
                        >
                            Reset password
                        </Button>
                    </div>
                </template>
            </Table>

            <Pagination
                :current-page="users.current_page"
                :per-page="users.per_page"
                :total="users.total"
                @update:current-page="changePage"
            />
        </div>

        <div v-else class="py-16 text-center">
            <EmptyState title="No users found" description="Try adjusting your filters or search terms." iconColor="text-text-muted" />
        </div>
    </AppShell>
</template>

<script setup>
import { onMounted, reactive, ref, computed } from 'vue';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Select from '../../components/Select.vue';
import Input from '../../components/Input.vue';
import Button from '../../components/Button.vue';
import Table from '../../components/Table.vue';
import Pagination from '../../components/Pagination.vue';
import EmptyState from '../../components/EmptyState.vue';
import Avatar from '../../components/Avatar.vue';
import Badge from '../../components/Badge.vue';

const toast = useToastStore();
const users = ref({ data: [], current_page: 1, per_page: 12, total: 0 });
const initialFilters = { search: '', role: '', is_active: '', page: 1 };
const filters = reactive({ ...initialFilters });
const savingIds = ref(new Set());
const resettingIds = ref(new Set());

const tableColumns = ['name', 'email', 'phone', 'role', 'status', 'verified', 'actions'];

async function loadUsers() {
    try {
        const { data } = await axios.get('/api/admin/users', { params: { ...filters, page: filters.page } });
        users.value = {
            data: normalizeItems(data).map((u) => ({ ...u, editRole: u.role })),
            current_page: data.current_page ?? 1,
            per_page: data.per_page ?? 12,
            total: data.total ?? 0,
        };
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load users.'));
    }
}

function resetFilters() {
    Object.assign(filters, initialFilters);
    loadUsers();
}

function changePage(page) {
    filters.page = page;
    loadUsers();
}

async function saveUser(user) {
    savingIds.value.add(user.id);
    try {
        await axios.patch(`/api/admin/users/${user.id}`, {
            role: user.editRole,
            is_active: user.is_active,
        });
        toast.success('User updated successfully.');
        await loadUsers();
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to update user.'));
        await loadUsers();
    } finally {
        savingIds.value.delete(user.id);
    }
}

async function resetPassword(user) {
    resettingIds.value.add(user.id);
    try {
        const { data } = await axios.post(`/api/admin/users/${user.id}/reset-password`);
        const message = data.mail_error ? `${data.message} ${data.mail_error}` : data.message;
        toast.success(message || 'Password reset link processed.');
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to send password reset link.'));
    } finally {
        resettingIds.value.delete(user.id);
    }
}

onMounted(loadUsers);
</script>
