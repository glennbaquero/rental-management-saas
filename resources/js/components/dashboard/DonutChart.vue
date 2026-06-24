<script setup lang="ts">
import { computed, ref } from 'vue';
import type { ChartDataPoint } from '@/types/dashboard';

const props = withDefaults(defineProps<{
    data?: ChartDataPoint[];
    centerLabel?: string;
    centerSublabel?: string;
    size?: number;
    strokeWidth?: number;
}>(), { data: () => [] });

const SIZE   = computed(() => props.size ?? 140);
const SW     = computed(() => props.strokeWidth ?? 22);
const R      = computed(() => (SIZE.value - SW.value) / 2);
const CX     = computed(() => SIZE.value / 2);
const CY     = computed(() => SIZE.value / 2);
const CIRC   = computed(() => 2 * Math.PI * R.value);

const total  = computed(() => props.data.reduce((s, d) => s + d.value, 0) || 1);

interface Segment {
    label: string;
    value: number;
    pct: number;
    color: string;
    dasharray: string;
    dashoffset: string;
}

const segments = computed<Segment[]>(() => {
    let offset = 0;
    return props.data.map(d => {
        const pct  = d.value / total.value;
        const dash = pct * CIRC.value;
        const gap  = CIRC.value - dash;
        const seg: Segment = {
            label:      d.label,
            value:      d.value,
            pct:        Math.round(pct * 100),
            color:      d.color ?? '#6366f1',
            dasharray:  `${dash} ${gap}`,
            dashoffset: String(-offset),
        };
        offset += dash;
        return seg;
    });
});

const hovered = ref<number | null>(null);
</script>

<template>
    <div class="flex flex-col items-center gap-4 sm:flex-row sm:items-start">
        <!-- Donut -->
        <div class="relative shrink-0" :style="{ width: `${SIZE}px`, height: `${SIZE}px` }">
            <svg :width="SIZE" :height="SIZE" :viewBox="`0 0 ${SIZE} ${SIZE}`" class="-rotate-90">
                <!-- Background track -->
                <circle
                    :cx="CX" :cy="CY" :r="R"
                    fill="none"
                    stroke="currentColor"
                    stroke-opacity="0.08"
                    :stroke-width="SW"
                />

                <!-- Segments -->
                <circle
                    v-for="(seg, i) in segments"
                    :key="i"
                    :cx="CX" :cy="CY" :r="R"
                    fill="none"
                    :stroke="seg.color"
                    :stroke-width="hovered === i ? SW + 3 : SW"
                    :stroke-dasharray="seg.dasharray"
                    :stroke-dashoffset="seg.dashoffset"
                    stroke-linecap="butt"
                    class="cursor-pointer transition-all duration-150"
                    @mouseenter="hovered = i"
                    @mouseleave="hovered = null"
                />
            </svg>

            <!-- Center text -->
            <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <span v-if="hovered !== null" class="text-base font-bold leading-tight">
                    {{ segments[hovered]?.pct }}%
                </span>
                <span v-else-if="centerLabel" class="text-base font-bold leading-tight">
                    {{ centerLabel }}
                </span>
                <span v-if="hovered !== null" class="mt-0.5 text-[10px] text-muted-foreground">
                    {{ segments[hovered]?.label }}
                </span>
                <span v-else-if="centerSublabel" class="mt-0.5 text-[10px] text-muted-foreground">
                    {{ centerSublabel }}
                </span>
            </div>
        </div>

        <!-- Legend -->
        <ul class="flex min-w-0 flex-1 flex-col gap-1.5">
            <li
                v-for="(seg, i) in segments"
                :key="i"
                class="flex items-center gap-2 text-sm transition-opacity duration-100"
                :class="hovered !== null && hovered !== i ? 'opacity-40' : ''"
                @mouseenter="hovered = i"
                @mouseleave="hovered = null"
            >
                <span
                    class="h-2.5 w-2.5 shrink-0 rounded-full"
                    :style="{ backgroundColor: seg.color }"
                />
                <span class="min-w-0 truncate text-muted-foreground">{{ seg.label }}</span>
                <span class="ml-auto shrink-0 font-medium tabular-nums">{{ seg.pct }}%</span>
            </li>
        </ul>
    </div>
</template>
