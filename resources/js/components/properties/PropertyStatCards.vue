<script setup lang="ts">
import { computed } from 'vue';
import { Building2, DoorOpen, CheckCircle2, Users, TrendingUp } from '@lucide/vue';
import { Card, CardContent } from '@/components/ui/card';

const props = defineProps<{
    totalBuildings: number;
    totalUnits: number;
    availableUnits: number;
    occupiedUnits: number;
    monthlyRevenue: number;
}>();

const cards = computed(() => [
    {
        label: 'Total Buildings',
        value: props.totalBuildings,
        icon:  Building2,
        color: 'text-blue-600',
        bg:    'bg-blue-50 dark:bg-blue-950/30',
    },
    {
        label: 'Total Units',
        value: props.totalUnits,
        icon:  DoorOpen,
        color: 'text-violet-600',
        bg:    'bg-violet-50 dark:bg-violet-950/30',
    },
    {
        label: 'Available Units',
        value: props.availableUnits,
        icon:  CheckCircle2,
        color: 'text-green-600',
        bg:    'bg-green-50 dark:bg-green-950/30',
    },
    {
        label: 'Occupied Units',
        value: props.occupiedUnits,
        icon:  Users,
        color: 'text-orange-600',
        bg:    'bg-orange-50 dark:bg-orange-950/30',
    },
    {
        label: 'Monthly Revenue',
        value: `₱${props.monthlyRevenue.toLocaleString('en-PH', { minimumFractionDigits: 0 })}`,
        icon:  TrendingUp,
        color: 'text-emerald-600',
        bg:    'bg-emerald-50 dark:bg-emerald-950/30',
    },
]);
</script>

<template>
    <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
        <Card v-for="card in cards" :key="card.label" class="border-border/50">
            <CardContent class="p-4">
                <div class="flex items-start justify-between">
                    <div>
                        <p class="text-xs font-medium text-muted-foreground">{{ card.label }}</p>
                        <p class="mt-1 text-2xl font-semibold tracking-tight">{{ card.value }}</p>
                    </div>
                    <div :class="['rounded-lg p-2', card.bg]">
                        <component :is="card.icon" :class="['h-5 w-5', card.color]" />
                    </div>
                </div>
            </CardContent>
        </Card>
    </div>
</template>
