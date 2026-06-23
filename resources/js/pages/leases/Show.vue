<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Calendar, FileText, History, ReceiptText, Shield, Wallet } from '@lucide/vue';
import LeaseStatusBadge from '@/components/leases/LeaseStatusBadge.vue';
import LeaseHistoryTimeline from '@/components/leases/LeaseHistoryTimeline.vue';
import LeaseDocumentUploader from '@/components/leases/LeaseDocumentUploader.vue';
import LeaseDepositForm from '@/components/leases/LeaseDepositForm.vue';
import RenewLeaseModal from '@/components/leases/RenewLeaseModal.vue';
import TerminateLeaseModal from '@/components/leases/TerminateLeaseModal.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { usePermission } from '@/composables/usePermission';
import type { Lease } from '@/types/lease';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Leases', href: '/leases' },
            { title: 'Lease Details', href: '#' },
        ],
    },
});

const props = defineProps<{
    lease: Lease;
    terminationReasons: { value: string; label: string }[];
}>();

const { can } = usePermission();

const renewOpen     = ref(false);
const terminateOpen = ref(false);
const depositOpen   = ref(false);
const docUploaderOpen = ref(false);

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function formatCurrency(amount: number | null | undefined, currency = 'PHP'): string {
    if (amount == null) return '—';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format(amount);
}

