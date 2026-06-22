<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Search, SlidersHorizontal, Upload } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import PaymentMethodBadge from '@/components/billing/PaymentMethodBadge.vue';
import PaymentStatusBadge from '@/components/billing/PaymentStatusBadge.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import { usePermission } from '@/composables/usePermission';
import type { PaginatedPayments, Payment, PaymentFilters, SelectOption } from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Payments', href: '/billing/payments' },
        ],
    },
});

const props = defineProps<{
    payments: PaginatedPayments;
    filters: PaymentFilters;
    methods: SelectOption[];
    statuses: SelectOption[];
}>();

const { can } = usePermission();

const search       = ref(props.filters.search ?? '');
const methodFilter = ref(props.filters.method ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');
const dateFrom     = ref(props.filters.date_from ?? '');
const dateTo       = ref(props.filters.date_to ?? '');

const rejectTarget = ref<Payment | null>(null);
const rejectOpen   = ref(false);
const rejectForm   = useForm({ rejection_reason: '' });

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/billing/payments', {
        search:    search.value || undefined,
        method:    methodFilter.value !== 'all' ? methodFilter.value : undefined,
        status:    statusFilter.value !== 'all' ? statusFilter.value : undefined,
        date_from: dateFrom.value || undefined,
        date_to:   dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch([methodFilter, statusFilter, dateFrom, dateTo], applyFilters);

function clearFilters() {
    search.value       = '';
    methodFilter.value = 'all';
    statusFilter.value = 'all';
    dateFrom.value     = '';
    dateTo.value       = '';
}

const hasActiveFilters = computed(() =>
    !!(search.value || methodFilter.value !== 'all' || statusFilter.value !== 'all' || dateFrom.value || dateTo.value)
);

function verify(payment: Payment) {
    if (confirm(`Verify payment ${payment.payment_number}?`)) {
        router.patch(`/billing/payments/${payment.id}/verify`, {}, { preserveScroll: true });
    }
}

function openReject(payment: Payment) {
    rejectTarget.value        = payment;
    rejectForm.rejection_reason = '';
    rejectOpen.value          = true;
}

function submitReject() {
    if (!rejectTarget.value) return;
    rejectForm.patch(`/billing/payments/${rejectTarget.value.id}/reject`, {
        onSuccess: () => { rejectOpen.value = false; },
    });
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <Head title="Payments" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading title="Payments" description="Track and verify all payment transactions." />

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
            <div class="relative min-w-56 flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search by ref # or tenant…" class="pl-9" />
            </div>

            <Select v-model="methodFilter">
                <SelectTrigger class="w-40">
                    <SlidersHorizontal class="mr-2 h-4 w-4 text-muted-foreground" />
                    <SelectValue placeholder="Method" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Methods</SelectItem>
                    <SelectItem v-for="m in methods" :key="m.value" :value="m.value">{{ m.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-36">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Input v-model="dateFrom" type="date" class="w-36" title="From date" />
            <Input v-model="dateTo"   type="date" class="w-36" title="To date" />

            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">Clear</Button>
        </div>

        <p class="text-sm text-muted-foreground">
            Showing {{ payments.from ?? 0 }}–{{ payments.to ?? 0 }} of {{ payments.total }} payments
        </p>

        <!-- Table -->
        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Reference</TableHead>
                        <TableHead>Invoice</TableHead>
                        <TableHead>Tenant</TableHead>
                        <TableHead>Method</TableHead>
                        <TableHead>Date</TableHead>
                        <TableHead class="text-right">Amount</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="payments.data.length === 0">
                        <TableCell colspan="8" class="py-12 text-center text-muted-foreground">
                            No payments found.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="pay in payments.data" :key="pay.id">
                        <TableCell>
                            <div class="font-medium">{{ pay.payment_number }}</div>
                            <div v-if="pay.reference_number" class="text-xs text-muted-foreground">{{ pay.reference_number }}</div>
                        </TableCell>
                        <TableCell class="text-sm">
                            <Link
                                v-if="pay.invoice_id"
                                :href="`/billing/invoices/${pay.invoice_id}`"
                                class="hover:underline"
                            >
                                {{ pay.invoice_number }}
                            </Link>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell class="text-sm">{{ pay.tenant_name }}</TableCell>
                        <TableCell>
                            <PaymentMethodBadge :method="pay.payment_method" :label="pay.method_label" />
                        </TableCell>
                        <TableCell class="text-sm">{{ pay.payment_date }}</TableCell>
                        <TableCell class="text-right text-sm font-medium">
                            {{ formatCurrency(pay.amount) }}
                        </TableCell>
                        <TableCell>
                            <PaymentStatusBadge :status="pay.status" :label="pay.status_label" />
                        </TableCell>
                        <TableCell class="text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm">⋯</Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        v-if="pay.proof_of_payment_url"
                                        as-child
                                    >
                                        <a :href="pay.proof_of_payment_url" target="_blank">View Proof</a>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="can('billing.verify_payment') && pay.status === 'pending'"
                                        @click="verify(pay)"
                                    >
                                        Verify
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator v-if="can('billing.verify_payment') && pay.status === 'pending'" />
                                    <DropdownMenuItem
                                        v-if="can('billing.verify_payment') && pay.status === 'pending'"
                                        class="text-destructive focus:text-destructive"
                                        @click="openReject(pay)"
                                    >
                                        Reject
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="payments.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in payments.links"
                :key="link.label"
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                as-child
            >
                <Link v-if="link.url" :href="link.url" preserve-scroll v-html="link.label" />
                <span v-else v-html="link.label" />
            </Button>
        </div>
    </div>

    <!-- Reject Modal -->
    <Dialog v-model:open="rejectOpen">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Reject Payment</DialogTitle>
            </DialogHeader>
            <form class="space-y-4" @submit.prevent="submitReject">
                <div class="space-y-1.5">
                    <Label>Rejection Reason *</Label>
                    <Textarea
                        v-model="rejectForm.rejection_reason"
                        placeholder="Explain why this payment is being rejected…"
                        rows="3"
                    />
                    <InputError :message="rejectForm.errors.rejection_reason" />
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="rejectOpen = false">Cancel</Button>
                    <Button type="submit" variant="destructive" :disabled="rejectForm.processing">
                        {{ rejectForm.processing ? 'Rejecting…' : 'Reject Payment' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
