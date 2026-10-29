<template>
    <div
        :class="[
            'inline-flex items-center justify-center rounded-full transition-colors',
            variant === 'page' ? 'h-12 w-12' : 'h-8 w-8',
        ]"
    >
        <svg
            class="animate-spin h-full w-full text-primary"
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
        <span v-if="label" class="ml-3 text-sm text-text-secondary">
            {{ label }}
        </span>
    </div>
</template>

<script setup>
defineProps({
    variant: { type: String, default: 'inline' },
    label: { type: String, default: '' },
});
</script>
