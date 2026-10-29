<template>
    <div>
        <svg :viewBox="`0 0 ${W} ${H}`" class="h-auto w-full select-none" role="img" tabindex="0"
            :aria-label="`${ariaLabel}. Use left and right arrow keys to inspect each point.`"
            @keydown.left.prevent="move(-1)" @keydown.right.prevent="move(1)" @blur="active = null"
            @mouseleave="active = null">
            <g v-for="tick in ticks" :key="tick.value">
                <line :x1="PAD.left" :x2="W - PAD.right" :y1="tick.y" :y2="tick.y" stroke="#e2e8f0" stroke-width="1" />
                <text :x="PAD.left - 8" :y="tick.y + 4" text-anchor="end" font-size="11" fill="#64748b">{{ tick.value }}</text>
            </g>
            <path v-if="points.length > 1" :d="areaPath" fill="#3b82f6" fill-opacity="0.08" />
            <path v-if="points.length > 1" :d="linePath" fill="none" stroke="#2563eb" stroke-width="2"
                stroke-linejoin="round" stroke-linecap="round" />
            <g v-for="(point, index) in points" :key="point.key">
                <text v-if="index % labelStep === 0" :x="point.x" :y="H - 8" text-anchor="middle" font-size="11"
                    fill="#64748b">{{ point.label }}</text>
                <circle v-if="points.length <= 40 || active === index" :cx="point.x" :cy="point.y"
                    :r="active === index ? 5 : 3" fill="#fff" stroke="#2563eb" stroke-width="2" />
                <rect :x="point.x - hitWidth / 2" :y="PAD.top" :width="hitWidth" :height="innerH" fill="transparent"
                    @mouseenter="active = index" />
            </g>
            <g v-if="activePoint" pointer-events="none">
                <line :x1="activePoint.x" :x2="activePoint.x" :y1="PAD.top" :y2="PAD.top + innerH" stroke="#94a3b8"
                    stroke-dasharray="3 3" />
                <rect :x="tooltip.x" :y="tooltip.y" width="104" height="38" rx="6" fill="#0f172a" />
                <text :x="tooltip.x + 10" :y="tooltip.y + 16" font-size="11" fill="#cbd5e1">{{ activePoint.label }}</text>
                <text :x="tooltip.x + 10" :y="tooltip.y + 31" font-size="12" font-weight="600" fill="#fff">
                    {{ activePoint.total }} {{ activePoint.total === 1 ? 'complaint' : 'complaints' }}
                </text>
            </g>
        </svg>
    </div>
</template>

<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    data: { type: Array, default: () => [] },
    ariaLabel: { type: String, default: 'Complaint trend' },
});

const W = 640;
const H = 260;
const PAD = { top: 16, right: 16, bottom: 30, left: 36 };
const innerW = W - PAD.left - PAD.right;
const innerH = H - PAD.top - PAD.bottom;
const active = ref(null);

const maxValue = computed(() => Math.max(...props.data.map((d) => Number(d.total) || 0), 0));
const yMax = computed(() => {
    const max = maxValue.value;
    if (max <= 4) return Math.max(max, 1);
    const step = Math.pow(10, Math.floor(Math.log10(max / 4)));
    const nice = [1, 2, 5, 10].map((m) => m * step).find((s) => s * 4 >= max) || step * 10;
    return nice * 4;
});

const points = computed(() => props.data.map((d, i) => ({
    key: d.key ?? i,
    label: d.label,
    total: Number(d.total) || 0,
    x: props.data.length === 1 ? PAD.left + innerW / 2 : PAD.left + (i * innerW) / (props.data.length - 1),
    y: PAD.top + innerH - (Number(d.total) || 0) / yMax.value * innerH,
})));

const ticks = computed(() => {
    const count = yMax.value <= 4 ? yMax.value : 4;
    return Array.from({ length: count + 1 }, (_, i) => {
        const value = Math.round((yMax.value / count) * i);
        return { value, y: PAD.top + innerH - (value / yMax.value) * innerH };
    });
});

const linePath = computed(() => points.value.map((p, i) => `${i ? 'L' : 'M'}${p.x.toFixed(1)},${p.y.toFixed(1)}`).join(' '));
const areaPath = computed(() => {
    const base = PAD.top + innerH;
    const first = points.value[0];
    const last = points.value[points.value.length - 1];
    return `${linePath.value} L${last.x.toFixed(1)},${base} L${first.x.toFixed(1)},${base} Z`;
});

const labelStep = computed(() => Math.max(1, Math.ceil(points.value.length / 8)));
const hitWidth = computed(() => (points.value.length > 1 ? innerW / (points.value.length - 1) : innerW));
const activePoint = computed(() => (active.value === null ? null : points.value[active.value] || null));
const tooltip = computed(() => {
    const p = activePoint.value;
    if (!p) return { x: 0, y: 0 };
    const x = Math.min(Math.max(p.x - 52, PAD.left), W - PAD.right - 104);
    const y = Math.max(p.y - 50, 2);
    return { x, y };
});

function move(delta) {
    if (!points.value.length) return;
    const next = active.value === null ? (delta > 0 ? 0 : points.value.length - 1) : active.value + delta;
    active.value = Math.min(Math.max(next, 0), points.value.length - 1);
}
</script>
