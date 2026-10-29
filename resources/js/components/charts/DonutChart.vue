<template>
    <div class="flex flex-col items-center gap-5 sm:flex-row sm:justify-center xl:flex-col">
        <div class="relative h-40 w-40 shrink-0">
            <svg viewBox="0 0 36 36" class="h-full w-full -rotate-90" role="img" :aria-label="ariaLabel">
                <circle cx="18" cy="18" r="15.9155" fill="none" stroke="#f1f5f9" stroke-width="4" />
                <circle v-for="segment in arcs" :key="segment.label" cx="18" cy="18" r="15.9155" fill="none"
                    :stroke="segment.color" stroke-width="4" :stroke-dasharray="`${segment.length} ${100 - segment.length}`"
                    :stroke-dashoffset="-segment.offset" />
            </svg>
            <div class="absolute inset-0 flex flex-col items-center justify-center">
                <span class="text-2xl font-semibold text-slate-900 tabular-nums">{{ total.toLocaleString() }}</span>
                <span class="text-xs text-slate-500">{{ centerLabel }}</span>
            </div>
        </div>
        <ul class="w-full max-w-xs space-y-2">
            <li v-for="segment in segments" :key="segment.label" class="flex items-center justify-between gap-3 text-sm">
                <span class="flex items-center gap-2 text-slate-600">
                    <span class="h-2.5 w-2.5 rounded-full" :style="{ backgroundColor: segment.color }" aria-hidden="true"></span>
                    {{ segment.label }}
                </span>
                <span class="font-medium text-slate-900 tabular-nums">
                    {{ segment.value.toLocaleString() }}
                    <span class="ml-1 font-normal text-slate-500">{{ percent(segment.value) }}</span>
                </span>
            </li>
        </ul>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    segments: { type: Array, default: () => [] },
    centerLabel: { type: String, default: 'Total' },
    ariaLabel: { type: String, default: 'Breakdown chart' },
});

const total = computed(() => props.segments.reduce((sum, s) => sum + (Number(s.value) || 0), 0));

const arcs = computed(() => {
    let offset = 0;
    return props.segments
        .filter((s) => Number(s.value) > 0)
        .map((s) => {
            const length = (Number(s.value) / total.value) * 100;
            const arc = { label: s.label, color: s.color, length, offset };
            offset += length;
            return arc;
        });
});

const percent = (value) => (total.value ? `${Math.round((value / total.value) * 100)}%` : '0%');
</script>
