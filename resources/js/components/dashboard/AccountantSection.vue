<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    AlertTriangle,
    ArrowRight,
    CreditCard,
    PercentCircle,
    Shield,
    TrendingUp,
    Wallet,
} from '@lucide/vue';
import AreaChart from '@/components/dashboard/AreaChart.vue';
import BarChart from '@/components/dashboard/BarChart.vue';
import DonutChart from '@/components/dashboard/DonutChart.vue';
import InvoiceOverviewTable from '@/components/dashboard/InvoiceOverviewTable.vue';
import QuickActions from '@/components/dashboard/QuickActions.vue';
import RecentActivities from '@/components/dashboard/RecentActivities.vue';
import StatCard from '@/components/dashboard/StatCard.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { AccountantDashboardProps } from '@/types/dashboard';

const props = withDefaults(defineProps<AccountantDashboardProps>(), {
    role:                       'accountant',
    monthly_revenue:            0,
    monthly_revenue_change:     0,
    outstanding_balance:        0,
    overdue_count:              0,
    deposits_held:              0,
    rent_collection_rate:       0,
    revenue_trend:              () => [],
    payment_method_breakdown:   () => [],
    revenue_by_property:        () => [],
    recent_invoices:            () => [],
    recent_activities:          () => [],
    upcoming_events:            () => [],
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
                <h1 class="text-xl font-semibold tracking-tight">Accountant Dashboard</h1>
                <p class="text-sm text-muted-foreground">Financial overview, revenue, and billing summary.</p>
            </div>
            <QuickActions role="accountant" />
        </div>

        <!-- Stat cards -->
        <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            <StatCard
                label="Monthly Revenue"
                :value="formatCurrency(monthly_revenue)"
                :icon="TrendingUp"
                icon-color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/30 dark:text-emerald-400"
                :change-pct="monthly_revenue_change"
                :trend="revenueTrendValues"
            />
            <StatCard
                label="Outstanding"
                :value="formatCurrency(outstanding_balance)"
                :icon="Wallet"
                icon-color="bg-amber-100 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400"
            />
            <StatCard
                label="Overdue Invoices"
                :value="overdue_count"
                :icon="AlertTriangle"
                icon-color="bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400"
            />
            <StatCard
                label="Deposits Held"
                :value="formatCurrency(deposits_held)"
                :icon="Shield"
                icon-color="bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400"
            />
            <StatCard
                label="Collection Rate"
                :value="`${rent_collection_rate}%`"
                :icon="PercentCircle"
                icon-color="bg-violet-100 text-violet-600 dark:bg-violet-900/30 dark:text-violet-400"
            />
            <StatCard
                label="Invoices"
                :value="recent_invoices.length"
                :icon="CreditCard"
                icon-color="bg-indigo-100 text-indigo-600 dark:bg-indigo-900/30 dark:text-indigo-400"
                subtitle="Recent"
            />
        </div>

        <!-- Revenue charts -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Monthly Revenue (6 Months)</CardTitle>
                </CardHeader>
                <CardContent>
                    <AreaChart
                        :data="revenue_trend"
                        color="#22c55e"
                        :height="180"
                    />
                </CardContent>
            </Card>

            <Card>
                <CardHeader>
                    <CardTitle class="text-base">Revenue by Property</CardTitle>
                </CardHeader>
                <CardContent>
                    <BarChart
                        :data="revenue_by_property"
                        color="#6366f1"
                        :height="180"
                    />
                </CardContent>
            </Card>
        </div>

        <!-- Payment method breakdown -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Payments by Method (This Month)</CardTitle>
            </CardHeader>
            <CardContent>
                <DonutChart
                    :data="payment_method_breakdown"
                    center-label="Methods"
                    center-sublabel="This month"
                    :size="130"
                />
            </CardContent>
        </Card>

        <!-- Invoice table -->
        <Card>
            <CardHeader class="flex flex-row items-center justify-between">
                <CardTitle class="text-base">Recent Invoices</CardTitle>
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/billing/invoices">
                        View all <ArrowRight class="ml-1 h-3.5 w-3.5" />
                    </Link>
                </Button>
            </CardHeader>
            <CardContent class="p-0">
                <InvoiceOverviewTable :invoices="recent_invoices" />
            </CardContent>
        </Card>

        <!-- Activities -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Recent Activity</CardTitle>
            </CardHeader>
            <CardContent>
                <RecentActivities :activities="recent_activities" />
            </CardContent>
        </Card>
    </div>
</template>

