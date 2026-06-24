<script setup lang="ts">
import { computed, ref } from 'vue';
import type { TrendPoint, ChartDataPoint } from '@/types/dashboard';

const props = withDefaults(defineProps<{
    data?: TrendPoint[] | ChartDataPoint[];
    color?: string;
    height?: number;
    formatValue?: (v: number) => string;
}>(), { data: () => [] });

const color  = computed(() => props.color ?? '#6366f1');
const HEIGHT = computed(() => props.height ?? 180);
const WIDTH  = 520;
const PAD    = { top: 12, right: 16, bottom: 32, left: 52 };

const innerW = computed(() => WIDTH - PAD.left - PAD.right);
const innerH = computed(() => HEIGHT.value - PAD.top - PAD.bottom);

const normalised = computed(() =>
    props.data.map(d => ({ label: d.label, value: d.value, color: (d as ChartDataPoint).color })),
);

const maxVal = computed(() => Math.max(...normalised.value.map(d => d.value), 1));

const barWidth = computed(() => {
    const n = normalised.value.length;
    return n > 0 ? (innerW.value / n) * 0.6 : 20;
});

const barGap = computed(() => {
    const n = normalised.value.length;
    return n > 0 ? innerW.value / n : 40;
});

const fmt = computed(() => props.formatValue ?? ((v: number) => {
    if (v >= 1_000_000) return `₱${(v / 1_000_000).toFixed(1)}M`;
    if (v >= 1_000)     return `₱${(v / 1_000).toFixed(0)}K`;
    return v % 1 === 0 ? String(v) : `₱${v.toFixed(0)}`;
}));

const yTicks = computed(() => {
    const steps = 4;
    return Array.from({ length: steps + 1 }, (_, i) => {
        const val = (maxVal.value / steps) * i;
        const y   = PAD.top + (1 - val / maxVal.value) * innerH.value;
        return { val, y };
    });
});

const hovered = ref<number | null>(null);
</script>

<template>
    <div class="relative w-full">
        <svg
            :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
            preserveAspectRatio="xMidYMid meet"
            class="w-full"
            :style="{ height: `${HEIGHT}px` }"
        >
            <!-- Y-axis gridlines + labels -->
            <g v-for="tick in yTicks" :key="tick.val">
                <line
                    :x1="PAD.left" :y1="tick.y"
                    :x2="PAD.left + innerW" :y2="tick.y"
                    stroke="currentColor"
                    stroke-opacity="0.08"
                    stroke-width="1"
                />
                <text
                    :x="PAD.left - 6" :y="tick.y + 4"
                    text-anchor="end"
                    class="fill-muted-foreground"
                    style="font-size: 10px"
                >
                    {{ fmt(tick.val) }}
                </text>
            </g>

            <!-- Bars -->
            <g v-for="(d, i) in normalised" :key="i">
                <rect
                    :x="PAD.left + i * barGap + (barGap - barWidth) / 2"
                    :y="PAD.top + (1 - d.value / maxVal) * innerH"
                    :width="barWidth"
                    :height="(d.value / maxVal) * innerH"
                    :fill="d.color ?? color"
                    :opacity="hovered !== null && hovered !== i ? 0.4 : 1"
                    rx="3"
                    class="cursor-pointer transition-opacity duration-100"
                    @mouseenter="hovered = i"
                    @mouseleave="hovered = null"
                />

                <!-- X label -->
                <text
                    :x="PAD.left + i * barGap + barGap / 2"
                    :y="PAD.top + innerH + 18"
                    text-anchor="middle"
                    class="fill-muted-foreground"
                    style="font-size: 10px"
                >
                    {{ d.label }}
                </text>
            </g>
        </svg>

        <!-- Tooltip -->
        <Transition name="fade">
            <div
                v-if="hovered !== null"
                class="pointer-events-none absolute top-2 rounded-md border border-border bg-popover px-2.5 py-1.5 shadow-md"
                :style="{
                    left: `${((PAD.left + hovered * barGap + barGap / 2) / WIDTH) * 100}%`,
                    transform: 'translateX(-50%)',
                }"
            >
                <div class="text-[11px] font-semibold text-foreground">
                    {{ fmt(normalised[hovered]?.value ?? 0) }}
                </div>
                <div class="text-[10px] text-muted-foreground">
                    {{ normalised[hovered]?.label }}
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.1s;
}
.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
