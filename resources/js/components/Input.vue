<template>
    <div class="group field">
        <label
            v-if="label"
            class="block text-sm font-medium text-text-secondary mb-1.5"
            :for="fieldId"
        >
            {{ label }}
            <span v-if="required" class="text-error ml-0.5">*</span>
        </label>

        <input
            :id="fieldId"
            :name="name"
            v-model="localValue"
            :type="type"
            :placeholder="placeholder"
            :disabled="disabled"
            :required="required"
            :autocomplete="autocomplete"
            class="block w-full rounded-lg border border-surface-border bg-surface px-4 py-3 text-text-main transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:border-primary"
            :class="{
                'border-error': hasError,
                'bg-surface-elevated': disabled,
            }"
            @input="updateValue($event.target.value)"
            @blur="$emit('blur', $event)"
        />

        <div v-if="helper" class="mt-1 text-xs text-text-muted">
            {{ helper }}
        </div>

        <p v-if="hasError" class="mt-1 text-xs text-error">
            {{ errorText }}
        </p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: { type: [String, Number, Boolean], default: '' },
    label: { type: String, default: '' },
    helper: { type: String, default: '' },
    error: { type: [String, Array], default: '' },
    type: { type: String, default: 'text' },
    placeholder: { type: String, default: '' },
    disabled: { type: Boolean, default: false },
    required: { type: Boolean, default: false },
    name: { type: String, default: '' },
    autocomplete: { type: String, default: 'off' },
});

const emit = defineEmits(['update:modelValue', 'blur']);

const fieldId = computed(() => props.name || `input-${Math.random().toString(36).slice(2)}`);

const hasError = computed(() => {
    const error = props.error;
    if (Array.isArray(error)) {
        return error.length > 0;
    }
    return !!error;
});

const errorText = computed(() => {
    const error = props.error;
    if (Array.isArray(error)) {
        return error[0] ?? '';
    }
    return error ?? '';
});

const localValue = computed({
    get: () => props.modelValue ?? '',
    set: (value) => emit('update:modelValue', value),
});

function updateValue(value) {
    emit('update:modelValue', value);
}
</script>
