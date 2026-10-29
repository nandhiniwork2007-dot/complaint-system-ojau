<template>
    <span
        :class="[
            'relative inline-block shrink-0',
            sizeClasses,
            rounded ? 'rounded-full' : 'rounded-lg',
        ]"
    >
        <img
            v-if="src"
            :src="src"
            :alt="alt"
            class="block w-full h-full object-cover"
            :class="['block w-full h-full object-cover', { 'opacity-50': !loaded }, imgClasses]"
            @load="loaded = true"
        />
        <div v-else class="flex items-center justify-center w-full h-full bg-surface-elevated text-text-muted">
            <slot name="initials">
                {{ initials }}
            </slot>
        </div>
    </span>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    src: { type: String, default: '' },
    name: { type: String, default: '' },
    initials: { type: String, default: '' },
    alt: { type: String, default: '' },
    size: { type: String, default: 'md' },
    rounded: { type: Boolean, default: true },
});

const loaded = ref(false);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'xs': return 'h-5 w-5';
        case 'sm': return 'h-7 w-7';
        case 'lg': return 'h-10 w-10';
        case 'xl': return 'h-12 w-12';
        default: return 'h-8 w-8';
    }
});

const imgClasses = computed(() => ({
    'animate-pulse': !loaded,
}));
</script>
