<script setup lang="ts">
import { computed } from 'vue';
import type { RevenueTrendPoint } from '@/types/billing';

const props = defineProps<{
    data: RevenueTrendPoint[];
    currency?: string;
}>();

const max = computed(() => Math.max(...props.data.map(d => d.revenue), 1));

function barHeight(revenue: number): string {
    return `${Math.max((revenue / max.value) * 100, 2)}%`;
}

function formatShort(amount: number): string {
    if (amount >= 1_000_000) return `₱${(amount / 1_000_000).toFixed(1)}M`;
    if (amount >= 1_000)     return `₱${(amount / 1_000).toFixed(0)}K`;
    return `₱${amount.toFixed(0)}`;
}
</script>

<template>
    <div class="flex h-48 items-end gap-2 px-2">
        <div
            v-for="point in data"
            :key="point.month"
            class="group relative flex flex-1 flex-col items-center gap-1"
        >
            <div class="absolute -top-6 left-1/2 hidden -translate-x-1/2 whitespace-nowrap rounded bg-popover px-2 py-1 text-xs shadow group-hover:block">
                {{ formatShort(point.revenue) }}
            </div>
            <div
                class="w-full rounded-t-md bg-primary/80 transition-all group-hover:bg-primary"
                :style="{ height: barHeight(point.revenue) }"
            />
            <span class="text-[10px] text-muted-foreground">{{ point.month.split(' ')[0] }}</span>
        </div>
    </div>
</template>
