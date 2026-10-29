<template>
    <section
        :class="[
            'rounded-xl border border-surface-border bg-surface transition-colors',
            padded ? 'p-6' : 'p-0',
            bordered ? 'border' : 'border-transparent',
            elevated ? 'shadow-lg' : 'shadow-sm',
        ]"
    >
        <slot />
    </section>
</template>

<script setup>
defineProps({
    padded: { type: Boolean, default: true },
    bordered: { type: Boolean, default: true },
    elevated: { type: Boolean, default: false },
});
</script>
