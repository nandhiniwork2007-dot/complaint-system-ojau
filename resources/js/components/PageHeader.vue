<template>
    <div class="flex flex-col gap-2">
        <div v-if="breadcrumbs.length" class="flex flex-wrap items-center gap-1.5 text-xs text-text-muted">
            <template v-for="(crumb, index) in breadcrumbs" :key="index">
                <svg v-if="index > 0" class="h-3 w-3 text-text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13 7l5 5m0 0l-5 5m5-5L6 17"></path>
                </svg>

                <RouterLink
                    v-if="crumb.to"
                    :to="crumb.to"
                    class="transition-colors hover:text-text-main"
                >
                    {{ crumb.label }}
                </RouterLink>

                <span v-else class="text-text-secondary">{{ crumb.label }}</span>
            </template>
        </div>

        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div>
                <h1 class="text-2xl font-semibold leading-tight tracking-tight text-text-main sm:text-3xl">{{ title }}</h1>
                <p v-if="subtitle" class="text-sm text-text-secondary mt-0.5">{{ subtitle }}</p>
            </div>

            <div v-if="$slots.actions" class="flex items-center gap-2">
                <slot name="actions" />
            </div>
        </div>

        <div v-if="$slots.lead" class="mt-2">
            <slot name="lead" />
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, required: true },
    subtitle: { type: String, default: '' },
    breadcrumbs: { type: Array, default: () => [] },
});
</script>
