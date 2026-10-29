<template>
    <div class="space-y-3">
        <label
            class="flex items-center justify-center gap-2 rounded-xl border-2 border-dashed border-surface-border bg-surface/50 px-6 py-6 text-sm text-text-secondary hover:border-primary hover:text-text-main transition-colors cursor-pointer"
        >
            <input
                class="sr-only"
                multiple
                accept=".jpg,.jpeg,.png,.pdf"
                type="file"
                @change="captureFiles"
            />
            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
            </svg>
            <span>Choose files</span>
            <span class="text-xs text-text-muted">(drag &amp; drop supported)</span>
        </label>

        <div v-if="files.length" class="space-y-2">
            <div
                v-for="(file, index) in files"
                :key="file.name + index"
                class="flex items-center justify-between gap-2 rounded-lg border border-surface-border bg-surface/50 px-3 py-2 text-xs"
            >
                <div class="flex items-center gap-2">
                    <span class="text-text-secondary">{{ file.size / 1024 }} KB</span>
                    <span class="text-text-main truncate max-w-xs">{{ file.name }}</span>
                </div>
                <button
                    @click="removeFile(index)"
                    class="rounded p-1 text-text-muted hover:text-error transition-colors"
                    aria-label="Remove attachment"
                >
                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        </div>

        <p v-if="error" class="text-xs text-error">{{ Array.isArray(error) ? error[0] : error }}</p>
    </div>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    modelValue: { type: Array, default: () => [] },
    error: { type: [String, Array], default: '' },
});

const emit = defineEmits(['update:modelValue']);

const files = ref(props.modelValue.slice());

function captureFiles(event) {
    const selected = Array.from(event.target.files || []);
    files.value = [...files.value, ...selected];
    emit('update:modelValue', files.value);
}

function removeFile(index) {
    files.value.splice(index, 1);
    emit('update:modelValue', files.value);
}
</script>
