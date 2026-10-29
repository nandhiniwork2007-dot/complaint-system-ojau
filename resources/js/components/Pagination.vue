<template>
    <div v-if="totalPages > 1" class="flex items-center justify-between gap-4">
        <div class="text-sm text-text-secondary">
            Showing <span class="font-medium text-text-main">{{ from }}</span> to{" "}
            <span class="font-medium text-text-main">{{ to }}</span> of{" "}
            <span class="font-medium text-text-main">{{ total }}</span> results
        </div>

        <div class="flex items-center gap-1">
            <Button
                variant="ghost"
                size="sm"
                :disabled="currentPage === 1"
                @click="changePage(currentPage - 1)"
                aria-label="Previous page"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </Button>

            <button
                v-for="page in pages"
                :key="page"
                @click="changePage(page)"
                :class="[
                    'flex h-8 w-8 items-center justify-center rounded-lg text-sm font-medium transition-colors',
                    page === currentPage
                        ? 'bg-primary text-white'
                        : 'text-text-secondary hover:bg-surface-elevated hover:text-text-main',
                ]"
            >
                {{ page }}
            </button>

            <Button
                variant="ghost"
                size="sm"
                :disabled="currentPage === totalPages"
                @click="changePage(currentPage + 1)"
                aria-label="Next page"
            >
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
            </Button>
        </div>
    </div>
</template>

<script setup>
import { computed } from 'vue';
import Button from './Button.vue';

const props = defineProps({
    currentPage: { type: Number, default: 1 },
    perPage: { type: Number, default: 10 },
    total: { type: Number, default: 0 },
});

const emit = defineEmits(['update:currentPage']);

const totalPages = computed(() => Math.ceil(props.total / props.perPage) || 1);

const from = computed(() => {
    if (props.total === 0) return 0;
    return (props.currentPage - 1) * props.perPage + 1;
});

const to = computed(() => Math.min(props.currentPage * props.perPage, props.total));

const pages = computed(() => {
    const pages = [];
    const total = totalPages.value;
    const current = props.currentPage;
    const maxVisible = 5;

    let start = Math.max(1, current - Math.floor(maxVisible / 2));
    let end = Math.min(total, start + maxVisible - 1);
    start = Math.max(1, end - maxVisible + 1);

    for (let i = start; i <= end; i++) {
        pages.push(i);
    }
    return pages;
});

function changePage(page) {
    if (page < 1 || page > totalPages.value || page === props.currentPage) {
        return;
    }
    emit('update:currentPage', page);
}
</script>
