<script setup lang="ts">
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Building2, Calendar, DollarSign, FileText } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import type { Lease } from '@/types/lease';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Leases', href: '/leases' },
            { title: 'Edit Lease', href: '#' },
        ],
    },
});

const props = defineProps<{
    lease: Lease;
    statuses: { value: string; label: string }[];
    leaseTypes: { value: string; label: string }[];
    billingCycles: { value: string; label: string }[];
    properties: { id: string; name: string; buildings: { id: string; name: string }[] }[];
    tenants: { value: string; label: string; email: string }[];
    units: { id: string; property_id: string; building_id: string | null; unit_number: string }[];
}>();

const form = useForm({
    unit_id:              props.lease.unit_id ?? '',
    building_id:          props.lease.building_id ?? '',
    rental_tenant_id:     props.lease.rental_tenant_id ?? '',
    lease_type:           props.lease.lease_type ?? 'monthly',
    status:               props.lease.status ?? 'draft',
    start_date:           props.lease.start_date ?? '',
    end_date:             props.lease.end_date ?? '',
    move_in_date:         props.lease.move_in_date ?? '',
    move_out_date:        props.lease.move_out_date ?? '',
    rent_amount:          String(props.lease.rent_amount ?? ''),
    deposit_amount:       String(props.lease.deposit_amount ?? ''),
    advance_payment:      String(props.lease.advance_payment ?? ''),
    utility_deposit:      String(props.lease.utility_deposit ?? ''),
    parking_fee:          String(props.lease.parking_fee ?? ''),
    other_charges:        String(props.lease.other_charges ?? ''),
    currency:             props.lease.currency ?? 'PHP',
    billing_day:          props.lease.billing_day ?? 1,
    billing_cycle:        props.lease.billing_cycle ?? 'monthly',
    generate_days_before: String(props.lease.generate_days_before ?? ''),
    issue_date_offset:    props.lease.issue_date_offset ?? 0,
    notes:                props.lease.notes ?? '',
});

const totalMonthly = computed(() => {
    return (Number(form.rent_amount) || 0) +
           (Number(form.parking_fee) || 0) +
           (Number(form.other_charges) || 0);
});

function submit() {
    form.patch(`/leases/${props.lease.id}`);
}
</script>

<template>
    <Head :title="`Edit Lease ${lease.lease_number}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link :href="`/leases/${lease.id}`">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to Lease
                </Link>
            </Button>
            <Heading :title="`Edit ${lease.lease_number}`" description="Update lease agreement details." />
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="flex flex-col gap-6 lg:col-span-2">

                <!-- Status & Type -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <FileText class="h-4 w-4" />
                            Lease Information
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label>Lease Type <span class="text-destructive">*</span></Label>
                            <Select v-model="form.lease_type">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in leaseTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.lease_type" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label>Status <span class="text-destructive">*</span></Label>
                            <Select v-model="form.status">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label>Tenant</Label>
                            <Select v-model="form.rental_tenant_id">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in tenants" :key="t.value" :value="t.value">
                                        {{ t.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.rental_tenant_id" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Lease Period -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Calendar class="h-4 w-4" />
                            Lease Period
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label>Start Date <span class="text-destructive">*</span></Label>
                            <Input v-model="form.start_date" type="date" />
                            <InputError :message="form.errors.start_date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>End Date <span class="text-destructive">*</span></Label>
                            <Input v-model="form.end_date" type="date" />
                            <InputError :message="form.errors.end_date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Move-in Date</Label>
                            <Input v-model="form.move_in_date" type="date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Move-out Date</Label>
                            <Input v-model="form.move_out_date" type="date" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Rent & Financials -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <DollarSign class="h-4 w-4" />
                            Rent &amp; Financials
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label>Currency</Label>
                            <Select v-model="form.currency">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="PHP">PHP</SelectItem>
                                    <SelectItem value="USD">USD</SelectItem>
                                    <SelectItem value="SGD">SGD</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Monthly Rent <span class="text-destructive">*</span></Label>
                            <Input v-model="form.rent_amount" type="number" min="0" step="0.01" />
                            <InputError :message="form.errors.rent_amount" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Security Deposit</Label>
                            <Input v-model="form.deposit_amount" type="number" min="0" step="0.01" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Advance Payment</Label>
                            <Input v-model="form.advance_payment" type="number" min="0" step="0.01" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Utility Deposit</Label>
                            <Input v-model="form.utility_deposit" type="number" min="0" step="0.01" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Parking Fee</Label>
                            <Input v-model="form.parking_fee" type="number" min="0" step="0.01" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Other Charges</Label>
                            <Input v-model="form.other_charges" type="number" min="0" step="0.01" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Billing Config -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2 text-base">
                            <Building2 class="h-4 w-4" />
                            Billing Configuration
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label>Billing Cycle</Label>
                            <Select v-model="form.billing_cycle">
                                <SelectTrigger><SelectValue /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in billingCycles" :key="c.value" :value="c.value">{{ c.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Billing Day (1–28)</Label>
                            <Input v-model.number="form.billing_day" type="number" min="1" max="28" />
                            <InputError :message="form.errors.billing_day" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Generate Invoice (days before)</Label>
                            <Input v-model="form.generate_days_before" type="number" min="0" max="30" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Issue Date Offset (days)</Label>
                            <Input v-model.number="form.issue_date_offset" type="number" min="0" max="28" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-1.5">
                            <Label>Notes</Label>
                            <Textarea v-model="form.notes" rows="3" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Summary Sidebar -->
            <div class="flex flex-col gap-4">
                <Card class="sticky top-4">
                    <CardHeader>
                        <CardTitle class="text-sm text-muted-foreground">Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Lease #</p>
                            <p class="mt-0.5 font-mono font-semibold">{{ lease.lease_number }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total Monthly</p>
                            <p class="mt-0.5 text-lg font-bold">{{ form.currency }} {{ totalMonthly.toFixed(2) }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    {{ form.processing ? 'Saving…' : 'Save Changes' }}
                </Button>
                <Button variant="outline" class="w-full" as-child>
                    <Link :href="`/leases/${lease.id}`">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
