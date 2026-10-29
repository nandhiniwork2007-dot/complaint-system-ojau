<template>
    <div
        class="flex flex-col rounded-xl border border-surface-border bg-surface p-5 shadow-sm transition-colors hover:bg-surface-elevated/50"
    >
        <div class="flex items-center justify-between mb-3">
            <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-surface-elevated text-primary">
                <slot name="icon">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"></path>
                    </svg>
                </slot>
            </div>
            <span class="text-xs font-medium text-text-secondary">{{ trend }}</span>
        </div>

        <p class="text-sm font-semibold text-text-main">{{ value }}</p>
        <p class="text-xs text-text-muted mt-0.5">{{ label }}</p>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    trend: { type: String, default: '' },
});
</script>
