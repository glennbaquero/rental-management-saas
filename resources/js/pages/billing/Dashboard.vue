<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import BillingStatCards from '@/components/billing/BillingStatCards.vue';
import InvoiceStatusBadge from '@/components/billing/InvoiceStatusBadge.vue';
import PaymentMethodBadge from '@/components/billing/PaymentMethodBadge.vue';
import RevenueChart from '@/components/billing/RevenueChart.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type {
    BillingStats,
    DashboardRecentInvoice,
    DashboardRecentPayment,
    RevenueTrendPoint,
} from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Dashboard', href: '/billing/dashboard' },
        ],
    },
});

defineProps<{
    stats: BillingStats;
    revenue_trend: RevenueTrendPoint[];
    recent_invoices: DashboardRecentInvoice[];
    recent_payments: DashboardRecentPayment[];
}>();

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <Head title="Billing Dashboard" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading title="Billing Dashboard" description="Monitor revenue, payments, and outstanding balances." />

        <BillingStatCards :stats="stats" />

        <!-- Revenue Chart -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">Revenue Trend (Last 6 Months)</CardTitle>
            </CardHeader>
            <CardContent>
                <RevenueChart :data="revenue_trend" />
            </CardContent>
        </Card>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">
            <!-- Recent Invoices -->
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
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Invoice</TableHead>
                                <TableHead>Tenant</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                                <TableHead>Status</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="recent_invoices.length === 0">
                                <TableCell colspan="4" class="py-8 text-center text-muted-foreground">
                                    No invoices yet
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="inv in recent_invoices" :key="inv.id">
                                <TableCell>
                                    <Link :href="`/billing/invoices/${inv.id}`" class="font-medium hover:underline">
                                        {{ inv.invoice_number }}
                                    </Link>
                                    <div class="text-xs text-muted-foreground">Due {{ inv.due_date }}</div>
                                </TableCell>
                                <TableCell class="text-sm">{{ inv.tenant_name }}</TableCell>
                                <TableCell class="text-right text-sm font-medium">
                                    {{ formatCurrency(inv.total_amount) }}
                                </TableCell>
                                <TableCell>
                                    <InvoiceStatusBadge :status="inv.status" :label="inv.status_label" />
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>

            <!-- Recent Payments -->
            <Card>
                <CardHeader class="flex flex-row items-center justify-between">
                    <CardTitle class="text-base">Recent Payments</CardTitle>
                    <Button variant="ghost" size="sm" as-child>
                        <Link href="/billing/payments">
                            View all <ArrowRight class="ml-1 h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </CardHeader>
                <CardContent class="p-0">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Ref</TableHead>
                                <TableHead>Tenant</TableHead>
                                <TableHead>Method</TableHead>
                                <TableHead class="text-right">Amount</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-if="recent_payments.length === 0">
                                <TableCell colspan="4" class="py-8 text-center text-muted-foreground">
                                    No payments yet
                                </TableCell>
                            </TableRow>
                            <TableRow v-for="pay in recent_payments" :key="pay.id">
                                <TableCell>
                                    <div class="font-medium">{{ pay.payment_number }}</div>
                                    <div class="text-xs text-muted-foreground">{{ pay.payment_date }}</div>
                                </TableCell>
                                <TableCell class="text-sm">{{ pay.tenant_name }}</TableCell>
                                <TableCell>
                                    <PaymentMethodBadge :method="pay.payment_method" :label="pay.method_label" />
                                </TableCell>
                                <TableCell class="text-right text-sm font-medium">
                                    {{ formatCurrency(pay.amount) }}
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </CardContent>
            </Card>
        </div>
    </div>
</template>
