<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Clock, FileText } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { LeaseOverviewItem } from '@/types/dashboard';

defineProps<{
    leases: LeaseOverviewItem[];
}>();

function statusClass(status: string): string {
    const map: Record<string, string> = {
        active: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        expiring_soon: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        expired: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        renewed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        terminated: 'bg-muted text-muted-foreground',
    };
    return map[status] ?? 'bg-muted text-muted-foreground';
}

function daysClass(days: number): string {
    if (days <= 7)  return 'text-red-600 dark:text-red-400 font-semibold';
    if (days <= 30) return 'text-amber-600 dark:text-amber-400';
    return 'text-muted-foreground';
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Lease #</TableHead>
                <TableHead>Tenant</TableHead>
                <TableHead>Property / Unit</TableHead>
                <TableHead>End Date</TableHead>
                <TableHead class="text-center">Days Left</TableHead>
                <TableHead>Status</TableHead>
                <TableHead />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-if="leases.length === 0">
                <TableCell colspan="7" class="py-10 text-center text-muted-foreground">
                    <FileText class="mx-auto mb-2 h-8 w-8 opacity-30" />
                    No leases found
                </TableCell>
            </TableRow>
            <TableRow v-for="lease in leases" :key="lease.id">
                <TableCell class="font-mono text-sm font-medium">{{ lease.lease_number }}</TableCell>
                <TableCell>{{ lease.tenant_name }}</TableCell>
                <TableCell class="text-sm">
                    <div class="font-medium">{{ lease.property_name }}</div>
                    <div class="text-xs text-muted-foreground">Unit {{ lease.unit_number }}</div>
                </TableCell>
                <TableCell class="text-sm">{{ lease.end_date }}</TableCell>
                <TableCell class="text-center">
                    <span class="flex items-center justify-center gap-1 text-sm" :class="daysClass(lease.days_remaining)">
                        <Clock class="h-3 w-3" />
                        {{ lease.days_remaining }}d
                    </span>
                </TableCell>
                <TableCell>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="statusClass(lease.status)"
                    >
                        {{ lease.status_label }}
                    </span>
                </TableCell>
                <TableCell>
                    <div class="flex items-center gap-1">
                        <Button variant="ghost" size="sm" as-child>
                            <Link :href="`/leases/${lease.id}`">
                                <ArrowRight class="h-3.5 w-3.5" />
                            </Link>
                        </Button>
                    </div>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
