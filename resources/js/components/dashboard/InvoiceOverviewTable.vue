<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, CreditCard } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { RecentInvoice } from '@/types/dashboard';

defineProps<{
    invoices: RecentInvoice[];
}>();

function statusClass(status: string): string {
    const map: Record<string, string> = {
        paid:     'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400',
        partial:  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
        overdue:  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        sent:     'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        draft:    'bg-muted text-muted-foreground',
        void:     'bg-muted text-muted-foreground',
    };
    return map[status] ?? 'bg-muted text-muted-foreground';
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <Table>
        <TableHeader>
            <TableRow>
                <TableHead>Invoice</TableHead>
                <TableHead>Tenant</TableHead>
                <TableHead>Property</TableHead>
                <TableHead class="text-right">Amount</TableHead>
                <TableHead class="text-right">Balance</TableHead>
                <TableHead>Due Date</TableHead>
                <TableHead>Status</TableHead>
                <TableHead />
            </TableRow>
        </TableHeader>
        <TableBody>
            <TableRow v-if="invoices.length === 0">
                <TableCell colspan="8" class="py-10 text-center text-muted-foreground">
                    <CreditCard class="mx-auto mb-2 h-8 w-8 opacity-30" />
                    No invoices found
                </TableCell>
            </TableRow>
            <TableRow v-for="inv in invoices" :key="inv.id">
                <TableCell class="font-mono text-xs font-medium">{{ inv.invoice_number }}</TableCell>
                <TableCell class="text-sm">{{ inv.tenant_name }}</TableCell>
                <TableCell class="text-sm text-muted-foreground">{{ inv.property_name }}</TableCell>
                <TableCell class="text-right tabular-nums text-sm font-medium">
                    {{ formatCurrency(inv.total_amount) }}
                </TableCell>
                <TableCell class="text-right tabular-nums text-sm" :class="inv.balance_due > 0 ? 'text-red-600 dark:text-red-400 font-medium' : 'text-muted-foreground'">
                    {{ formatCurrency(inv.balance_due) }}
                </TableCell>
                <TableCell class="text-sm">{{ inv.due_date }}</TableCell>
                <TableCell>
                    <span
                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                        :class="statusClass(inv.status)"
                    >
                        {{ inv.status_label }}
                    </span>
                </TableCell>
                <TableCell>
                    <Button variant="ghost" size="sm" as-child>
                        <Link :href="`/billing/invoices/${inv.id}`">
                            <ArrowRight class="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </TableCell>
            </TableRow>
        </TableBody>
    </Table>
</template>
