<template>
    <Transition
        enter="transition-opacity duration-200"
        enter-from="opacity-0"
        enter-to="opacity-100"
        leave="transition-opacity duration-150"
        leave-from="opacity-100"
        leave-to="opacity-0"
    >
        <div
            v-if="open"
            class="fixed inset-0 z-50 flex items-center justify-center"
            @click.self="close"
        >
            <div
                class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
                @click="close"
            ></div>

            <div
                class="relative z-10 w-full max-w-lg mx-4 rounded-2xl border border-surface-border bg-surface shadow-xl transition-all"
                @click.stop
            >
                <div class="flex items-center justify-between p-6 border-b border-surface-border">
                    <h3
                        v-if="title"
                        class="text-lg font-semibold text-text-main"
                    >
                        <slot name="title">{{ title }}</slot>
                    </h3>
                    <button
                        @click="close"
                        class="rounded-lg p-2 text-text-muted hover:text-text-main hover:bg-surface-elevated transition-colors"
                        aria-label="Close"
                    >
                        <svg
                            class="h-5 w-5"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M6 18L18 6M6 6l12 12"
                            ></path>
                        </svg>
                    </button>
                </div>

                <div class="p-6">
                    <slot />
                </div>

                <div v-if="$slots.footer" class="p-6 border-t border-surface-border bg-surface/50">
                    <div class="flex justify-end gap-3">
                        <slot name="footer" />
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<script setup>
import { ref } from 'vue';

const props = defineProps({
    open: { type: Boolean, default: false },
    title: { type: String, default: '' },
});

const emit = defineEmits(['update:open', 'close']);

function close() {
    emit('update:open', false);
    emit('close');
}
</script>
