<template>
    <div class="relative overflow-hidden rounded-2xl border border-surface-border bg-surface-elevated/50 p-5 transition-all hover:border-primary/30 hover:shadow-lg hover:shadow-primary/10">
        <div v-if="icon" class="absolute -right-4 -top-4 h-24 w-24 rounded-full opacity-5">
            <div class="h-full w-full bg-linear-to-br from-primary to-secondary blur-xl"></div>
        </div>
        <div class="relative">
            <div v-if="icon" class="mb-3 flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <slot name="icon"></slot>
            </div>
            <p class="text-xs font-semibold text-text-muted uppercase tracking-widest">{{ label }}</p>
            <p class="mt-2 text-3xl font-bold text-text-main tabular-nums">{{ value }}</p>
            <p v-if="trend" class="mt-1 text-xs text-text-secondary">{{ trend }}</p>
        </div>
    </div>
</template>

<script setup>
defineProps({
    label: { type: String, required: true },
    value: { type: [String, Number], required: true },
    trend: { type: String, default: '' },
    icon: { type: Boolean, default: false },
});
</script>
