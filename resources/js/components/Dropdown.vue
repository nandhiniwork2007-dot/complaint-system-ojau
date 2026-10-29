<template>
    <div class="relative inline-block" @click.outside="close">
        <div class="flex items-center gap-2" ref="triggerRef">
            <slot name="trigger" />
        </div>

        <Transition
            enter="transition-opacity duration-100"
            enter-from="opacity-0 translate-y-1"
            enter-to="opacity-100 translate-y-0"
            leave="transition-opacity duration-75"
            leave-from="opacity-100 translate-y-0"
            leave-to="opacity-0 translate-y-1"
        >
            <div
                v-if="open"
                class="absolute z-50 mt-1.5 w-48 rounded-xl border border-surface-border bg-surface shadow-lg py-1.5"
                :style="{ left: triggerX + 'px', top: triggerY + 'px' }"
            >
                <slot name="content" :close="close" />
            </div>
        </Transition>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
});

const emit = defineEmits(['update:open']);

const triggerRef = ref(null);
const triggerX = ref(0);
const triggerY = ref(0);

onMounted(() => {
    const rect = triggerRef.value?.getBoundingClientRect();
    if (rect) {
        triggerX.value = rect.left;
        triggerY.value = rect.bottom + 8;
    }
});

function close() {
    emit('update:open', false);
}
</script>
