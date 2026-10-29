<template>
    <AppShell
        section="admin"
        :title="complaint?.title || 'Complaint review'"
        :subtitle="`Case #${complaint?.reference} · ${complaint?.user?.name}`"
    >
        <div v-if="!complaint" class="py-16 text-center">
            <EmptyState title="Loading complaint…" description="Please wait while we fetch the case details." iconColor="text-text-muted" />
        </div>

        <div v-else class="grid gap-6 lg:grid-cols-[1.35fr_1fr]">
            <!-- Case details -->
            <section class="grid gap-6">
                <Card padded bordered>
                    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                        <div>
                            <p class="text-xs font-semibold text-text-muted uppercase tracking-wider">Case reference</p>
                            <p class="text-sm font-mono font-semibold text-text-main">#{{ complaint.reference }}</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <Badge :variant="complaint.status" class="text-xs font-bold">
                                {{ complaint.status.replace('_', ' ') }}
                            </Badge>
                            <Badge :variant="priorityKey(complaint.priority)" class="text-xs">
                                {{ complaint.priority.charAt(0).toUpperCase() + complaint.priority.slice(1) }} priority
                            </Badge>
                        </div>
                    </div>

                    <h2 class="text-xl font-bold text-text-main mb-2">{{ complaint.title }}</h2>
                    <p class="whitespace-pre-line text-text-secondary leading-relaxed mb-6">{{ complaint.description }}</p>

                    <div class="grid gap-3 sm:grid-cols-3 border-t border-surface-border pt-5">
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Submitted by</span>
                            <div class="flex items-center gap-2">
                                <Avatar :name="complaint.user?.name" size="xs" class="shrink-0" />
                                <span class="text-sm text-text-main">{{ complaint.user?.name }}</span>
                            </div>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Category</span>
                            <span class="text-sm text-text-main">{{ complaint.category?.name || 'Uncategorized' }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Assigned to</span>
                            <span class="text-sm text-text-main">{{ complaint.assignee?.name || 'Unassigned' }}</span>
                        </div>
                        <div class="flex flex-col gap-1">
                            <span class="text-text-muted">Submitted</span>
                            <span class="text-sm text-text-main">{{ formatDateTime(complaint.submitted_at) }}</span>
                        </div>
                        <div v-if="complaint.first_admin_action_at" class="flex flex-col gap-1">
                            <span class="text-text-muted">First response</span>
                            <span class="text-sm text-text-main">{{ formatDateTime(complaint.first_admin_action_at) }}</span>
                        </div>
                        <div v-if="complaint.resolved_at" class="flex flex-col gap-1">
                            <span class="text-text-muted">Resolved</span>
                            <span class="text-sm text-text-success">{{ formatDateTime(complaint.resolved_at) }}</span>
                        </div>
                    </div>

                    <div v-if="complaint.attachments.length" class="mt-6 border-t border-surface-border pt-5">
                        <h4 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-3">Attachments</h4>
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
                </Card>
            </section>

            <!-- Case management -->
            <section class="grid gap-6">
                <!-- Update form -->
                <Card padded bordered class="sticky top-6">
                    <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-4">Update case</h3>
                    <form class="grid gap-4" @submit.prevent="saveCase">
                        <div class="field">
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Status</label>
                            <Select v-model="form.status" name="status">
                                <option value="pending">Pending</option>
                                <option value="in_progress">In Progress</option>
                                <option value="resolved">Resolved</option>
                                <option value="rejected">Rejected</option>
                            </Select>
                        </div>

                        <div class="field">
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Assign to</label>
                            <Select v-model="form.assigned_to" name="assigned_to">
                                <option value="">Unassigned</option>
                                <option v-for="member in staff" :key="member.id" :value="member.id">
                                    {{ member.name }} ({{ member.role }})
                                </option>
                            </Select>
                        </div>

                        <div class="field">
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Public response</label>
                            <p class="text-xs text-text-secondary mb-1">Visible to the customer.</p>
                            <Textarea v-model="form.public_message" name="public_message" rows="3" placeholder="Type a response to the customer..." />
                        </div>

                        <div class="field">
                            <label class="block text-xs font-medium text-text-secondary mb-1.5">Internal note</label>
                            <p class="text-xs text-text-secondary mb-1">Staff-only note, not shared with the customer.</p>
                            <Textarea v-model="form.internal_note" name="internal_note" rows="3" placeholder="Internal notes for the support team..." />
                        </div>

                        <div class="flex items-center justify-between pt-2">
                            <Badge variant="default" class="text-[10px]">{{ saving ? 'Saving...' : 'Ready' }}</Badge>
                            <Button
                                type="submit"
                                variant="primary"
                                :loading="saving"
                                class="w-auto"
                            >
                                Save changes
                            </Button>
                        </div>
                    </form>
                </Card>

                <!-- Messages -->
                <Card padded bordered>
                    <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-4">
                        Conversation ({{ complaint.messages.length }})
                    </h3>
                    <div v-if="complaint.messages.length" class="space-y-4 max-h-[420px] overflow-y-auto pr-2">
                        <div
                            v-for="message in complaint.messages"
                            :key="message.id"
                            class="p-3 rounded-lg border border-surface-border"
                            :class="{ 'bg-surface-elevated/50': message.is_internal }"
                        >
                            <div class="flex items-center gap-2 mb-1">
                                <Avatar :name="message.user?.name" size="xs" class="shrink-0" />
                                <span class="text-xs font-semibold text-text-main">{{ message.user?.name }}</span>
                                <span class="text-xs text-text-muted">{{ message.is_internal ? 'Internal' : 'Public' }}</span>
                                <span class="text-xs text-text-muted ml-auto">{{ formatDateTime(message.created_at) }}</span>
                            </div>
                            <p class="text-sm text-text-secondary whitespace-pre-line">{{ message.message }}</p>
                        </div>
                    </div>
                    <div v-else class="py-4 text-center text-xs text-text-secondary">No messages yet.</div>
                </Card>

                <!-- Audit log -->
                <Card padded bordered class="max-h-[300px] overflow-hidden flex flex-col">
                    <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-4">Audit log</h3>
                    <div class="flex-1 overflow-y-auto pr-2 space-y-3 text-xs">
                        <div
                            v-for="log in complaint.audit_logs"
                            :key="log.id"
                            class="p-2 rounded-lg border border-surface-border bg-surface/50"
                        >
                            <p class="text-xs font-semibold text-text-main">{{ log.action }}</p>
                            <p class="text-[10px] text-text-secondary leading-relaxed">{{ log.description }}</p>
                            <p class="text-[10px] text-text-muted mt-1">{{ log.user }} · {{ formatDateTime(log.created_at) }}</p>
                        </div>
                        <div v-if="!complaint.audit_logs?.length" class="text-[10px] text-text-secondary text-center py-2">No activity recorded.</div>
                    </div>
                </Card>
            </section>
        </div>
    </AppShell>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import axios from 'axios';
import { useRoute } from 'vue-router';
import { useToastStore } from '../../stores/toasts';
import { extractMessage } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Select from '../../components/Select.vue';
import Textarea from '../../components/Textarea.vue';
import Button from '../../components/Button.vue';
import Badge from '../../components/Badge.vue';
import Icon from '../../components/Icon.vue';
import Avatar from '../../components/Avatar.vue';
import EmptyState from '../../components/EmptyState.vue';

const route = useRoute();
const toast = useToastStore();
const complaint = ref(null);
const staff = ref([]);
const form = ref({ status: 'pending', assigned_to: '', public_message: '', internal_note: '' });
const saving = ref(false);

async function load() {
    try {
        const { data } = await axios.get(`/api/admin/complaints/${route.params.id}`);
        complaint.value = data.data;
        staff.value = data.staff || [];
        form.value.status = complaint.value.status;
        form.value.assigned_to = complaint.value.assignee?.id || '';
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load complaint details.'));
    }
}

async function saveCase() {
    saving.value = true;
    try {
        const { data } = await axios.patch(`/api/admin/complaints/${route.params.id}`, form.value);
        complaint.value = data.data;
        form.value.public_message = '';
        form.value.internal_note = '';
        toast.success(data.message || 'Complaint updated successfully.');
        if (data.warning) {
            const message = data.mail_error ? `${data.warning} ${data.mail_error}` : data.warning;
            toast.error(message);
        }
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to update complaint.'));
    } finally {
        saving.value = false;
    }
}

function priorityKey(priority) {
    const map = { low: 'low', normal: 'normal', high: 'high', urgent: 'urgent' };
    return map[priority] || 'normal';
}

function formatDateTime(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleString(undefined, { month: 'short', day: 'numeric', hour: '2-digit', minute: '2-digit' });
}

onMounted(load);
</script>
