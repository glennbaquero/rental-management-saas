<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    ArrowRight,
    CheckCircle2,
    Clock,
    Flame,
    Wrench,
} from '@lucide/vue';
import BarChart from '@/components/dashboard/BarChart.vue';
import DonutChart from '@/components/dashboard/DonutChart.vue';
import MaintenanceOverviewTable from '@/components/dashboard/MaintenanceOverviewTable.vue';
import QuickActions from '@/components/dashboard/QuickActions.vue';
import RecentActivities from '@/components/dashboard/RecentActivities.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import UpcomingEvents from '@/components/dashboard/UpcomingEvents.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { StaffDashboardProps } from '@/types/dashboard';

withDefaults(defineProps<StaffDashboardProps>(), {
    role:                   'staff',
    maintenance_stats:      () => ({ open: 0, assigned: 0, in_progress: 0, resolved: 0, emergency: 0, avg_resolution_hours: 0, total_cost_this_month: 0 }),
    assigned_tickets:       () => [],
    monthly_trend:          () => [],
    priority_distribution:  () => [],
    recent_activities:      () => [],
    upcoming_events:        () => [],
});
</script>

<template>
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Staff Dashboard</h1>
                <p class="text-sm text-muted-foreground">Your assigned tickets and task overview.</p>
            </div>
            <QuickActions role="staff" />
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            <StatCard
                label="Assigned to Me"
                :value="assigned_tickets.length"
                :icon="Wrench"
                icon-color="bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                subtitle="Active tickets"
            />
            <StatCard
                label="Open Tickets"
                :value="maintenance_stats.open"
                :icon="Clock"
                icon-color="bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"
            />
            <StatCard
                label="In Progress"
                :value="maintenance_stats.in_progress"
                :icon="Wrench"
                icon-color="bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
            />
            <StatCard
                label="Resolved"
                :value="maintenance_stats.resolved"
                :icon="CheckCircle2"
                icon-color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"
            />
            <StatCard
                label="Emergency"
                :value="maintenance_stats.emergency"
                :icon="Flame"
                icon-color="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
            />
        </div>

        <!-- Charts -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Monthly Request Trend</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :data="monthly_trend"
                        color="#6366f1"
                        :height="160"
                        :format-value="(v) => String(v)"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Priority Distribution</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :data="priority_distribution"
                        center-label="Tickets"
                        center-sublabel="By priority"
                        :size="130"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Assigned tickets -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-base">My Assigned Tickets</CardTitle>
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/maintenance">
                        View all <ArrowRight class="ml-1 h-3.5 w-3.5" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent class="p-0">
                <MaintenanceOverviewTable :tickets="assigned_tickets" />
            </CardContent>
        </Card>

        <!-- Activity + Events -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Recent Activity</CardTitle>
                </CardHeader>
                <CardContent>
                    <RecentActivities :activities="recent_activities" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Upcoming Events</CardTitle>
                </CardHeader>
                <CardContent>
                    <UpcomingEvents :events="upcoming_events" />
                </CardContent>
            </Card>
        </div>
    </div>
</template>
