<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, Wrench } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { MaintenanceOverviewItem } from '@/types/dashboard';

defineProps<{
    tickets: MaintenanceOverviewItem[];
}>();

function priorityClass(priority: string): string {
    const map: Record<string, string> = {
        emergency: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        urgent:    'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        high:      'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        medium:    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        low:       'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
    };
    return map[priority] ?? 'bg-muted text-muted-foreground';
}

function statusClass(status: string): string {
    const map: Record<string, string> = {
        open:              'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
        assigned:          'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        in_progress:       'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/30 dark:text-indigo-400',
        waiting_for_parts: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        on_hold:           'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400',
        resolved:          'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        completed:         'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    };
    return map[status] ?? 'bg-muted text-muted-foreground';
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Ticket #</TableHead>
                <TableHead>Issue</TableHead>
                <TableHead>Property / Unit</TableHead>
                <TableHead>Tenant</TableHead>
                <TableHead>Priority</TableHead>
                <TableHead>Assigned To</TableHead>
                <TableHead>Status</TableHead>
                <TableHead />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-if="tickets.length === 0">
                <TableCell colspan="8" class="py-10 text-center text-muted-foreground">
                    <Wrench class="mx-auto mb-2 h-8 w-8 opacity-30" />
                    No maintenance tickets
                </TableCell>
            </TableRow>
            <TableRow v-for="ticket in tickets" :key="ticket.id">
                <TableCell class="font-mono text-xs font-medium">{{ ticket.ticket_number }}</TableCell>
                <TableCell class="max-w-[160px]">
                    <div class="truncate text-sm font-medium">{{ ticket.title }}</div>
                </TableCell>
                <TableCell class="text-sm">
                    <div class="font-medium">{{ ticket.property_name }}</div>
                    <div class="text-xs text-muted-foreground">Unit {{ ticket.unit_number }}</div>
                </TableCell>
                <TableCell class="text-sm">{{ ticket.tenant_name }}</TableCell>
                <TableCell>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="priorityClass(ticket.priority)"
                    >
                        {{ ticket.priority_label }}
                    </span>
                </TableCell>
                <TableCell class="text-sm text-muted-foreground">{{ ticket.assigned_to }}</TableCell>
                <TableCell>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="statusClass(ticket.status)"
                    >
                        {{ ticket.status_label }}
                    </span>
                </TableCell>
                <TableCell>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="`/maintenance/${ticket.id}`">
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
