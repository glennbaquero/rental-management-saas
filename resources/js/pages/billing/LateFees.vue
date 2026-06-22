<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { AlertTriangle, Search } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { LateFee, PaginatedLateFees } from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Late Fees', href: '/billing/late-fees' },
        ],
    },
});

const props = defineProps<{
    lateFees: PaginatedLateFees;
    filters: { search?: string };
    totalCollected: number;
}>();

const search = ref(props.filters.search ?? '');
let searchTimer: ReturnType<typeof setTimeout>;

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get('/billing/late-fees', { search: search.value || undefined }, { preserveState: true, replace: true });
    }, 400);
});

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <Head title="Late Fees" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Late Fees" description="Track all applied late fees across invoices." />
        </div>

        <Card class="w-fit">
            <CardHeader class="flex flex-row items-center gap-3 space-y-0 pb-2">
                <AlertTriangle class="h-5 w-5 text-orange-500" />
                <CardTitle class="text-sm font-medium text-muted-foreground">Total Late Fees Collected</CardTitle>
            </CardHeader>
            <CardContent>
                <div class="text-2xl font-bold">{{ formatCurrency(totalCollected) }}</div>
            </CardContent>
        </Card>

        <!-- Search -->
        <div class="relative w-full max-w-sm">
            <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
            <Input v-model="search" placeholder="Search by invoice or tenant…" class="pl-9" />
        </div>

        <p class="text-sm text-muted-foreground">
            Showing {{ lateFees.from ?? 0 }}–{{ lateFees.to ?? 0 }} of {{ lateFees.total }} late fees
        </p>

        <div class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Invoice</TableHead>
                        <TableHead>Tenant</TableHead>
                        <TableHead>Property</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead class="text-right">Rate</TableHead>
                        <TableHead class="text-right">Amount</TableHead>
                        <TableHead class="text-right">Days Overdue</TableHead>
                        <TableHead>Applied At</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="lateFees.data.length === 0">
                        <TableCell colspan="8" class="py-12 text-center text-muted-foreground">
                            No late fees recorded.
                        </TableCell>
                    </TableRow>
                    <TableRow v-for="fee in lateFees.data" :key="fee.id">
                        <TableCell>
                            <Link
                                v-if="fee.invoice_id"
                                :href="`/billing/invoices/${fee.invoice_id}`"
                                class="font-medium hover:underline"
                            >
                                {{ fee.invoice_number ?? '—' }}
                            </Link>
                        </TableCell>
                        <TableCell class="text-sm">{{ fee.tenant_name }}</TableCell>
                        <TableCell class="text-sm">{{ fee.property_name }}</TableCell>
                        <TableCell class="text-sm">{{ fee.type_label }}</TableCell>
                        <TableCell class="text-right text-sm">
                            {{ fee.type === 'percentage' ? `${fee.rate}%` : formatCurrency(fee.rate) }}
                        </TableCell>
                        <TableCell class="text-right text-sm font-medium text-destructive">
                            {{ formatCurrency(fee.amount) }}
                        </TableCell>
                        <TableCell class="text-right text-sm">{{ fee.days_overdue }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ new Date(fee.applied_at).toLocaleDateString('en-PH', { dateStyle: 'medium' }) }}
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="lateFees.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in lateFees.links"
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
</template>
