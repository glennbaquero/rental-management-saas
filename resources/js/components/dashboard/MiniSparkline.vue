<script setup lang="ts">
import { computed } from 'vue';

const props = withDefaults(defineProps<{
    data?: number[];
    color?: string;
    filled?: boolean;
}>(), { data: () => [] });

const color = computed(() => props.color ?? 'var(--color-primary)');

const WIDTH  = 64;
const HEIGHT = 24;
const PAD    = 2;

const points = computed(() => {
    const vals = props.data;
    if (!vals || vals.length < 2) return [];

    const min = Math.min(...vals);
    const max = Math.max(...vals);
    const range = max - min || 1;

    return vals.map((v, i) => {
        const x = PAD + ((i / (vals.length - 1)) * (WIDTH - PAD * 2));
        const y = PAD + ((1 - (v - min) / range) * (HEIGHT - PAD * 2));
        return { x, y };
    });
});

const polylinePoints = computed(() =>
    points.value.map(p => `${p.x},${p.y}`).join(' '),
);

const areaPoints = computed(() => {
    if (!points.value.length) return '';
    const last = points.value[points.value.length - 1];
    const first = points.value[0];
    return [
        `${first.x},${HEIGHT}`,
        ...points.value.map(p => `${p.x},${p.y}`),
        `${last.x},${HEIGHT}`,
    ].join(' ');
});
</script>

<template>
    <svg
        :width="WIDTH"
        :height="HEIGHT"
        :viewBox="`0 0 ${WIDTH} ${HEIGHT}`"
        preserveAspectRatio="none"
        aria-hidden="true"
    >
        <defs>
            <linearGradient :id="`spark-grad-${$.uid}`" x1="0" y1="0" x2="0" y2="1">
                <stop offset="0%" :stop-color="color" stop-opacity="0.3" />
                <stop offset="100%" :stop-color="color" stop-opacity="0" />
            </linearGradient>
        </defs>

        <polygon
            v-if="filled !== false && areaPoints"
            :points="areaPoints"
            :fill="`url(#spark-grad-${$.uid})`"
        />

        <polyline
            v-if="polylinePoints"
            :points="polylinePoints"
            fill="none"
            :stroke="color"
            stroke-width="1.5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
