<template>
    <ul class="space-y-4">
        <li v-for="item in items" :key="item.key ?? item.name">
            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                <span class="truncate font-medium text-slate-700">{{ item.name }}</span>
                <span class="shrink-0 text-slate-500 tabular-nums">
                    <span class="font-semibold text-slate-900">{{ item.total.toLocaleString() }}</span>
                    · {{ percent(item.total) }}
                </span>
            </div>
            <div class="h-2 overflow-hidden rounded-full bg-slate-100" role="presentation">
                <div class="h-2 rounded-full bg-blue-600" :style="{ width: `${Math.max((item.total / max) * 100, item.total ? 3 : 0)}%` }"></div>
            </div>
        </li>
    </ul>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    items: { type: Array, default: () => [] },
});

const max = computed(() => Math.max(...props.items.map((i) => i.total), 1));
const sum = computed(() => props.items.reduce((s, i) => s + i.total, 0));
const percent = (value) => (sum.value ? `${Math.round((value / sum.value) * 100)}%` : '0%');
</script>
