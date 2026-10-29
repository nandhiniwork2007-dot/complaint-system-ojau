<template>
    <form class="grid gap-5" @submit.prevent="submitForm">
        <div class="grid gap-4 md:grid-cols-2">
            <div class="field">
                <label class="block text-sm font-medium text-text-secondary mb-1.5">Title</label>
                <Input
                    v-model="form.title"
                    type="text"
                    name="title"
                    placeholder="Brief summary of the issue"
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
            <p class="text-xs text-text-secondary mb-2">Describe what happened, when, and what you expected instead.</p>
            <Textarea
                v-model="form.description"
                name="description"
                rows="8"
                placeholder="e.g. I was charged twice for the same subscription on May 1st."
                :error="errors.description"
                required
            />
        </div>

        <div class="field">
            <label class="block text-sm font-medium text-text-secondary mb-1.5">Attachments</label>
            <p class="text-xs text-text-secondary mb-2">Up to 5 files. Images and PDF only, 5 MB each.</p>
            <FileAttachment
                v-model="form.attachments"
                :error="errors.attachments"
            />
        </div>

        <div class="pt-2">
            <Button
                type="submit"
                variant="primary"
                :disabled="submitting"
                :loading="submitting"
            >
                {{ submitting ? 'Saving...' : submitLabel }}
            </Button>
        </div>
    </form>
</template>

<script setup>
import { reactive, ref, watch } from 'vue';
import Input from './Input.vue';
import Select from './Select.vue';
import Textarea from './Textarea.vue';
import FileAttachment from './FileAttachment.vue';
import Button from './Button.vue';

const props = defineProps({
    complaint: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
    submitting: { type: Boolean, default: false },
    submitLabel: { type: String, default: 'Submit complaint' },
});

const emit = defineEmits(['submit']);

const form = reactive({
    title: '',
    complaint_category_id: '',
    description: '',
    attachments: [],
});

watch(
    () => props.complaint,
    (complaint) => {
        form.title = complaint?.title ?? '';
        form.complaint_category_id = complaint?.category?.id ?? '';
        form.description = complaint?.description ?? '';
        form.attachments = [];
    },
    { immediate: true },
);

function submitForm() {
    emit('submit', { ...form });
}
</script>
