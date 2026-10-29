<template>
    <AppShell section="admin" title="Categories" subtitle="Manage the complaint taxonomy used by the submission form.">
        <div class="grid gap-6 lg:grid-cols-[1fr_1.5fr]">
            <!-- Create category -->
            <Card padded bordered>
                <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider mb-4">Create category</h3>
                <form class="grid gap-4" @submit.prevent="createCategory">
                    <Input v-model="form.name" label="Name" name="name" placeholder="e.g. Billing" required />
                    <Input v-model="form.type" label="Type" name="type" placeholder="e.g. Payments" required />
                    <Textarea v-model="form.description" label="Description" name="description" rows="3" placeholder="Brief description of this category..." />
                    <div class="flex items-end gap-3 pt-2">
                        <Button variant="outline" type="button" @click="resetForm" class="w-auto">Reset</Button>
                        <Button type="submit" variant="primary" :loading="creating" class="w-auto">Create category</Button>
                    </div>
                </form>
            </Card>

            <!-- Existing categories -->
            <Card padded bordered>
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-xs font-semibold text-text-muted uppercase tracking-wider">Existing categories</h3>
                    <span class="text-xs text-text-secondary">{{ categories.length }}</span>
                </div>

                <div v-if="categories.length" class="space-y-3">
                    <div
                        v-for="category in categories"
                        :key="category.id"
                        class="flex items-center justify-between gap-3 p-3 rounded-xl border border-surface-border bg-surface hover:bg-surface-elevated/50 transition-colors"
                    >
                        <div class="flex items-center gap-3">
                            <div class="flex h-8 w-8 items-center justify-center rounded-xl bg-surface-elevated text-text-secondary">
                                <Icon name="TagIcon" class="h-4 w-4" />
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-text-main">{{ category.name }}</p>
                                <p class="text-xs text-text-secondary">{{ category.description || 'No description' }}</p>
                                <p class="text-[10px] text-text-muted mt-0.5 uppercase tracking-wider">{{ category.complaints_count }} complaint{{ category.complaints_count === 1 ? '' : 's' }}</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button variant="outline" size="sm" @click="editCategory(category)" class="w-auto">Edit</Button>
                            <button
                                v-if="(category.complaints_count || 0) > 0"
                                class="h-8 w-8 rounded-lg text-text-muted hover:text-error transition-colors"
                                title="Cannot delete - category in use"
                            >
                                <Icon name="Trash2Icon" class="h-4 w-4" />
                            </button>
                            <button
                                v-else
                                @click="confirmDelete(category.id)"
                                class="h-8 w-8 rounded-lg text-text-muted hover:text-error transition-colors"
                                title="Delete category"
                            >
                                <Icon name="Trash2Icon" class="h-4 w-4" />
                            </button>
                        </div>
                    </div>
                </div>
                <div v-else class="py-8 text-center">
                    <EmptyState title="No categories yet" description="Create your first complaint category above." iconColor="text-text-muted" />
                </div>
            </Card>
        </div>

        <!-- Delete confirmation -->
        <Modal v-model:open="confirmDeleteOpen" title="Delete category">
            <p class="text-sm text-text-secondary mb-6">Are you sure you want to delete this category? This cannot be undone.</p>
            <template #footer>
                <Button variant="outline" @click="confirmDeleteOpen = false">Cancel</Button>
                <Button variant="danger" @click="proceedDelete" :loading="deleting">Delete</Button>
            </template>
        </Modal>
    </AppShell>
</template>

<script setup>
import { onMounted, ref, reactive, computed } from 'vue';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Input from '../../components/Input.vue';
import Textarea from '../../components/Textarea.vue';
import Button from '../../components/Button.vue';
import Icon from '../../components/Icon.vue';
import Modal from '../../components/Modal.vue';
import EmptyState from '../../components/EmptyState.vue';

const toast = useToastStore();
const categories = ref([]);
const form = ref({ name: '', type: '', description: '' });
const creating = ref(false);
const deleting = ref(false);
const confirmDeleteOpen = ref(false);
const deleteId = ref(null);

const editTarget = ref(null);
const isEditing = computed(() => !!editTarget.value);

async function load() {
    try {
        const { data } = await axios.get('/api/admin/categories');
        categories.value = normalizeItems(data);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load categories.'));
    }
}

async function createCategory() {
    if (isEditing.value) {
        await updateCategory();
        return;
    }
    creating.value = true;
    try {
        await axios.post('/api/admin/categories', form.value);
        toast.success('Category created successfully.');
        resetForm();
        await load();
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to create category.'));
    } finally {
        creating.value = false;
    }
}

async function updateCategory() {
    creating.value = true;
    try {
        await axios.put(`/api/admin/categories/${editTarget.value.id}`, form.value);
        toast.success('Category updated successfully.');
        resetForm();
        await load();
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to update category.'));
    } finally {
        creating.value = false;
    }
}

function editCategory(category) {
    editTarget.value = category;
    form.value = { name: category.name, type: category.type, description: category.description || '' };
}

function resetForm() {
    editTarget.value = null;
    form.value = { name: '', type: '', description: '' };
}

function confirmDelete(id) {
    deleteId.value = id;
    confirmDeleteOpen.value = true;
}

async function proceedDelete() {
    if (!deleteId.value) return;
    deleting.value = true;
    try {
        await axios.delete(`/api/admin/categories/${deleteId.value}`);
        toast.success('Category deleted successfully.');
        await load();
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to delete category.'));
    } finally {
        confirmDeleteOpen.value = false;
        deleteId.value = null;
        deleting.value = false;
    }
}

onMounted(load);
</script>
