<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building2,
    CalendarX,
    DoorOpen,
    FileText,
    LogOut,
    PercentCircle,
    UserRound,
    Wrench,
} from '@lucide/vue';
import LeaseOverviewTable from '@/components/dashboard/LeaseOverviewTable.vue';
import MaintenanceOverviewTable from '@/components/dashboard/MaintenanceOverviewTable.vue';
import PropertyOverviewTable from '@/components/dashboard/PropertyOverviewTable.vue';
import QuickActions from '@/components/dashboard/QuickActions.vue';
import RecentActivities from '@/components/dashboard/RecentActivities.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import UpcomingEvents from '@/components/dashboard/UpcomingEvents.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { PropertyManagerDashboardProps } from '@/types/dashboard';

withDefaults(defineProps<PropertyManagerDashboardProps>(), {
    role:              'property_manager',
    tenant_count:      0,
    property_stats:    () => ({ total_properties: 0, total_units: 0, occupied_units: 0, vacant_units: 0, occupancy_rate: 0 }),
    lease_stats:       () => ({ active: 0, expiring_soon: 0, expiring_this_month: 0, expired: 0, renewed: 0, terminated: 0 }),
    maintenance_stats: () => ({ open: 0, assigned: 0, in_progress: 0, resolved: 0, emergency: 0, avg_resolution_hours: 0, total_cost_this_month: 0 }),
    occupancy_stats:   () => ({ total_units: 0, occupied_units: 0, vacant_units: 0, occupancy_rate: 0 }),
    expiring_leases:   () => [],
    upcoming_move_outs: () => [],
    recent_tickets:    () => [],
    property_occupancy: () => [],
    recent_activities: () => [],
    upcoming_events:   () => [],
});
</script>

<template>
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Property Manager Dashboard</h1>
                <p class="text-sm text-muted-foreground">Manage your properties, tenants, and requests.</p>
            </div>
            <QuickActions role="property_manager" />
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <StatCard
                label="Properties"
                :value="property_stats.total_properties"
                :icon="Building2"
                icon-color="bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
            />
            <StatCard
                label="Total Tenants"
                :value="tenant_count"
                :icon="UserRound"
                icon-color="bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400"
            />
            <StatCard
                label="Expiring Leases"
                :value="lease_stats.expiring_soon"
                :icon="CalendarX"
                icon-color="bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"
                subtitle="Next 30 days"
            />
            <StatCard
                label="Open Tickets"
                :value="maintenance_stats.open"
                :icon="Wrench"
                icon-color="bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
            />
            <StatCard
                label="Upcoming Move-Outs"
                :value="upcoming_move_outs.length"
                :icon="LogOut"
                icon-color="bg-rose-100 text-rose-600 dark:bg-rose-900/30 dark:text-rose-400"
            />
            <StatCard
                label="Occupancy Rate"
                :value="`${occupancy_stats.occupancy_rate}%`"
                :icon="PercentCircle"
                icon-color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"
            />
        </div>

        <!-- Property overview -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-base">Property Occupancy</CardTitle>
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/properties">View all <ArrowRight class="ml-1 h-3.5 w-3.5" /></Link>
                </Button>
            </CardHeader>
            <CardContent class="p-0">
                <PropertyOverviewTable :properties="property_occupancy.slice(0, 5)" />
            </CardContent>
        </Card>

        <!-- Expiring leases + Upcoming move outs -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">Expiring Leases</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link href="/leases">View all <ArrowRight class="ml-1 h-3.5 w-3.5" /></Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <LeaseOverviewTable :leases="expiring_leases" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Upcoming Move-Outs</CardTitle>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tenant</TableHead>
                                <TableHead>Property</TableHead>
                                <TableHead>Unit</TableHead>
                                <TableHead>Move-Out Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="upcoming_move_outs.length === 0">
                                <TableCell colspan="4" class="py-8 text-center text-muted-foreground">
                                    No upcoming move-outs
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="mu in upcoming_move_outs" :key="mu.id">
                                <TableCell>{{ mu.tenant_name }}</TableCell>
                                <TableCell class="text-sm">{{ mu.property_name }}</TableCell>
                                <TableCell class="text-sm">{{ mu.unit_number }}</TableCell>
                                <TableCell class="text-sm font-medium">{{ mu.move_out_date }}</TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>

        <!-- Maintenance + Activity -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">Open Maintenance Tickets</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link href="/maintenance">View all <ArrowRight class="ml-1 h-3.5 w-3.5" /></Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <MaintenanceOverviewTable :tickets="recent_tickets" />
                </CardContent>
            </Card>

            <div class="flex flex-col gap-6">
                <Card class="flex-1">
                    <CardHeader>
                        <CardTitle class="text-base">Recent Activity</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <RecentActivities :activities="recent_activities.slice(0, 5)" />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Upcoming Events</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <UpcomingEvents :events="upcoming_events.slice(0, 4)" />
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>
</template>
