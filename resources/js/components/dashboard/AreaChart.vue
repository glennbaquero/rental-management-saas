<script setup lang="ts">
import { computed, ref } from 'vue';
import type { TrendPoint } from '@/types/dashboard';

const props = withDefaults(defineProps<{
    data?: TrendPoint[];
    color?: string;
    height?: number;
    formatValue?: (v: number) => string;
}>(), { data: () => [] });

const color  = computed(() => props.color ?? '#6366f1');
const HEIGHT = computed(() => props.height ?? 200);
const WIDTH  = 560;
const PAD    = { top: 12, right: 16, bottom: 32, left: 56 };

const innerW = computed(() => WIDTH - PAD.left - PAD.right);
const innerH = computed(() => HEIGHT.value - PAD.top - PAD.bottom);

const values = computed(() => props.data.map(d => d.value));
const maxVal = computed(() => Math.max(...values.value, 1));

const fmt = computed(() => props.formatValue ?? ((v: number) => {
    if (v >= 1_000_000) return `₱${(v / 1_000_000).toFixed(1)}M`;
    if (v >= 1_000)     return `₱${(v / 1_000).toFixed(0)}K`;
    return `₱${v.toFixed(0)}`;
}));

const chartPoints = computed(() =>
    props.data.map((d, i) => ({
        x: PAD.left + (i / Math.max(props.data.length - 1, 1)) * innerW.value,
        y: PAD.top + (1 - d.value / maxVal.value) * innerH.value,
        label: d.label,
        value: d.value,
    })),
);

const polylinePts = computed(() =>
    chartPoints.value.map(p => `${p.x},${p.y}`).join(' '),
);

const areaPts = computed(() => {
    if (!chartPoints.value.length) return '';
    const first = chartPoints.value[0];
    const last  = chartPoints.value[chartPoints.value.length - 1];
    const bottom = PAD.top + innerH.value;
    return [
        `${first.x},${bottom}`,
        ...chartPoints.value.map(p => `${p.x},${p.y}`),
        `${last.x},${bottom}`,
    ].join(' ');
});

const yTicks = computed(() => {
    const steps = 4;
    return Array.from({ length: steps + 1 }, (_, i) => {
        const val = (maxVal.value / steps) * i;
        const y   = PAD.top + (1 - val / maxVal.value) * innerH.value;
        return { val, y };
    });
});

const hovered = ref<number | null>(null);
const uid = Math.random().toString(36).slice(2, 8);
</script>

<template>
    <div class="relative w-full overflow-hidden">
        <svg
            :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
            preserveAspectRatio="xMidYMid meet"
            class="w-full"
            :style="{ height: `${HEIGHT}px` }"
        >
            <defs>
                <linearGradient :id="`area-grad-${uid}`" x1="0" y1="0" x2="0" y2="1">
                    <stop offset="0%"   :stop-color="color" stop-opacity="0.25" />
                    <stop offset="100%" :stop-color="color" stop-opacity="0.02" />
                </linearGradient>
            </defs>

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
                    class="fill-muted-foreground text-[10px]"
                    style="font-size: 10px"
                >
                    {{ fmt(tick.val) }}
                </text>
            </g>

            <!-- Area fill -->
            <polygon
                v-if="areaPts"
                :points="areaPts"
                :fill="`url(#area-grad-${uid})`"
            />

            <!-- Line -->
            <polyline
                v-if="polylinePts"
                :points="polylinePts"
                fill="none"
                :stroke="color"
                stroke-width="2"
                stroke-linecap="round"
                stroke-linejoin="round"
            />

            <!-- Data points + hover zones -->
            <g v-for="(pt, i) in chartPoints" :key="i">
                <!-- Hover zone -->
                <rect
                    :x="pt.x - (innerW / chartPoints.length / 2)"
                    :y="PAD.top"
                    :width="innerW / chartPoints.length"
                    :height="innerH"
                    fill="transparent"
                    @mouseenter="hovered = i"
                    @mouseleave="hovered = null"
                    class="cursor-crosshair"
                />

                <!-- Dot -->
                <circle
                    :cx="pt.x"
                    :cy="pt.y"
                    :r="hovered === i ? 5 : 3"
                    :fill="color"
                    :stroke="hovered === i ? 'white' : 'transparent'"
                    stroke-width="2"
                    class="transition-all duration-100"
                />

                <!-- Tooltip line -->
                <line
                    v-if="hovered === i"
                    :x1="pt.x" :y1="PAD.top"
                    :x2="pt.x" :y2="PAD.top + innerH"
                    :stroke="color"
                    stroke-opacity="0.3"
                    stroke-width="1"
                    stroke-dasharray="4 2"
                />

                <!-- X-axis label -->
                <text
                    :x="pt.x"
                    :y="PAD.top + innerH + 18"
                    text-anchor="middle"
                    class="fill-muted-foreground"
                    style="font-size: 10px"
                >
                    {{ pt.label }}
                </text>
            </g>
        </svg>

        <!-- Floating tooltip -->
        <Transition name="fade">
            <div
                v-if="hovered !== null"
                class="pointer-events-none absolute top-2 rounded-md border border-border bg-popover px-2.5 py-1.5 shadow-md"
                :style="{
                    left: `${(chartPoints[hovered]?.x / WIDTH) * 100}%`,
                    transform: 'translateX(-50%)',
                }"
            >
                <div class="text-[11px] font-semibold text-foreground">
                    {{ fmt(chartPoints[hovered]?.value ?? 0) }}
                </div>
                <div class="text-[10px] text-muted-foreground">
                    {{ data[hovered]?.month }}
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
