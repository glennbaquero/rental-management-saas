<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    AlertTriangle,
    ArrowRight,
    Building2,
    CreditCard,
    DoorOpen,
    PercentCircle,
    TrendingUp,
    Users,
    Wallet,
    Wrench,
} from '@lucide/vue';
import AreaChart from '@/components/dashboard/AreaChart.vue';
import DonutChart from '@/components/dashboard/DonutChart.vue';
import LeaseOverviewTable from '@/components/dashboard/LeaseOverviewTable.vue';
import MaintenanceOverviewTable from '@/components/dashboard/MaintenanceOverviewTable.vue';
import PropertyOverviewTable from '@/components/dashboard/PropertyOverviewTable.vue';
import QuickActions from '@/components/dashboard/QuickActions.vue';
import RecentActivities from '@/components/dashboard/RecentActivities.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import UpcomingEvents from '@/components/dashboard/UpcomingEvents.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { OwnerDashboardProps } from '@/types/dashboard';

const props = withDefaults(defineProps<OwnerDashboardProps>(), {
    role:                    'owner',
    monthly_revenue:         0,
    monthly_revenue_change:  0,
    outstanding_balance:     0,
    overdue_count:           0,
    rent_collection_rate:    0,
    deposits_held:           0,
    property_stats:          () => ({ total_properties: 0, total_units: 0, occupied_units: 0, vacant_units: 0, occupancy_rate: 0 }),
    lease_stats:             () => ({ active: 0, expiring_soon: 0, expiring_this_month: 0, expired: 0, renewed: 0, terminated: 0 }),
    maintenance_stats:       () => ({ open: 0, assigned: 0, in_progress: 0, resolved: 0, emergency: 0, avg_resolution_hours: 0, total_cost_this_month: 0 }),
    revenue_trend:           () => [],
    occupancy_trend:         () => [],
    property_occupancy:      () => [],
    expiring_leases:         () => [],
    recent_tickets:          () => [],
    revenue_by_property:     () => [],
    recent_activities:       () => [],
    upcoming_events:         () => [],
});

function formatCurrency(amount: number): string {
    if (amount >= 1_000_000) return `₱${(amount / 1_000_000).toFixed(2)}M`;
    if (amount >= 1_000)     return `₱${(amount / 1_000).toFixed(1)}K`;
    return `₱${amount.toFixed(2)}`;
}

const revenueTrendValues = computed(() => (props.revenue_trend ?? []).map(d => d.value));
</script>

<template>
    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-xl font-semibold tracking-tight">Owner Dashboard</h1>
                <p class="text-sm text-muted-foreground">Your portfolio overview at a glance.</p>
            </div>
            <QuickActions role="owner" />
        </div>

        <!-- Summary cards — row 1 -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Total Properties"
                :value="property_stats.total_properties"
                :icon="Building2"
                icon-color="bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
                subtitle="Active portfolio"
            />
            <StatCard
                label="Total Units"
                :value="property_stats.total_units"
                :icon="DoorOpen"
                icon-color="bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400"
                subtitle="Across all properties"
            />
            <StatCard
                label="Occupied Units"
                :value="property_stats.occupied_units"
                :icon="Users"
                icon-color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"
                subtitle="Currently rented"
            />
            <StatCard
                label="Occupancy Rate"
                :value="`${property_stats.occupancy_rate}%`"
                :icon="PercentCircle"
                icon-color="bg-teal-100 text-teal-600 dark:bg-teal-900/30 dark:text-teal-400"
                :change-pct="property_stats.occupancy_rate - 80"
                subtitle="vs 80% benchmark"
            />
        </div>

        <!-- Summary cards — row 2 -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <StatCard
                label="Monthly Revenue"
                :value="formatCurrency(monthly_revenue)"
                :icon="TrendingUp"
                icon-color="bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400"
                :change-pct="monthly_revenue_change"
                :trend="revenueTrendValues"
                subtitle="This month"
            />
            <StatCard
                label="Outstanding Balance"
                :value="formatCurrency(outstanding_balance)"
                :icon="Wallet"
                icon-color="bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"
                subtitle="Unpaid invoices"
            />
            <StatCard
                label="Overdue Invoices"
                :value="overdue_count"
                :icon="AlertTriangle"
                icon-color="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
                subtitle="Require attention"
            />
            <StatCard
                label="Open Maintenance"
                :value="maintenance_stats.open + maintenance_stats.in_progress"
                :icon="Wrench"
                icon-color="bg-orange-100 text-orange-600 dark:bg-orange-900/30 dark:text-orange-400"
                subtitle="Active tickets"
            />
        </div>

        <!-- Charts row -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Revenue trend -->
            <Card class="lg:col-span-2">
                <CardHeader>
                    <CardTitle class="text-base">Revenue Trend (6 Months)</CardTitle>
                </CardHeader>
                <CardContent>
                    <AreaChart
                        :data="revenue_trend"
                        color="#6366f1"
                        :height="200"
                    />
                </CardContent>
            </Card>

            <!-- Occupancy donut -->
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Occupancy Rate</CardTitle>
                </CardHeader>
                <CardContent>
                    <DonutChart
                        :data="[
                            { label: 'Occupied', value: property_stats.occupied_units, color: '#22c55e' },
                            { label: 'Vacant',   value: property_stats.vacant_units,   color: '#f59e0b' },
                        ]"
                        :center-label="`${property_stats.occupancy_rate}%`"
                        center-sublabel="Occupancy"
                    />

                    <!-- Rent collection rate -->
                    <div class="mt-4 rounded-lg bg-muted/50 p-3 text-center">
                        <div class="text-xs text-muted-foreground">Rent Collection Rate</div>
                        <div class="mt-0.5 text-lg font-bold">
                            <span :class="rent_collection_rate >= 90 ? 'text-emerald-600' : 'text-amber-600'">
                                {{ rent_collection_rate }}%
                            </span>
                        </div>
                    </div>
                </CardContent>
            </Card>
        </div>

        <!-- Property overview -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-base">Property Overview</CardTitle>
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/properties">
                        View all <ArrowRight class="ml-1 h-3.5 w-3.5" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent class="p-0">
                <PropertyOverviewTable :properties="property_occupancy.slice(0, 6)" />
            </CardContent>
        </Card>

        <!-- Lease + Maintenance row -->
        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">Expiring Leases</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link href="/leases">
                            View all <ArrowRight class="ml-1 h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <LeaseOverviewTable :leases="expiring_leases" />
                </CardContent>
            </Card>

            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">Recent Maintenance</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link href="/maintenance">
                            View all <ArrowRight class="ml-1 h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <MaintenanceOverviewTable :tickets="recent_tickets" />
                </CardContent>
            </Card>
        </div>

        <!-- Activity + Events row -->
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