function formatDate(date: string | null | undefined): string {
    if (!date) return '—';
    return new Date(date).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatBytes(bytes: number | null | undefined): string {
    if (!bytes) return '';
    if (bytes < 1024) return `${bytes} B`;
    if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
    return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function getHistoryIcon(eventType: string): string {
    const icons: Record<string, string> = {
        created: '📄', activated: '✅', renewed: '🔄', terminated: '🔴',
        expired: '⏰', deposit_recorded: '💰', deposit_updated: '💳',
        document_uploaded: '📎', document_deleted: '🗑️', updated: '✏️',
        expiring_soon: '⚠️', reminder_sent: '🔔',
    };
    return icons[eventType] ?? '•';
}

function getDepositStatusClass(status: string): string {
    return {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        partially_paid: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        refunded: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
    }[status] ?? 'bg-gray-100 text-gray-600';
}

function deleteDocument(docId: string) {
    if (confirm('Delete this document?')) {
        router.delete(`/leases/${props.lease.id}/documents/${docId}`);
    }
}

const canRenew     = ['active', 'expiring_soon'].includes(props.lease.status ?? '');
const canTerminate = ['active', 'expiring_soon'].includes(props.lease.status ?? '');
</script>

<template>
    <Head :title="`Lease ${lease.lease_number}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/leases">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Leases
                </Link>
            </Button>
            <div class="flex flex-wrap gap-2">
                <Button v-if="can('leases.edit')" variant="outline" size="sm" as-child>
                    <Link :href="`/leases/${lease.id}/edit`">Edit</Link>
                </Button>
                <Button
                    v-if="can('leases.renew') && canRenew"
                    variant="outline"
                    size="sm"
                    @click="renewOpen = true"
                >
                    Renew Lease
                </Button>
                <Button
                    v-if="can('leases.terminate') && canTerminate"
                    variant="outline"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="terminateOpen = true"
                >
                    Terminate
                </Button>
            </div>
        </div>

        <!-- 4-col Grid -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

            <!-- Left Sidebar -->
            <Card class="h-fit lg:col-span-1">
                <CardContent class="flex flex-col items-center gap-4 pt-6">
                    <!-- Lease Number -->
                    <span class="rounded bg-muted px-3 py-1 font-mono text-sm font-semibold">
                        {{ lease.lease_number }}
                    </span>

                    <!-- Tenant -->
                    <template v-if="lease.tenant">
                        <Avatar class="h-16 w-16">
                            <AvatarImage :src="lease.tenant.profile_photo_url ?? ''" :alt="lease.tenant.full_name" />
                            <AvatarFallback class="text-xl">{{ getInitials(lease.tenant.full_name) }}</AvatarFallback>
                        </Avatar>
                        <div class="text-center">
                            <p class="font-semibold">{{ lease.tenant.full_name }}</p>
                            <p class="text-xs text-muted-foreground">{{ lease.tenant.email }}</p>
                        </div>
                    </template>

                    <LeaseStatusBadge :status="lease.status" :label="lease.status_label" />

                    <!-- Progress Bar -->
                    <div class="w-full">
                        <div class="mb-1 flex justify-between text-xs text-muted-foreground">
                            <span>Progress</span>
                            <span>{{ lease.progress_percentage?.toFixed(0) }}%</span>
                        </div>
                        <div class="h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{ width: `${Math.min(lease.progress_percentage ?? 0, 100)}%` }"
                            />
                        </div>
                        <p class="mt-1 text-center text-xs text-muted-foreground">
                            {{ lease.days_remaining > 0 ? `${lease.days_remaining} days remaining` : 'Lease ended' }}
                        </p>
                    </div>

                    <Separator />

                    <!-- Key Info -->
                    <div class="w-full space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Property</span>
                            <span class="font-medium">{{ lease.property_name ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Unit</span>
                            <span class="font-medium">{{ lease.unit_number ?? '—' }}</span>
                        </div>
                        <div v-if="lease.building_name" class="flex justify-between">
                            <span class="text-muted-foreground">Building</span>
                            <span class="font-medium">{{ lease.building_name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Type</span>
                            <span class="font-medium">{{ lease.lease_type_label ?? '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">Start</span>
                            <span class="font-medium">{{ formatDate(lease.start_date) }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-muted-foreground">End</span>
                            <span class="font-medium">{{ formatDate(lease.end_date) }}</span>
                        </div>
                    </div>

                    <Separator />

                    <div class="w-full text-center">
                        <p class="text-xs text-muted-foreground">Monthly Rent</p>
                        <p class="text-2xl font-bold">{{ formatCurrency(lease.rent_amount, lease.currency) }}</p>
                    </div>
                </CardContent>
            </Card>

            <!-- Right — Tabs -->
            <div class="lg:col-span-3">
                <Tabs default-value="overview">
                    <TabsList class="mb-4 h-auto flex-wrap gap-1">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="documents">
                            Documents
                            <Badge v-if="lease.documents_count" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ lease.documents_count }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="renewals">
                            Renewals
                            <Badge v-if="lease.renewals_count" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ lease.renewals_count }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="billing">
                            Billing
                            <Badge v-if="lease.invoices_count" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ lease.invoices_count }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="deposits">
                            Deposits
                            <Badge v-if="lease.deposits_count" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ lease.deposits_count }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="history">History</TabsTrigger>
                    </TabsList>

                    <!-- Overview Tab -->
                    <TabsContent value="overview" class="flex flex-col gap-4">
                        <!-- Stat Cards -->
                        <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <Card>
                                <CardContent class="pt-4">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <Wallet class="h-4 w-4" />
                                        <span class="text-xs">Monthly Rent</span>
                                    </div>
                                    <p class="mt-1 text-lg font-bold">{{ formatCurrency(lease.rent_amount, lease.currency) }}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-4">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <Shield class="h-4 w-4" />
                                        <span class="text-xs">Security Deposit</span>
                                    </div>
                                    <p class="mt-1 text-lg font-bold">{{ formatCurrency(lease.deposit_amount, lease.currency) }}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-4">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <Calendar class="h-4 w-4" />
                                        <span class="text-xs">Days Remaining</span>
                                    </div>
                                    <p class="mt-1 text-lg font-bold">{{ lease.days_remaining > 0 ? lease.days_remaining : '—' }}</p>
                                </CardContent>
                            </Card>
                            <Card>
                                <CardContent class="pt-4">
                                    <div class="flex items-center gap-2 text-muted-foreground">
                                        <ReceiptText class="h-4 w-4" />
                                        <span class="text-xs">Months Remaining</span>
                                    </div>
                                    <p class="mt-1 text-lg font-bold">{{ lease.months_remaining > 0 ? lease.months_remaining : '—' }}</p>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Info Grid -->
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm text-muted-foreground">Lease Details</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Lease Number</span>
                                        <span class="font-mono font-medium">{{ lease.lease_number }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Type</span>
                                        <span>{{ lease.lease_type_label }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Move-in</span>
                                        <span>{{ formatDate(lease.move_in_date) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Move-out</span>
                                        <span>{{ formatDate(lease.move_out_date) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Billing Cycle</span>
                                        <span class="capitalize">{{ lease.billing_cycle ?? '—' }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Billing Day</span>
                                        <span>Day {{ lease.billing_day }}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm text-muted-foreground">Financial Summary</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Monthly Rent</span>
                                        <span class="font-medium">{{ formatCurrency(lease.rent_amount, lease.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Parking Fee</span>
                                        <span>{{ formatCurrency(lease.parking_fee, lease.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Other Charges</span>
                                        <span>{{ formatCurrency(lease.other_charges, lease.currency) }}</span>
                                    </div>
                                    <Separator />
                                    <div class="flex justify-between font-semibold">
                                        <span>Total Monthly</span>
                                        <span>{{ formatCurrency(lease.total_monthly_charges, lease.currency) }}</span>
                                    </div>
                                    <Separator />
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Security Deposit</span>
                                        <span>{{ formatCurrency(lease.deposit_amount, lease.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Advance Payment</span>
                                        <span>{{ formatCurrency(lease.advance_payment, lease.currency) }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Utility Deposit</span>
                                        <span>{{ formatCurrency(lease.utility_deposit, lease.currency) }}</span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Notes -->
                        <Card v-if="lease.notes">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm text-muted-foreground">Notes</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="text-sm">{{ lease.notes }}</p>
                            </CardContent>
                        </Card>

                        <!-- Termination Info -->
                        <Card v-if="lease.status === 'terminated'" class="border-destructive/30 bg-destructive/5">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm text-destructive">Termination Details</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Date</span>
                                    <span>{{ formatDate(lease.termination_date) }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="text-muted-foreground">Reason</span>
                                    <span class="capitalize">{{ lease.termination_reason?.replace(/_/g, ' ') ?? '—' }}</span>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Documents Tab -->
                    <TabsContent value="documents" class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">Lease Documents</h3>
                            <Button v-if="can('leases.edit')" size="sm" @click="docUploaderOpen = true">
                                <FileText class="mr-2 h-4 w-4" />
                                Upload Document
                            </Button>
                        </div>

                        <div v-if="lease.documents?.length" class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <Card v-for="doc in lease.documents" :key="doc.id" class="p-4">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded bg-muted text-lg">
                                            {{ doc.mime_type?.includes('pdf') ? '📄' : doc.mime_type?.includes('image') ? '🖼️' : '📎' }}
                                        </div>
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium">{{ doc.name }}</p>
                                            <p class="text-xs capitalize text-muted-foreground">
                                                {{ doc.type.replace(/_/g, ' ') }}
                                                <template v-if="doc.file_size"> · {{ formatBytes(doc.file_size) }}</template>
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex shrink-0 gap-1">
                                        <Button variant="ghost" size="sm" as-child>
                                            <a :href="doc.file_url" target="_blank">View</a>
                                        </Button>
                                        <Button
                                            v-if="can('leases.edit')"
                                            variant="ghost"
                                            size="sm"
                                            class="text-destructive hover:text-destructive"
                                            @click="deleteDocument(doc.id)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </div>
                            </Card>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                            <FileText class="mb-3 h-10 w-10 text-muted-foreground/40" />
                            <p class="text-sm font-medium">No documents yet</p>
                            <p class="text-xs text-muted-foreground">Upload lease agreements, IDs, and other files.</p>
                        </div>
                    </TabsContent>

                    <!-- Renewals Tab -->
                    <TabsContent value="renewals" class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">Renewal History</h3>
                            <Button
                                v-if="can('leases.renew') && canRenew"
                                size="sm"
                                @click="renewOpen = true"
                            >
                                Renew Lease
                            </Button>
                        </div>

                        <div v-if="lease.renewals?.length" class="flex flex-col gap-3">
                            <Card v-for="renewal in lease.renewals" :key="renewal.id">
                                <CardContent class="pt-4">
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="space-y-1 text-sm">
                                            <div class="flex items-center gap-2">
                                                <span class="font-medium">{{ formatDate(renewal.renewal_date) }}</span>
                                                <span
                                                    class="rounded-full px-2 py-0.5 text-xs font-medium"
                                                    :class="{
                                                        'bg-green-100 text-green-700': renewal.renewal_status === 'completed',
                                                        'bg-yellow-100 text-yellow-700': renewal.renewal_status === 'pending',
                                                        'bg-blue-100 text-blue-700': renewal.renewal_status === 'approved',
                                                        'bg-red-100 text-red-700': renewal.renewal_status === 'rejected',
                                                    }"
                                                >
                                                    {{ renewal.renewal_status_label }}
                                                </span>
                                            </div>
                                            <p class="text-muted-foreground">
                                                {{ formatDate(renewal.previous_end_date) }} → {{ formatDate(renewal.new_end_date) }}
                                            </p>
                                            <p>New rent: <span class="font-medium">{{ formatCurrency(renewal.new_rent_amount, lease.currency) }}</span></p>
                                            <p v-if="renewal.reason" class="text-muted-foreground">{{ renewal.reason }}</p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                            <History class="mb-3 h-10 w-10 text-muted-foreground/40" />
                            <p class="text-sm font-medium">No renewals yet</p>
                            <p class="text-xs text-muted-foreground">Renewals will appear here once initiated.</p>
                        </div>
                    </TabsContent>

                    <!-- Billing Tab -->
                    <TabsContent value="billing" class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">Invoices</h3>
                            <Button variant="outline" size="sm" as-child>
                                <Link :href="`/billing/invoices?lease_id=${lease.id}`">View All</Link>
                            </Button>
                        </div>

                        <div v-if="lease.invoices?.length" class="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Invoice #</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead class="text-right">Action</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="inv in lease.invoices" :key="inv.id">
                                        <TableCell class="font-mono text-sm">{{ inv.invoice_number }}</TableCell>
                                        <TableCell class="text-sm">{{ formatDate(inv.due_date) }}</TableCell>
                                        <TableCell class="text-sm font-medium">{{ formatCurrency(inv.total_amount, lease.currency) }}</TableCell>
                                        <TableCell>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                                :class="{
                                                    'bg-green-100 text-green-700': inv.status === 'paid',
                                                    'bg-yellow-100 text-yellow-700': inv.status === 'sent',
                                                    'bg-red-100 text-red-700': inv.status === 'overdue',
                                                    'bg-gray-100 text-gray-600': ['draft','void'].includes(inv.status ?? ''),
                                                }"
                                            >
                                                {{ inv.status_label }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-right">
                                            <Button variant="ghost" size="sm" as-child>
                                                <Link :href="`/billing/invoices/${inv.id}`">View</Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                            <ReceiptText class="mb-3 h-10 w-10 text-muted-foreground/40" />
                            <p class="text-sm font-medium">No invoices yet</p>
                            <p class="text-xs text-muted-foreground">Invoices will be generated automatically on the billing day.</p>
                        </div>
                    </TabsContent>

                    <!-- Deposits Tab -->
                    <TabsContent value="deposits" class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <h3 class="font-medium">Deposits</h3>
                            <Button v-if="can('leases.edit')" size="sm" @click="depositOpen = true">
                                Record Deposit
                            </Button>
                        </div>

                        <div v-if="lease.deposits?.length" class="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Payment Date</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Refund</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="dep in lease.deposits" :key="dep.id">
                                        <TableCell class="font-medium">{{ dep.type_label }}</TableCell>
                                        <TableCell class="font-medium">{{ formatCurrency(dep.amount, lease.currency) }}</TableCell>
                                        <TableCell class="text-sm">{{ formatDate(dep.payment_date) }}</TableCell>
                                        <TableCell>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="getDepositStatusClass(dep.status)">
                                                {{ dep.status_label }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-sm">
                                            <template v-if="dep.refund_amount">
                                                {{ formatCurrency(dep.refund_amount, lease.currency) }}
                                                <div v-if="dep.deduction_amount" class="text-xs text-muted-foreground">
                                                    Deduction: {{ formatCurrency(dep.deduction_amount, lease.currency) }}
                                                </div>
                                            </template>
                                            <span v-else class="text-muted-foreground">—</span>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>

                        <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                            <Shield class="mb-3 h-10 w-10 text-muted-foreground/40" />
                            <p class="text-sm font-medium">No deposits recorded</p>
                            <p class="text-xs text-muted-foreground">Track security deposits and advance payments here.</p>
                        </div>
                    </TabsContent>

                    <!-- History Tab -->
                    <TabsContent value="history">
                        <LeaseHistoryTimeline
                            :histories="lease.histories ?? []"
                            :get-icon="getHistoryIcon"
                        />
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <RenewLeaseModal
        :open="renewOpen"
        :lease="lease"
        @update:open="renewOpen = $event"
    />

    <TerminateLeaseModal
        :open="terminateOpen"
        :lease="lease"
        :termination-reasons="terminationReasons"
        @update:open="terminateOpen = $event"
    />

    <LeaseDepositForm
        :open="depositOpen"
        :lease-id="lease.id"
        :currency="lease.currency"
        @update:open="depositOpen = $event"
    />

    <LeaseDocumentUploader
        :open="docUploaderOpen"
        :lease-id="lease.id"
        @update:open="docUploaderOpen = $event"
    />
</template>
