<template>
    <div class="pointer-events-none fixed top-4 right-4 z-50 grid w-full max-w-[420px] gap-3">
        <transition-group name="toast" class="flex flex-col gap-3">
            <div
                v-for="toast in store.items"
                :key="toast.id"
                class="rounded-xl border border-white/10 bg-surface/95 backdrop-blur-md px-4 py-3.5 text-sm font-medium shadow-xl ring-1 ring-white/5 transition-all duration-300"
                :class="[
                    toast.type === 'error'
                        ? 'border-error text-text-main bg-error/10'
                        : 'border-primary/30 text-text-main bg-surface/90',
                ]"
            >
                <div class="flex items-start gap-2.5">
                    <div class="flex shrink-0 items-center h-5 w-5 rounded-full flex items-center justify-center">
                        <svg
                            v-if="toast.type === 'error'"
                            class="h-3 w-3 text-error"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        <svg
                            v-else
                            class="h-3 w-3 text-primary"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                    </div>
                    <p class="text-text-main leading-relaxed">{{ toast.message }}</p>
                    <button
                        @click="dismiss(toast.id)"
                        class="ml-auto shrink-0 rounded p-1 text-text-muted hover:text-text-main transition-colors"
                        aria-label="Dismiss"
                    >
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
            </div>
        </transition-group>
    </div>
</template>

<script setup>
import { useToastStore } from '../stores/toasts';

const store = useToastStore();

function dismiss(id) {
    store.items = store.items.filter((item) => item.id !== id);
}
</script>

<style scoped>
.toast-enter-active, .toast-leave-active {
    transition: all 0.3s ease;
}
.toast-enter-from {
    opacity: 0;
    transform: translateX(20px);
}
.toast-leave-to {
    opacity: 0;
    transform: translateX(20px);
    max-height: 0;
    margin: 0;
    padding: 0;
}
</style>
