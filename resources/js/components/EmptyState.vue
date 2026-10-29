<template>
    <div class="flex flex-col items-center justify-center text-center py-12">
        <div
            class="flex h-16 w-16 items-center justify-center rounded-full bg-surface-elevated mb-4"
            :class="iconColor"
        >
            <slot name="icon">
                <svg
                    class="h-8 w-8"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.5"
                        d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    ></path>
                </svg>
            </slot>
        </div>

        <h3 v-if="title" class="text-lg font-semibold text-text-main mb-1">
            <slot name="title">{{ title }}</slot>
        </h3>

        <p v-if="description" class="text-text-secondary text-sm max-w-xs leading-relaxed">
            <slot name="description">{{ description }}</slot>
        </p>

        <slot name="action" v-if="$slots.action" class="mt-6" />
    </div>
</template>

<script setup>
defineProps({
    title: { type: String, default: '' },
    description: { type: String, default: '' },
    iconColor: { type: String, default: 'text-text-muted' },
});
</script>
