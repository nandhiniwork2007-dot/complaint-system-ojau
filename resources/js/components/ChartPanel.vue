<template>
    <section class="rounded-xl border border-slate-200 bg-white shadow-sm transition-all duration-200 hover:shadow-lg hover:shadow-indigo-100">
        <header class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4">
            <div>
                <h3 class="text-base font-semibold text-slate-900">{{ title }}</h3>
                <p v-if="subtitle" class="mt-0.5 text-sm text-slate-500">{{ subtitle }}</p>
            </div>
            <slot name="actions" />
        </header>
        <div class="p-5">
            <div v-if="loading" class="space-y-3" aria-busy="true" aria-label="Loading chart">
                <div class="h-4 w-1/3 rounded bg-slate-100 motion-safe:animate-pulse"></div>
                <div class="h-48 rounded bg-slate-100 motion-safe:animate-pulse"></div>
            </div>
            <div v-else-if="error" class="flex flex-col items-center gap-3 py-10 text-center" role="alert">
                <AppIcon name="alert" class="h-6 w-6 text-red-500" />
                <p class="text-sm text-slate-600">{{ error }}</p>
                <button type="button"
                    class="inline-flex items-center gap-2 rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
                    @click="$emit('retry')">
                    <AppIcon name="refresh" class="h-4 w-4" /> Try again
                </button>
            </div>
            <div v-else-if="empty" class="flex flex-col items-center gap-2 py-10 text-center">
                <AppIcon name="inbox" class="h-6 w-6 text-slate-400" />
                <p class="text-sm text-slate-500">{{ emptyText }}</p>
            </div>
            <slot v-else />
        </div>
    </section>
</template>

<script setup>
import AppIcon from './AppIcon.vue';

defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    loading: { type: Boolean, default: false },
    error: { type: String, default: '' },
    empty: { type: Boolean, default: false },
    emptyText: { type: String, default: 'No data to display for this selection.' },
});
defineEmits(['retry']);
</script>
