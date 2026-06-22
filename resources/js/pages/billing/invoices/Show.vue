<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Download, Mail, Receipt } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InvoiceStatusBadge from '@/components/billing/InvoiceStatusBadge.vue';
import PaymentMethodBadge from '@/components/billing/PaymentMethodBadge.vue';
import PaymentStatusBadge from '@/components/billing/PaymentStatusBadge.vue';
import RecordPaymentModal from '@/components/billing/RecordPaymentModal.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';
import type { Invoice, SelectOption } from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Invoices', href: '/billing/invoices' },
            { title: 'Invoice Detail', href: '#' },
        ],
    },
});

const props = defineProps<{
    invoice: Invoice;
    paymentMethods: SelectOption[];
}>();

const { can } = usePermission();
const recordOpen = ref(false);

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function sendInvoice() {
    router.post(`/billing/invoices/${props.invoice.id}/send`, {}, { preserveScroll: true });
}

function voidInvoice() {
    if (confirm(`Void invoice ${props.invoice.invoice_number}? This cannot be undone.`)) {
        router.delete(`/billing/invoices/${props.invoice.id}`);
    }
}
</script>

<template>
    <Head :title="`Invoice ${invoice.invoice_number}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="sm" as-child>
                    <Link href="/billing/invoices">
                        <ArrowLeft class="mr-1 h-4 w-4" />
                        Back
                    </Link>
                </Button>
                <Separator orientation="vertical" class="h-6" />
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-semibold">{{ invoice.invoice_number }}</h1>
                        <InvoiceStatusBadge :status="invoice.status" :label="invoice.status_label" />
                    </div>
                    <p class="mt-0.5 text-sm text-muted-foreground">{{ invoice.type_label }}</p>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button variant="outline" size="sm" disabled>
                    <Download class="mr-2 h-4 w-4" />
                    Download PDF
                </Button>
                <Button
                    v-if="invoice.status !== 'void' && can('billing.manage_invoice')"
                    variant="outline"
                    size="sm"
                    @click="sendInvoice"
                >
                    <Mail class="mr-2 h-4 w-4" />
                    Send Invoice
                </Button>
                <Button
                    v-if="invoice.status !== 'paid' && invoice.status !== 'void' && can('billing.record_payment')"
                    size="sm"
                    @click="recordOpen = true"
                >
                    <Receipt class="mr-2 h-4 w-4" />
                    Record Payment
                </Button>
                <Button
                    v-if="invoice.status !== 'void' && invoice.status !== 'paid' && can('billing.manage_invoice')"
                    variant="destructive"
                    size="sm"
                    @click="voidInvoice"
                >
                    Void
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Left column: Invoice details -->
            <div class="flex flex-col gap-6 lg:col-span-2">

                <!-- Invoice Info -->
                <Card>
                    <CardHeader><CardTitle class="text-base">Invoice Details</CardTitle></CardHeader>
                    <CardContent>
                        <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm sm:grid-cols-3">
                            <div>
                                <p class="text-muted-foreground">Invoice Number</p>
                                <p class="font-medium">{{ invoice.invoice_number }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Issue Date</p>
                                <p class="font-medium">{{ invoice.issue_date ?? '—' }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Due Date</p>
                                <p class="font-medium">{{ invoice.due_date }}</p>
                            </div>
                            <div v-if="invoice.billing_period_start">
                                <p class="text-muted-foreground">Billing Period</p>
                                <p class="font-medium">{{ invoice.billing_period_start }} – {{ invoice.billing_period_end }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Property</p>
                                <p class="font-medium">{{ invoice.property_name }}</p>
                            </div>
                            <div>
                                <p class="text-muted-foreground">Unit</p>
                                <p class="font-medium">
                                    Unit {{ invoice.unit_number }}
                                    <template v-if="invoice.building_name"> · {{ invoice.building_name }}</template>
                                </p>
                            </div>
                        </div>

                        <Separator class="my-4" />

                        <div class="grid grid-cols-2 gap-x-8 gap-y-3 text-sm">
                            <div>
                                <p class="text-muted-foreground">Tenant</p>
                                <p class="font-medium">{{ invoice.tenant?.full_name ?? invoice.tenant_name }}</p>
                            </div>
                            <div v-if="invoice.tenant?.email">
                                <p class="text-muted-foreground">Email</p>
                                <p class="font-medium">{{ invoice.tenant.email }}</p>
                            </div>
                            <div v-if="invoice.tenant?.phone">
                                <p class="text-muted-foreground">Phone</p>
                                <p class="font-medium">{{ invoice.tenant.phone }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Line Items -->
                <Card>
                    <CardHeader><CardTitle class="text-base">Charges</CardTitle></CardHeader>
                    <CardContent class="p-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Description</TableHead>
                                    <TableHead class="text-right w-20">Qty</TableHead>
                                    <TableHead class="text-right w-32">Unit Price</TableHead>
                                    <TableHead class="text-right w-32">Total</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-for="item in invoice.items" :key="item.id">
                                    <TableCell class="font-medium">{{ item.description }}</TableCell>
                                    <TableCell class="text-right text-sm">{{ item.quantity }}</TableCell>
                                    <TableCell class="text-right text-sm">{{ formatCurrency(item.unit_price) }}</TableCell>
                                    <TableCell class="text-right text-sm font-medium">{{ formatCurrency(item.total_price) }}</TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>

                        <div class="space-y-2 px-6 py-4">
                            <Separator />
                            <div class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span>{{ formatCurrency(invoice.subtotal) }}</span>
                            </div>
                            <div v-if="invoice.discount_amount > 0" class="flex justify-between text-sm text-emerald-600">
                                <span>Discount</span>
                                <span>−{{ formatCurrency(invoice.discount_amount) }}</span>
                            </div>
                            <div v-if="invoice.tax_amount > 0" class="flex justify-between text-sm">
                                <span class="text-muted-foreground">Tax</span>
                                <span>{{ formatCurrency(invoice.tax_amount) }}</span>
                            </div>
                            <div v-if="invoice.late_fee_amount > 0" class="flex justify-between text-sm text-destructive">
                                <span>Late Fee</span>
                                <span>{{ formatCurrency(invoice.late_fee_amount) }}</span>
                            </div>
                            <Separator />
                            <div class="flex justify-between font-semibold">
                                <span>Grand Total</span>
                                <span>{{ formatCurrency(invoice.total_amount) }}</span>
                            </div>
                            <div v-if="invoice.paid_amount > 0" class="flex justify-between text-sm text-emerald-600">
                                <span>Amount Paid</span>
                                <span>−{{ formatCurrency(invoice.paid_amount) }}</span>
                            </div>
                            <div class="flex justify-between text-base font-bold">
                                <span>Balance Due</span>
                                <span :class="invoice.balance_due > 0 ? 'text-destructive' : 'text-emerald-600'">
                                    {{ formatCurrency(invoice.balance_due) }}
                                </span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card v-if="invoice.notes">
                    <CardHeader><CardTitle class="text-base">Notes</CardTitle></CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">{{ invoice.notes }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Right column: Payment History -->
            <div class="flex flex-col gap-6">
                <Card>
                    <CardHeader><CardTitle class="text-base">Payment History</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div v-if="!invoice.payments || invoice.payments.length === 0" class="py-6 text-center text-sm text-muted-foreground">
                            No payments recorded yet.
                        </div>
                        <div
                            v-for="payment in invoice.payments"
                            :key="payment.id"
                            class="rounded-lg border p-3 text-sm"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium">{{ formatCurrency(payment.amount) }}</span>
                                <PaymentStatusBadge :status="payment.status" :label="payment.status_label" />
                            </div>
                            <div class="mt-2 space-y-1 text-xs text-muted-foreground">
                                <div class="flex items-center justify-between">
                                    <span>{{ payment.payment_date }}</span>
                                    <PaymentMethodBadge :method="payment.payment_method" :label="payment.method_label" />
                                </div>
                                <div v-if="payment.reference_number">Ref: {{ payment.reference_number }}</div>
                                <div v-if="payment.proof_of_payment_url">
                                    <a :href="payment.proof_of_payment_url" target="_blank" class="text-primary underline">
                                        View Proof
                                    </a>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Late Fees -->
                <Card v-if="invoice.late_fees && invoice.late_fees.length > 0">
                    <CardHeader><CardTitle class="text-base">Late Fees Applied</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="fee in invoice.late_fees"
                            :key="fee.id"
                            class="flex items-center justify-between rounded-lg bg-destructive/10 px-3 py-2 text-sm"
                        >
                            <div>
                                <div class="font-medium text-destructive">{{ formatCurrency(fee.amount) }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ fee.days_overdue }} day(s) overdue · {{ fee.type_label }}
                                </div>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ new Date(fee.applied_at).toLocaleDateString() }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <RecordPaymentModal
        v-if="invoice.status !== 'paid' && invoice.status !== 'void'"
        v-model:open="recordOpen"
        :invoice="invoice"
        :payment-methods="paymentMethods"
    />
</template>
