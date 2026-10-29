<template>
    <span
        class="inline-flex items-center justify-center gap-1.5 whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-tighter transition-colors"
        :class="badgeClasses"
    >
        <span v-if="$slots.icon" class="flex shrink-0">
            <slot name="icon" />
        </span>
        <slot />
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    variant: {
        type: String,
        default: 'default',
    },
});

const STATUS_VARIANTS = {
    pending: 'bg-warning/15 text-warning border border-warning/30',
    in_progress: 'bg-secondary/15 text-secondary border border-secondary/30',
    resolved: 'bg-success/15 text-success border border-success/30',
    rejected: 'bg-error/15 text-error border border-error/30',
    withdrawn: 'bg-text-muted/15 text-text-muted border border-text-muted/30',
};

const PRIORITY_VARIANTS = {
    low: 'bg-text-muted/15 text-text-muted border border-text-muted/30',
    normal: 'bg-secondary/15 text-secondary border border-secondary/30',
    high: 'bg-warning/15 text-warning border border-warning/30',
    urgent: 'bg-error/15 text-error border border-error/30',
};

const badgeClasses = computed(() => {
    if (props.variant in STATUS_VARIANTS) {
        return STATUS_VARIANTS[props.variant];
    }
    if (props.variant in PRIORITY_VARIANTS) {
        return PRIORITY_VARIANTS[props.variant];
    }
    return 'bg-surface-elevated text-text-secondary border border-surface-border';
});
</script>
