<template>
    <ol v-if="steps.length > 1" class="flex items-center pb-8">
        <template v-for="(label, index) in steps" :key="label">
            <li class="relative flex flex-col items-center" :aria-current="index + 1 === current ? 'step' : undefined">
                <span
                    class="flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold transition-colors"
                    :class="circleClasses(index)"
                >
                    <svg
                        v-if="isCompleted(index)"
                        class="h-4 w-4"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path>
                    </svg>
                    <span v-else>{{ index + 1 }}</span>
                </span>

                <span class="absolute left-1/2 top-9 w-20 -translate-x-1/2 text-center text-[11px] font-medium leading-tight" :class="labelClasses(index)">
                    {{ label }}
                </span>
            </li>

            <li
                v-if="index < steps.length - 1"
                class="mx-2 h-0.5 flex-1 rounded-full transition-colors"
                :class="index + 1 < current ? 'bg-primary/60' : 'bg-surface-border'"
                aria-hidden="true"
            ></li>
        </template>
    </ol>
</template>

<script setup>
const props = defineProps({
    steps: { type: Array, default: () => [] },
    current: { type: Number, default: 1 },
});

function isCompleted(index) {
    return index + 1 < props.current;
}

function isActive(index) {
    return index + 1 === props.current;
}

function circleClasses(index) {
    if (isCompleted(index)) {
        return 'border-primary bg-primary text-white';
    }

    if (isActive(index)) {
        return 'border-primary/60 bg-primary/10 text-primary shadow-glow';
    }

    return 'border-surface-border bg-surface text-text-muted';
}

function labelClasses(index) {
    if (isActive(index)) {
        return 'text-text-main font-semibold';
    }

    return isCompleted(index) ? 'text-text-secondary' : 'text-text-muted';
}
</script>
