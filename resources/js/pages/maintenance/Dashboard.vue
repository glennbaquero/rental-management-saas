<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, AlertTriangle, Clock, CheckCircle2, Wrench, DollarSign, Zap } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MaintenanceStatusBadge from '@/components/maintenance/MaintenanceStatusBadge.vue';
import MaintenancePriorityBadge from '@/components/maintenance/MaintenancePriorityBadge.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type {
    MaintenanceDashboardStats,
    MaintenanceMonthlyPoint,
    MaintenancePriorityDistribution,
    MaintenanceCostByCategory,
    MaintenanceTicketRow,
} from '@/types/maintenance';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Maintenance', href: '/maintenance' },
            { title: 'Dashboard', href: '/maintenance/dashboard' },
        ],
    },
});

defineProps<{
    stats: MaintenanceDashboardStats;
    monthly_requests: MaintenanceMonthlyPoint[];
    priority_distribution: MaintenancePriorityDistribution[];
    cost_by_category: MaintenanceCostByCategory[];
    recent_tickets: MaintenanceTicketRow[];
}>();

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function formatHours(hours: number): string {
    if (hours < 1) return `${Math.round(hours * 60)}m`;
    if (hours < 24) return `${hours.toFixed(1)}h`;
    return `${(hours / 24).toFixed(1)}d`;
}

function formatDate(dt: string): string {
    return new Date(dt).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}
</script>

<template>
    <Head title="Maintenance Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <Heading title="Maintenance Dashboard" description="Track and manage all maintenance requests." />
            <Button as-child>
                <Link href="/maintenance/create">+ Create Ticket</Link>
            </Button>
        </div>

        <!-- Stat Cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-blue-100 p-2 dark:bg-blue-900/30">
                            <Wrench class="h-4 w-4 text-blue-600 dark:text-blue-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.open_count }}</p>
                            <p class="text-xs text-muted-foreground">Open</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-yellow-100 p-2 dark:bg-yellow-900/30">
                            <Clock class="h-4 w-4 text-yellow-600 dark:text-yellow-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.in_progress_count }}</p>
                            <p class="text-xs text-muted-foreground">In Progress</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-green-100 p-2 dark:bg-green-900/30">
                            <CheckCircle2 class="h-4 w-4 text-green-600 dark:text-green-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.resolved_this_month }}</p>
                            <p class="text-xs text-muted-foreground">Resolved (Month)</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-rose-100 p-2 dark:bg-rose-900/30">
                            <Zap class="h-4 w-4 text-rose-600 dark:text-rose-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ stats.emergency_count }}</p>
                            <p class="text-xs text-muted-foreground">Emergency</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900/30">
                            <Clock class="h-4 w-4 text-purple-600 dark:text-purple-400" />
                        </div>
                        <div>
                            <p class="text-2xl font-bold">{{ formatHours(stats.avg_resolution_hours) }}</p>
                            <p class="text-xs text-muted-foreground">Avg Resolution</p>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <Card>
                <CardContent class="pt-6">
                    <div class="flex items-center gap-3">
                        <div class="rounded-lg bg-orange-100 p-2 dark:bg-orange-900/30">
                            <DollarSign class="h-4 w-4 text-orange-600 dark:text-orange-400" />
                        </div>
                        <div>
                            <p class="text-lg font-bold">{{ formatCurrency(stats.total_cost_this_month) }}</p>
                            <p class="text-xs text-muted-foreground">Cost (Month)</p>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">
            <!-- Monthly Requests Chart -->
            <Card class="xl:col-span-2">
                <CardHeader>
                    <CardTitle class="text-base">Monthly Requests (Last 6 Months)</CardTitle>
                </CardHeader>
                <CardContent>
                    <div class="flex h-40 items-end gap-2">
                        <div
                            v-for="point in monthly_requests"
                            :key="point.month"
                            class="flex flex-1 flex-col items-center gap-1"
                        >
                            <span class="text-xs font-medium text-muted-foreground">{{ point.count }}</span>
                            <div
                                class="w-full rounded-t bg-primary/80 transition-all"
                                :style="{
                                    height: `${Math.max(4, (point.count / Math.max(...monthly_requests.map(p => p.count), 1)) * 128)}px`
                                }"
                            />
                            <span class="text-[10px] text-muted-foreground">{{ point.month.split(' ')[0] }}</span>
                        </div>
                    </div>
                </CardContent>
            </Card>

            <!-- Priority Distribution -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Priority Distribution</CardTitle>
                </CardHeader>
                <CardContent class="space-y-3">
                    <div
                        v-for="item in priority_distribution"
                        :key="item.priority"
                        class="flex items-center justify-between"
                    >
                        <MaintenancePriorityBadge :priority="item.priority" :label="item.label" />
                        <span class="text-sm font-semibold">{{ item.count }}</span>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Recent Tickets -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-base">Recent Tickets</CardTitle>
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/maintenance" class="flex items-center gap-1">
                        View all <ArrowRight class="h-3 w-3" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Ticket #</TableHead>
                            <TableHead>Title</TableHead>
                            <TableHead>Tenant</TableHead>
                            <TableHead>Priority</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>Assigned To</TableHead>
                            <TableHead>Date</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="recent_tickets.length === 0">
                            <TableCell colspan="7" class="py-8 text-center text-muted-foreground">
                                No tickets yet.
                            </TableCell>
                        </TableRow>
                        <TableRow
                            v-for="ticket in recent_tickets"
                            :key="ticket.id"
                            class="cursor-pointer hover:bg-muted/50"
                            @click="$inertia.visit(`/maintenance/${ticket.id}`)"
                        >
                            <TableCell class="font-mono text-xs">{{ ticket.ticket_number }}</TableCell>
                            <TableCell class="max-w-[200px] truncate font-medium">{{ ticket.title }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ ticket.tenant_name }}</TableCell>
                            <TableCell>
                                <MaintenancePriorityBadge
                                    :priority="ticket.priority"
                                    :label="ticket.priority_label"
                                    :icon="ticket.priority_icon"
                                />
                            </TableCell>
                            <TableCell>
                                <MaintenanceStatusBadge :status="ticket.status" :label="ticket.status_label" />
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ ticket.assigned_to }}</TableCell>
                            <TableCell class="text-sm text-muted-foreground">{{ formatDate(ticket.created_at) }}</TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    </div>
</template>
