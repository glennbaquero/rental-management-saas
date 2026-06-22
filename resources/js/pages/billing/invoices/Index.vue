<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, Plus, Search, SlidersHorizontal } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InvoiceStatusBadge from '@/components/billing/InvoiceStatusBadge.vue';
import RecordPaymentModal from '@/components/billing/RecordPaymentModal.vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';
import type { Invoice, InvoiceFilters, PaginatedInvoices, SelectOption } from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Invoices', href: '/billing/invoices' },
        ],
    },
});

const props = defineProps<{
    invoices: PaginatedInvoices;
    filters: InvoiceFilters;
    statuses: SelectOption[];
    properties: SelectOption[];
}>();

const { can } = usePermission();

const search         = ref(props.filters.search ?? '');
const statusFilter   = ref(props.filters.status ?? 'all');
const propertyFilter = ref(props.filters.property_id ?? 'all');
const dateFrom       = ref(props.filters.date_from ?? '');
const dateTo         = ref(props.filters.date_to ?? '');

const recordTarget = ref<Invoice | null>(null);
const recordOpen   = ref(false);

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/billing/invoices', {
        search:      search.value || undefined,
        status:      statusFilter.value !== 'all' ? statusFilter.value : undefined,
        property_id: propertyFilter.value !== 'all' ? propertyFilter.value : undefined,
        date_from:   dateFrom.value || undefined,
        date_to:     dateTo.value || undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch([statusFilter, propertyFilter, dateFrom, dateTo], applyFilters);

function clearFilters() {
    search.value         = '';
    statusFilter.value   = 'all';
    propertyFilter.value = 'all';
    dateFrom.value       = '';
    dateTo.value         = '';
}

const hasActiveFilters = computed(() =>
    !!(search.value || statusFilter.value !== 'all' || propertyFilter.value !== 'all' || dateFrom.value || dateTo.value)
);

function openRecordPayment(invoice: Invoice) {
    recordTarget.value = invoice;
    recordOpen.value   = true;
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function voidInvoice(invoice: Invoice) {
    if (confirm(`Void invoice ${invoice.invoice_number}? This cannot be undone.`)) {
        router.delete(`/billing/invoices/${invoice.id}`);
    }
}

function sendInvoice(invoice: Invoice) {
    router.post(`/billing/invoices/${invoice.id}/send`, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Invoices" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Invoices" description="Manage and track all rental invoices." />
            <div class="flex gap-2">
                <Button variant="outline" disabled>
                    <Plus class="mr-2 h-4 w-4" />
                    Generate Invoice
                </Button>
                <Button v-if="can('billing.create_invoice')" as-child>
                    <Link href="/billing/invoices/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Invoice
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap gap-3">
            <div class="relative min-w-56 flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search by invoice # or tenant…" class="pl-9" />
            </div>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-40">
                    <SlidersHorizontal class="mr-2 h-4 w-4 text-muted-foreground" />
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="propertyFilter">
                <SelectTrigger class="w-44">
                    <SelectValue placeholder="Property" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Properties</SelectItem>
                    <SelectItem v-for="p in properties" :key="p.value" :value="p.value">{{ p.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Input v-model="dateFrom" type="date" class="w-36" title="Due from" />
            <Input v-model="dateTo"   type="date" class="w-36" title="Due to" />

            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">Clear</Button>
        </div>

        <p class="text-sm text-muted-foreground">
            Showing {{ invoices.from ?? 0 }}–{{ invoices.to ?? 0 }} of {{ invoices.total }} invoices
        </p>

        <!-- Empty State -->
        <div
            v-if="invoices.data.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
            <FileText class="mb-4 h-12 w-12 text-muted-foreground/40" />
            <p class="text-base font-medium">No invoices found</p>
            <p class="mt-1 text-sm text-muted-foreground">Try adjusting your filters.</p>
        </div>

        <!-- Table -->
        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Invoice #</TableHead>
                        <TableHead>Tenant</TableHead>
                        <TableHead>Property / Unit</TableHead>
                        <TableHead>Due Date</TableHead>
                        <TableHead class="text-right">Amount</TableHead>
                        <TableHead class="text-right">Balance</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="inv in invoices.data" :key="inv.id">
                        <TableCell>
                            <Link :href="`/billing/invoices/${inv.id}`" class="font-medium hover:underline">
                                {{ inv.invoice_number }}
                            </Link>
                            <div class="text-xs text-muted-foreground">{{ inv.type_label }}</div>
                        </TableCell>
                        <TableCell class="text-sm">{{ inv.tenant_name }}</TableCell>
                        <TableCell class="text-sm">
                            <div class="font-medium">{{ inv.property_name }}</div>
                            <div class="text-xs text-muted-foreground">
                                Unit {{ inv.unit_number }}
                                <template v-if="inv.building_name"> · {{ inv.building_name }}</template>
                            </div>
                        </TableCell>
                        <TableCell class="text-sm">{{ inv.due_date }}</TableCell>
                        <TableCell class="text-right text-sm font-medium">
                            {{ formatCurrency(inv.total_amount) }}
                        </TableCell>
                        <TableCell class="text-right text-sm">
                            <span :class="inv.balance_due > 0 ? 'text-destructive font-medium' : 'text-muted-foreground'">
                                {{ formatCurrency(inv.balance_due) }}
                            </span>
                        </TableCell>
                        <TableCell>
                            <InvoiceStatusBadge :status="inv.status" :label="inv.status_label" />
                        </TableCell>
                        <TableCell class="text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm">⋯</Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="`/billing/invoices/${inv.id}`">View</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="inv.status !== 'paid' && inv.status !== 'void'"
                                        @click="openRecordPayment(inv)"
                                    >
                                        Record Payment
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="inv.status === 'draft' || inv.status === 'sent'"
                                        @click="sendInvoice(inv)"
                                    >
                                        Send Invoice
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator v-if="can('billing.manage_invoice') && inv.status !== 'void' && inv.status !== 'paid'" />
                                    <DropdownMenuItem
                                        v-if="can('billing.manage_invoice') && inv.status !== 'void' && inv.status !== 'paid'"
                                        class="text-destructive focus:text-destructive"
                                        @click="voidInvoice(inv)"
                                    >
                                        Void
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="invoices.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in invoices.links"
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

    <RecordPaymentModal
        v-if="recordTarget"
        v-model:open="recordOpen"
        :invoice="recordTarget"
        :payment-methods="[]"
    />
</template>
