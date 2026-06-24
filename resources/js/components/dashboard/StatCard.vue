<script setup lang="ts">
import { computed } from 'vue';
import { TrendingDown, TrendingUp } from '@lucide/vue';
import MiniSparkline from '@/components/dashboard/MiniSparkline.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { Component } from 'vue';

const props = defineProps<{
    label: string;
    value: string | number;
    icon: Component;
    iconColor?: string;
    changePct?: number;
    trend?: number[];
    trendColor?: string;
    subtitle?: string;
}>();

const changeDir = computed(() => {
    if (props.changePct === undefined || props.changePct === null) return null;
    return props.changePct >= 0 ? 'up' : 'down';
});

const changeDisplay = computed(() => {
    if (props.changePct === undefined || props.changePct === null) return null;
    const abs = Math.abs(props.changePct);
    return `${abs.toFixed(1)}%`;
});
</script>

<template>
    <Card class="relative overflow-hidden">
        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle class="text-sm font-medium text-muted-foreground">{{ label }}</CardTitle>
            <div
                class="flex h-8 w-8 items-center justify-center rounded-lg"
                :class="iconColor ?? 'bg-primary/10 text-primary'"
            >
                <component :is="icon" class="h-4 w-4" />
            </div>
        </CardHeader>

        <CardContent>
            <div class="flex items-end justify-between gap-2">
                <div>
                    <div class="text-2xl font-bold tracking-tight">{{ value }}</div>
                    <p v-if="subtitle" class="mt-0.5 text-xs text-muted-foreground">{{ subtitle }}</p>

                    <div v-if="changeDir !== null" class="mt-1.5 flex items-center gap-1">
                        <span
                            class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 text-[11px] font-medium"
                            :class="changeDir === 'up'
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'
                                : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'"
                        >
                            <TrendingUp v-if="changeDir === 'up'" class="h-3 w-3" />
                            <TrendingDown v-else class="h-3 w-3" />
                            {{ changeDisplay }}
                        </span>
                        <span class="text-[11px] text-muted-foreground">vs last month</span>
                    </div>
                </div>

                <MiniSparkline
                    v-if="trend && trend.length >= 2"
                    :data="trend"
                    :color="trendColor ?? (changeDir === 'down' ? '#ef4444' : '#22c55e')"
                    class="shrink-0 opacity-80"
                />
            </div>
        </CardContent>
    </Card>
</template>
