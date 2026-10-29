<template>
    <div
        ref="container"
        class="relative h-full w-full select-none"
        :style="{ minHeight: `${minHeight}px` }"
    >
        <svg
            v-if="points.length"
            class="block h-full w-full"
            :viewBox="`0 0 ${chartWidth} ${chartHeight}`"
            preserveAspectRatio="xMidYMid meet"
            role="img"
            aria-label="Complaint trend"
        >
            <defs>
                <linearGradient :id="gradientId" x1="0" x2="0" y1="0" y2="1">
                    <stop offset="0%" stop-color="#7C5CFC" stop-opacity="0.35" />
                    <stop offset="100%" stop-color="#7C5CFC" stop-opacity="0" />
                </linearGradient>
            </defs>

            <g>
                <line
                    v-for="tick in yTicks"
                    :key="`grid-${tick.value}`"
                    :x1="padding.left"
                    :x2="chartWidth - padding.right"
                    :y1="tick.y"
                    :y2="tick.y"
                    stroke="#243047"
                    stroke-width="1"
                />
            </g>

            <path
                v-if="showArea"
                :d="areaPath"
                :fill="`url(#${gradientId})`"
            ></path>

            <path
                :d="linePath"
                fill="none"
                stroke="#7C5CFC"
                stroke-width="2.5"
                stroke-linecap="round"
                stroke-linejoin="round"
            ></path>

            <g v-if="showPoints">
                <circle
                    v-for="(point, index) in points"
                    :key="index"
                    :cx="point.x"
                    :cy="point.y"
                    :r="hoveredIndex === index ? 5 : 4"
                    fill="#7C5CFC"
                    stroke="#090B14"
                    stroke-width="2"
                    class="cursor-pointer transition-all"
                    @mouseenter="hoveredIndex = index"
                    @mouseleave="hoveredIndex = -1"
                ></circle>
            </g>

            <g>
                <text
                    v-for="tick in yTicks"
                    :key="`y-${tick.value}`"
                    :x="padding.left - 8"
                    :y="tick.y"
                    class="fill-text-muted text-[10px]"
                    text-anchor="end"
                    dominant-baseline="middle"
                >
                    {{ tick.value }}
                </text>
            </g>

            <g>
                <text
                    v-for="label in xLabels"
                    :key="`${label.index}-${label.value}`"
                    :x="label.x"
                    :y="chartHeight - 8"
                    class="fill-text-muted text-[10px]"
                    :text-anchor="label.anchor"
                >
                    {{ label.value }}
                </text>
            </g>
        </svg>

        <div v-else class="flex h-full items-center justify-center text-sm text-text-muted">
            No trend data for this period.
        </div>

        <div
            v-if="activePoint"
            class="pointer-events-none absolute z-10 whitespace-nowrap rounded-lg border border-surface-border bg-surface px-2.5 py-1.5 text-xs shadow-lg"
            :style="tooltipStyle"
        >
            <div class="text-text-muted">{{ activePoint.label }}</div>
            <div class="font-semibold text-text-main">
                {{ activePoint.value }} {{ activePoint.value === 1 ? 'complaint' : 'complaints' }}
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    height: { type: [String, Number], default: 120 },
    showArea: { type: Boolean, default: true },
    showPoints: { type: Boolean, default: true },
});

const container = ref(null);
const chartWidth = ref(600);
const chartHeight = ref(Number(props.height) || 120);
const hoveredIndex = ref(-1);
const gradientId = `trend-chart-gradient-${Math.random().toString(36).slice(2, 9)}`;
let resizeObserver;

const minHeight = computed(() => Number(props.height) || 120);

const padding = computed(() => ({
    top: 16,
    right: 16,
    bottom: 30,
    left: chartWidth.value < 400 ? 28 : 36,
}));

const plotWidth = computed(() => Math.max(chartWidth.value - padding.value.left - padding.value.right, 1));
const plotHeight = computed(() => Math.max(chartHeight.value - padding.value.top - padding.value.bottom, 1));

function readValue(point) {
    const value = Number(point?.value ?? point?.total ?? 0);
    return Number.isFinite(value) ? value : 0;
}

const values = computed(() => props.data.map(readValue));
const maxValue = computed(() => Math.max(...values.value, 1));

const points = computed(() => {
    const count = props.data.length;
    if (!count) {
        return [];
    }

    return props.data.map((point, index) => {
        const value = readValue(point);
        const x = padding.value.left + (count === 1 ? plotWidth.value / 2 : (index / (count - 1)) * plotWidth.value);
        const y = padding.value.top + plotHeight.value - (value / maxValue.value) * plotHeight.value;

        return { x, y, label: point.label || '', value };
    });
});

const linePath = computed(() => points.value.map((point, index) => `${index === 0 ? 'M' : 'L'} ${point.x} ${point.y}`).join(' '));

const areaPath = computed(() => {
    if (!points.value.length) {
        return '';
    }

    const baseline = padding.value.top + plotHeight.value;
    const first = points.value[0];
    const last = points.value[points.value.length - 1];

    return `${linePath.value} L ${last.x} ${baseline} L ${first.x} ${baseline} Z`;
});

const yTicks = computed(() => {
    if (!points.value.length) {
        return [];
    }

    const maximum = maxValue.value;
    const baseline = padding.value.top + plotHeight.value;
    const tickValues = [...new Set([0, Math.round(maximum / 2), maximum])];

    return tickValues.map((value) => ({
        value,
        y: baseline - (value / maximum) * plotHeight.value,
    }));
});

const xLabels = computed(() => {
    const count = props.data.length;
    if (!count) {
        return [];
    }

    const maxLabels = Math.max(2, Math.floor(plotWidth.value / 72));
    const step = Math.max(1, Math.ceil(count / maxLabels));
    const indexes = [];

    for (let index = 0; index < count; index += step) {
        indexes.push(index);
    }

    if (indexes[indexes.length - 1] !== count - 1) {
        indexes.push(count - 1);
    }

    return indexes.map((index) => ({
        index,
        x: points.value[index].x,
        value: props.data[index].label || '',
        anchor: index === 0 ? 'start' : index === count - 1 ? 'end' : 'middle',
    }));
});

const activePoint = computed(() => points.value[hoveredIndex.value] ?? null);

const tooltipStyle = computed(() => {
    if (!activePoint.value) {
        return {};
    }

    const x = Math.min(Math.max(activePoint.value.x, 56), chartWidth.value - 56);
    const above = activePoint.value.y > 56;

    return {
        left: `${x}px`,
        top: above ? `${activePoint.value.y - 12}px` : `${activePoint.value.y + 12}px`,
        transform: above ? 'translate(-50%, -100%)' : 'translate(-50%, 0)',
    };
});

watch(() => props.data, () => {
    hoveredIndex.value = -1;
});

onMounted(() => {
    resizeObserver = new ResizeObserver(([entry]) => {
        const { width, height } = entry.contentRect;

        if (width > 0) {
            chartWidth.value = Math.round(width);
        }

        if (height > 0) {
            chartHeight.value = Math.round(height);
        }
    });

    resizeObserver.observe(container.value);
});

onBeforeUnmount(() => resizeObserver?.disconnect());
</script>
