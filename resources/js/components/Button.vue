<template>
    <button
        :type="type"
        :disabled="disabled || loading"
        :class="
            [
                'inline-flex items-center justify-center gap-2 whitespace-nowrap font-medium transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50',
                variantClasses,
                sizeClasses,
                {
                    'ring-primary': loading,
                },
            ]
        "
        @click="$emit('click', $event)"
    >
        <svg
            v-if="loading"
            class="h-4 w-4 shrink-0 animate-spin"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            ></circle>
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
            ></path>
        </svg>
        <span v-if="$slots.icon" class="flex items-center gap-1">
            <slot name="icon" />
        </span>
        <span class="flex items-center gap-1">
            <slot>{{ label || (loading ? loadingText : '') }}</slot>
        </span>
    </button>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    type: { type: String, default: 'button' },
    label: { type: String, default: '' },
    variant: {
        type: String,
        default: 'primary',
        validator: (v) => ['primary', 'secondary', 'ghost', 'danger', 'outline'].includes(v),
    },
    size: {
        type: String,
        default: 'md',
        validator: (v) => ['sm', 'md', 'lg'].includes(v),
    },
    disabled: { type: Boolean, default: false },
    loading: { type: Boolean, default: false },
    loadingText: { type: String, default: 'Loading...' },
    href: { type: String, default: '' },
});

const emit = defineEmits(['click']);

const variantClasses = computed(() => {
    const base = 'text-text-main rounded-lg';
    switch (props.variant) {
        case 'primary':
            return `${base} bg-primary hover:bg-primary-dark shadow-glow`;
        case 'secondary':
            return `${base} bg-surface-elevated hover:bg-surface border border-surface-border`;
        case 'ghost':
            return `${base} hover:bg-surface-elevated`;
        case 'danger':
            return `${base} bg-error hover:bg-error/90`;
        case 'outline':
            return `${base} border border-surface-border hover:bg-surface-elevated`;
        default:
            return base;
    }
});

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'px-3 py-2 text-sm';
        case 'lg':
            return 'px-6 py-3.5 text-base';
        default:
            return 'px-4 py-3 text-sm';
    }
});
</script>
