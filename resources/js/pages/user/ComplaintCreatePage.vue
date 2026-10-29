<template>
    <AppShell section="user" title="Submit a complaint" subtitle="Describe the issue clearly so the team can act quickly.">
        <Card padded bordered class="max-w-2xl">
            <div v-if="!submitted" class="space-y-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="field">
                        <label class="block text-sm font-medium text-text-secondary mb-1.5">Title</label>
                        <Input
                            v-model="form.title"
                            type="text"
                            name="title"
                            placeholder="e.g. Billing error on last invoice"
                            :error="errors.title"
                            required
                        />
                    </div>

                    <div class="field">
                        <label class="block text-sm font-medium text-text-secondary mb-1.5">Category</label>
                        <Select
                            v-model="form.complaint_category_id"
                            name="complaint_category_id"
                            placeholder="Select a category"
                            :error="errors.complaint_category_id"
                            required
                        >
                            <option value="">Choose a category</option>
                            <option v-for="category in categories" :key="category.id" :value="category.id">
                                {{ category.name }}
                            </option>
                        </Select>
                    </div>
                </div>

                <div class="field">
                    <label class="block text-sm font-medium text-text-secondary mb-1.5">Description</label>
                    <p class="text-xs text-text-secondary mb-2">Describe what happened, when it happened, and what you expected instead.</p>
                    <Textarea
                        v-model="form.description"
                        name="description"
                        rows="8"
                        placeholder="e.g. I was charged twice for the same subscription on May 1st. I expected a single charge."
                        :error="errors.description"
                        required
                    />
                </div>

                <div class="field">
                    <label class="block text-sm font-medium text-text-secondary mb-1.5">Attachments</label>
                    <p class="text-xs text-text-secondary mb-2">Supporting evidence such as screenshots, receipts, or PDFs (up to 5 files, 5 MB each).</p>
                    <FileAttachment
                        v-model="form.attachments"
                        :error="errors.attachments"
                    />
                </div>

                <div class="flex justify-end pt-4">
                    <Button
                        variant="outline"
                        @click="router.push('/complaints')"
                        class="mr-3"
                    >
                        Cancel
                    </Button>
                    <Button
                        type="submit"
                        variant="primary"
                        :loading="submitting"
                        class="w-auto"
                    >
                        {{ submitting ? 'Submitting...' : 'Submit complaint' }}
                    </Button>
                </div>
            </div>

            <div v-else class="py-12 text-center">
                <div class="flex h-16 w-16 items-center justify-center rounded-full bg-success/10 text-success mb-4 mx-auto">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                    </svg>
                </div>
                <h3 class="text-lg font-semibold text-text-main mb-1">Complaint submitted</h3>
                <p class="text-sm text-text-secondary">Your complaint has been received and is now pending review.</p>
                <Button variant="primary" @click="router.push('/complaints')">View my complaints</Button>
            </div>
        </Card>
    </AppShell>
</template>

<script setup>
import { onMounted, ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useToastStore } from '../../stores/toasts';
import { extractMessage, normalizeItems } from '../../utils/http';
import AppShell from '../../layouts/AppShell.vue';
import Card from '../../components/Card.vue';
import Button from '../../components/Button.vue';
import Icon from '../../components/Icon.vue';
import Input from '../../components/Input.vue';
import Select from '../../components/Select.vue';
import Textarea from '../../components/Textarea.vue';
import FileAttachment from '../../components/FileAttachment.vue';

const router = useRouter();
const toast = useToastStore();
const categories = ref([]);
const form = reactive({ title: '', complaint_category_id: '', description: '', attachments: [] });
const errors = ref({});
const submitting = ref(false);
const submitted = ref(false);

onMounted(async () => {
    try {
        const { data } = await axios.get('/api/categories');
        categories.value = normalizeItems(data);
    } catch (error) {
        toast.error(extractMessage(error, 'Unable to load categories.'));
    }
});

async function submit() {
    submitting.value = true;
    errors.value = {};

    const formData = new FormData();
    formData.append('title', form.title);
    formData.append('complaint_category_id', form.complaint_category_id);
    formData.append('description', form.description);
    form.attachments.forEach((file) => formData.append('attachments[]', file));

    try {
        const { data } = await axios.post('/api/complaints', formData);
        toast.success(data.message || 'Complaint submitted successfully.');
        submitted.value = true;
    } catch (error) {
        errors.value = error.response?.data?.errors ?? {};
        toast.error(extractMessage(error, 'Unable to submit complaint.'));
    } finally {
        submitting.value = false;
    }
}
</script>
