<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Building2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { PropertyOverviewItem } from '@/types/dashboard';

defineProps<{
    properties: PropertyOverviewItem[];
}>();

function occupancyBadgeClass(rate: number): string {
    if (rate >= 90) return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400';
    if (rate >= 70) return 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400';
    return 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400';
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(amount);
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Property</TableHead>
                <TableHead class="text-center">Units</TableHead>
                <TableHead class="text-center">Occupied</TableHead>
                <TableHead class="text-center">Vacant</TableHead>
                <TableHead class="text-center">Occupancy</TableHead>
                <TableHead class="text-right">Monthly Income</TableHead>
                <TableHead />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-if="properties.length === 0">
                <TableCell colspan="7" class="py-10 text-center text-muted-foreground">
                    <Building2 class="mx-auto mb-2 h-8 w-8 opacity-30" />
                    No properties found
                </TableCell>
            </TableRow>
            <TableRow v-for="prop in properties" :key="prop.id" class="group">
                <TableCell class="font-medium">{{ prop.name }}</TableCell>
                <TableCell class="text-center tabular-nums">{{ prop.total_units }}</TableCell>
                <TableCell class="text-center tabular-nums text-emerald-600 dark:text-emerald-400">
                    {{ prop.occupied_units }}
                </TableCell>
                <TableCell class="text-center tabular-nums text-amber-600 dark:text-amber-400">
                    {{ prop.vacant_units }}
                </TableCell>
                <TableCell class="text-center">
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="occupancyBadgeClass(prop.occupancy_rate)"
                    >
                        {{ prop.occupancy_rate }}%
                    </span>
                </TableCell>
                <TableCell class="text-right tabular-nums font-medium">
                    {{ formatCurrency(prop.monthly_income) }}
                </TableCell>
                <TableCell>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="`/properties/${prop.id}`">
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
