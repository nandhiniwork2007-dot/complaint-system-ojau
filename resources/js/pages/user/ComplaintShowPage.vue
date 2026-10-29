<template>
    <AppShell
        section="user"
        :title="complaint?.title || 'Complaint details'"
        :subtitle="`Reference #${complaint?.reference}`"
    >
        <div v-if="!complaint" class="py-16 text-center">
            <EmptyState title="Loading complaint…" description="Please wait while we fetch the details." iconColor="text-text-muted" />
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-[1.55fr_1fr]">
            <!-- Main complaint content -->
            <section class="grid gap-6">
                <Card padded bordered>
                    <div class="flex items-center justify-between gap-3 mb-4">
                        <div>
                            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Reference</p>
                            <p class="text-sm font-mono font-semibold text-text-main">#{{ complaint.reference }}</p>
                        </div>
                        <Badge :variant="complaint.status" class="text-xs">
                            {{ complaint.status.replace('_', ' ') }}
                        </Badge>
                    </div>

                    <h2 class="text-xl font-bold text-text-main mb-2">{{ complaint.title }}</h2>
                    <p class="whitespace-pre-line text-text-secondary leading-relaxed mb-6">{{ complaint.description }}</p>

                    <div class="grid gap-3 sm:grid-cols-2 text-sm border-t border-surface-border pt-4">
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Category</span>
                            <span class="text-text-main">{{ complaint.category?.name || 'Uncategorized' }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Priority</span>
                            <span class="text-text-main">{{ complaint.priority.charAt(0).toUpperCase() + complaint.priority.slice(1) }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Submitted</span>
                            <span class="text-text-main">{{ formatDate(complaint.submitted_at) }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Assigned to</span>
                            <span class="text-text-main">{{ complaint.assignee?.name || 'Unassigned' }}</span>
                        </div>
                        <div v-if="complaint.resolved_at" class="flex flex-col gap-1">
                            <span class="text-text-muted">Resolved</span>
                            <span class="text-text-success">{{ formatDate(complaint.resolved_at) }}</span>
                        </div>
                        <div v-if="complaint.withdrawn_at" class="flex flex-col gap-1">
                            <span class="text-text-muted">Withdrawn</span>
                            <span class="text-text-error">{{ formatDate(complaint.withdrawn_at) }}</span>
                        </div>
                    </div>

                    <div v-if="complaint.attachments.length" class="mt-6 border-t border-surface-border pt-4">
                        <h4 class="text-sm font-semibold text-text-main mb-3">Attachments</h4>
                        <div class="grid gap-2">
                            <a
                                v-for="attachment in complaint.attachments"
                                :key="attachment.id"
                                :href="attachment.download_url"
                                class="flex items-center gap-2 rounded-lg border border-surface-border bg-surface px-3 py-2 text-xs text-text-secondary hover:bg-surface-elevated hover:text-text-main transition-colors"
                                download
                            >
                                <Icon name="PaperclipIcon" class="h-3.5 w-3.5" />
                                <span class="truncate">{{ attachment.name }}</span>
                                <Icon name="DownloadIcon" class="h-3.5 w-3.5 shrink-0 opacity-50" />
                            </a>
                        </div>
                    </div>

                    <div v-if="complaint.messages.length" class="mt-6 border-t border-surface-border pt-4">
                        <h4 class="text-sm font-semibold text-text-main mb-3">Responses</h4>
                        <div class="space-y-3">
                            <div
                                v-for="message in complaint.messages"
                                :key="message.id"
                                class="p-3 rounded-lg border border-surface-border bg-surface/50"
                            >
                                <div class="flex items-center gap-2 mb-1">
                                    <Avatar :name="message.user?.name" size="xs" class="shrink-0" />
                                    <span class="text-xs font-semibold text-text-main">{{ message.user?.name }}</span>
                                    <span class="text-xs text-text-muted">{{ formatDateTime(message.created_at) }}</span>
                                </div>
                                <p class="text-sm text-text-secondary whitespace-pre-line">{{ message.message }}</p>
                            </div>
                        </div>
                    </div>

                    <div v-if="complaint.can_edit" class="mt-6 border-t border-surface-border pt-6">
                        <h4 class="text-sm font-semibold text-text-main mb-3">Edit complaint</h4>
                        <p class="text-xs text-text-secondary mb-3">You can only edit this complaint while it is pending and before an admin has acted on it.</p>
                        <ComplaintForm
                            :complaint="complaint"
                            :categories="categories"
                            :errors="editErrors"
                            :submitting="editSubmitting"
                            submit-label="Save changes"
                            @submit="updateComplaint"
                        />
                    </div>
                </Card>
            </section>

            <!-- Sidebar: actions + timeline -->
            <section class="grid gap-6">
                <Card padded bordered class="sticky top-6">
                    <h3 class="text-sm font-semibold text-text-main mb-4">Actions</h3>
                    <div class="space-y-2">
                        <button
                            v-if="complaint.can_edit"
                            @click="editing = true"
                            class="w-full flex items-center justify-center gap-2 rounded-lg border border-surface-border bg-surface px-4 py-3 text-sm font-medium text-text-main hover:bg-surface-elevated transition-colors"
                        >
                            <Icon name="EditIcon" class="h-4 w-4" />
                            <span>Edit complaint</span>
                        </button>
                        <button
                            v-if="complaint.can_withdraw"
                            @click="confirmWithdraw = true"
                            class="w-full flex items-center justify-center gap-2 rounded-lg border border-error/30 bg-error/5 px-4 py-3 text-sm font-medium text-error hover:bg-error/15 transition-colors"
                        >
                            <Icon name="Trash2Icon" class="h-4 w-4" />
                            <span>Withdraw complaint</span>
                        </button>
                    </div>
                </Card>

                <Card padded bordered>
                    <h3 class="text-sm font-semibold text-text-main mb-4">Timeline</h3>
                    <div class="space-y-5 relative">
                        <div class="absolute left-3 top-3 bottom-3 w-0.5 bg-surface-border" />

                        <div class="relative pl-10">
                            <div class="absolute left-0 top-1.5 w-2.5 h-2.5 rounded-full bg-success ring-4 ring-bg" />
                            <p class="text-sm font-semibold text-text-main">Submitted</p>
                            <p class="text-xs text-text-secondary">{{ formatDateTime(complaint.submitted_at) }}</p>
                        </div>

                        <div
                            v-for="(message, index) in complaint.messages"
                            :key="message.id"
                            class="relative pl-10"
                        >
                            <div class="absolute left-0 top-2 w-2.5 h-2.5 rounded-full bg-secondary ring-4 ring-bg" />
                            <p class="text-sm font-semibold text-text-main">Response from {{ message.user?.name }}</p>
                            <p class="text-xs text-text-secondary">{{ formatDateTime(message.created_at) }}</p>
                        </div>

                        <div
                            v-if="complaint.resolved_at"
                            class="relative pl-10"
                        >
                            <div class="absolute left-0 top-1.5 w-2.5 h-2.5 rounded-full bg-success ring-4 ring-bg" />
                            <p class="text-sm font-semibold text-text-main">Resolved</p>
                            <p class="text-xs text-text-success">{{ formatDateTime(complaint.resolved_at) }}</p>
                        </div>

                        <div
                            v-if="complaint.withdrawn_at"
                            class="relative pl-10"
                        >
                            <div class="absolute left-0 top-1.5 w-2.5 h-2.5 rounded-full bg-error ring-4 ring-bg" />
                            <p class="text-sm font-semibold text-text-main">Withdrawn</p>
                            <p class="text-xs text-text-error">{{ formatDateTime(complaint.withdrawn_at) }}</p>
                        </div>
                    </div>
                </Card>
            </section>
        </div>

        <!-- Withdraw confirm -->
        <Modal v-model:open="confirmWithdraw" title="Withdraw complaint">
            <p class="text-sm text-text-secondary mb-6">Are you sure you want to withdraw this complaint? This action cannot be undone.</p>
            <template #footer>
                <Button variant="outline" @click="confirmWithdraw = false">Cancel</Button>
                <Button variant="danger" @click="proceedWithdraw" :loading="withdrawing">Withdraw</Button>
            </template>
        </Modal>
    </AppShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import EmptyState from '../../components/EmptyState.vue';
import Badge from '../../components/Badge.vue';
import Avatar from '../../components/Avatar.vue';
import Icon from '../../components/Icon.vue';
import Modal from '../../components/Modal.vue';
import Button from '../../components/Button.vue';
import ComplaintForm from '../../components/ComplaintForm.vue';

const route = useRoute();
const router = useRouter();
const toast = useToastStore();
const complaint = ref(null);
const categories = ref([]);
const editErrors = ref({});
const editSubmitting = ref(false);
const editing = ref(false);
const confirmWithdraw = ref(false);
const withdrawing = ref(false);

async function load() {
    try {
        const [{ data: complaintData }, { data: categoryData }] = await Promise.all([
            axios.get(`/api/complaints/${route.params.id}`),
            axios.get('/api/categories'),
        ]);
        complaint.value = complaintData.data;
        categories.value = normalizeItems(categoryData);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load complaint details.'));
        router.push('/complaints');
    }
}

async function updateComplaint(payload) {
    editSubmitting.value = true;
    editErrors.value = {};

    const formData = new FormData();
    formData.append('_method', 'PUT');
    formData.append('title', payload.title);
    formData.append('complaint_category_id', payload.complaint_category_id);
    formData.append('description', payload.description);
    payload.attachments.forEach((file) => formData.append('attachments[]', file));

    try {
        const { data } = await axios.post(`/api/complaints/${route.params.id}`, formData);
        complaint.value = data.data;
        editing = false;
        toast.success(data.message || 'Complaint updated successfully.');
    } catch (error) {
        editErrors.value = error.response?.data?.errors ?? {};
        toast.error(extractMessage(error, 'Unable to update complaint.'));
    } finally {
        editSubmitting = false;
    }
}

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatDateTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

async function proceedWithdraw() {
    withdrawing.value = true;
    try {
        await axios.delete(`/api/complaints/${route.params.id}`);
        toast.success('Complaint withdrawn successfully.');
        router.push('/complaints');
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to withdraw complaint.'));
    } finally {
        confirmWithdraw.value = false;
        withdrawing.value = false;
    }
}

onMounted(load);
</script>
