<script setup lang="ts">
import { AlertTriangle, ArrowUpRight, CheckCircle2, Clock, TrendingUp, Wallet } from '@lucide/vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BillingStats } from '@/types/billing';

defineProps<{
    stats: BillingStats;
    currency?: string;
}>();

function formatCurrency(amount: number, currency = 'PHP'): string {
    return new Intl.NumberFormat('en-PH', {
        style: 'currency',
        currency,
        minimumFractionDigits: 2,
    }).format(amount);
}
</script>

<template>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3">
        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">Monthly Revenue</CardTitle>
                <TrendingUp class="h-4 w-4 text-emerald-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(stats.monthly_revenue, currency) }}</div>
                <p class="mt-1 text-xs text-muted-foreground">Collected this month</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">Outstanding Balance</CardTitle>
                <Wallet class="h-4 w-4 text-amber-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(stats.outstanding_balance, currency) }}</div>
                <p class="mt-1 text-xs text-muted-foreground">Unpaid invoices</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">Paid Invoices</CardTitle>
                <CheckCircle2 class="h-4 w-4 text-blue-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.paid_invoices_count }}</div>
                <p class="mt-1 text-xs text-muted-foreground">This month</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">Overdue Invoices</CardTitle>
                <AlertTriangle class="h-4 w-4 text-red-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400">{{ stats.overdue_count }}</div>
                <p class="mt-1 text-xs text-muted-foreground">Require attention</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">Payments Collected</CardTitle>
                <ArrowUpRight class="h-4 w-4 text-emerald-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ stats.collected_payments }}</div>
                <p class="mt-1 text-xs text-muted-foreground">Transactions this month</p>
            </CardContent>
        </Card>

        <Card>
            <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                <CardTitle class="text-sm font-medium text-muted-foreground">Late Fees Collected</CardTitle>
                <Clock class="h-4 w-4 text-orange-500" />
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(stats.late_fees_collected, currency) }}</div>
                <p class="mt-1 text-xs text-muted-foreground">This month</p>
            </CardContent>
        </Card>
    </div>
</template>
