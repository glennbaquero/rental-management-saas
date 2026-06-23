<script setup lang="ts">
import { computed, ref, watch } from 'vue';
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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Leases', href: '/leases' },
            { title: 'Create Lease', href: '#' },
        ],
    },
});

const props = defineProps<{
    statuses: { value: string; label: string }[];
    leaseTypes: { value: string; label: string }[];
    billingCycles: { value: string; label: string }[];
    properties: { id: string; name: string; buildings: { id: string; name: string }[] }[];
    tenants: { value: string; label: string; email: string }[];
    units: { id: string; property_id: string; building_id: string | null; unit_number: string }[];
}>();

const selectedProperty = ref('');

const availableBuildings = computed(() =>
    props.properties.find(p => p.id === selectedProperty.value)?.buildings ?? []
);

const availableUnits = computed(() => {
    if (!selectedProperty.value) return [];
    return props.units.filter(u => {
        if (u.property_id !== selectedProperty.value) return false;
        if (form.building_id && u.building_id !== form.building_id) return false;
        return true;
    });
});

const form = useForm({
    unit_id:              '',
    building_id:          '',
    rental_tenant_id:     '',
    lease_type:           'monthly',
    status:               'draft',
    start_date:           '',
    end_date:             '',
    move_in_date:         '',
    move_out_date:        '',
    rent_amount:          '',
    deposit_amount:       '',
    advance_payment:      '',
    utility_deposit:      '',
    parking_fee:          '',
    other_charges:        '',
    currency:             'PHP',
    billing_day:          1,
    billing_cycle:        'monthly',
    generate_days_before: '',
    issue_date_offset:    0,
    notes:                '',
});

watch(selectedProperty, () => {
    form.unit_id     = '';
    form.building_id = '';
});

watch(() => form.building_id, () => {
    form.unit_id = '';
});

const leaseDuration = computed(() => {
    if (!form.start_date || !form.end_date) return null;
    const start = new Date(form.start_date);
    const end   = new Date(form.end_date);
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return null;
    const months = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 30.44));
    return months > 0 ? `${months} Month${months !== 1 ? 's' : ''}` : null;
});

const totalMonthly = computed(() => {
    return (Number(form.rent_amount) || 0) +
           (Number(form.parking_fee) || 0) +
           (Number(form.other_charges) || 0);
});

const selectedTenant = computed(() => props.tenants.find(t => t.value === form.rental_tenant_id));
const selectedUnit   = computed(() => availableUnits.value.find(u => u.id === form.unit_id));

function submit() {
    form.post('/leases');
}
</script>

<template>
    <Head title="Create Lease" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/leases">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Leases
                </Link>
            </Button>
            <Heading title="Create Lease" description="Set up a new rental agreement." />
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <!-- Main Form -->
            <div class="flex flex-col gap-6 lg:col-span-2">

                <!-- Lease Info -->
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
                                <SelectTrigger>
                                    <SelectValue placeholder="Select type" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in leaseTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.lease_type" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label>Status <span class="text-destructive">*</span></Label>
                            <Select v-model="form.status">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select status" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label>Tenant <span class="text-destructive">*</span></Label>
                            <Select v-model="form.rental_tenant_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select tenant" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="t in tenants" :key="t.value" :value="t.value">
                                        {{ t.label }} <span class="text-muted-foreground">· {{ t.email }}</span>
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.rental_tenant_id" />
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label>Property <span class="text-destructive">*</span></Label>
                            <Select v-model="selectedProperty">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select property" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="p in properties" :key="p.id" :value="p.id">{{ p.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex flex-col gap-1.5">
                            <Label>Building</Label>
                            <Select v-model="form.building_id" :disabled="!availableBuildings.length">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select building" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="b in availableBuildings" :key="b.id" :value="b.id">{{ b.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>

                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label>Unit <span class="text-destructive">*</span></Label>
                            <Select v-model="form.unit_id" :disabled="!selectedProperty">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select unit" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="u in availableUnits" :key="u.id" :value="u.id">{{ u.unit_number }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.unit_id" />
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
                            <InputError :message="form.errors.move_in_date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Move-out Date</Label>
                            <Input v-model="form.move_out_date" type="date" />
                            <InputError :message="form.errors.move_out_date" />
                        </div>
                        <div v-if="leaseDuration" class="sm:col-span-2">
                            <p class="text-sm text-muted-foreground">
                                Lease Duration: <span class="font-medium text-foreground">{{ leaseDuration }}</span>
                            </p>
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
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="PHP">PHP — Philippine Peso</SelectItem>
                                    <SelectItem value="USD">USD — US Dollar</SelectItem>
                                    <SelectItem value="SGD">SGD — Singapore Dollar</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Monthly Rent <span class="text-destructive">*</span></Label>
                            <Input v-model="form.rent_amount" type="number" min="0" step="0.01" placeholder="0.00" />
                            <InputError :message="form.errors.rent_amount" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Security Deposit</Label>
                            <Input v-model="form.deposit_amount" type="number" min="0" step="0.01" placeholder="0.00" />
                            <InputError :message="form.errors.deposit_amount" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Advance Payment</Label>
                            <Input v-model="form.advance_payment" type="number" min="0" step="0.01" placeholder="0.00" />
                            <InputError :message="form.errors.advance_payment" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Utility Deposit</Label>
                            <Input v-model="form.utility_deposit" type="number" min="0" step="0.01" placeholder="0.00" />
                            <InputError :message="form.errors.utility_deposit" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Parking Fee</Label>
                            <Input v-model="form.parking_fee" type="number" min="0" step="0.01" placeholder="0.00" />
                            <InputError :message="form.errors.parking_fee" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Other Charges</Label>
                            <Input v-model="form.other_charges" type="number" min="0" step="0.01" placeholder="0.00" />
                            <InputError :message="form.errors.other_charges" />
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
                            <Label>Billing Cycle <span class="text-destructive">*</span></Label>
                            <Select v-model="form.billing_cycle">
                                <SelectTrigger>
                                    <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="c in billingCycles" :key="c.value" :value="c.value">{{ c.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Billing Day (1–28) <span class="text-destructive">*</span></Label>
                            <Input v-model.number="form.billing_day" type="number" min="1" max="28" />
                            <InputError :message="form.errors.billing_day" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Generate Invoice (days before due)</Label>
                            <Input v-model="form.generate_days_before" type="number" min="0" max="30" placeholder="e.g. 7" />
                            <InputError :message="form.errors.generate_days_before" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Issue Date Offset (days)</Label>
                            <Input v-model.number="form.issue_date_offset" type="number" min="0" max="28" placeholder="0" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card>
                    <CardContent class="pt-6">
                        <div class="flex flex-col gap-1.5">
                            <Label>Notes</Label>
                            <Textarea v-model="form.notes" placeholder="Additional notes about this lease…" rows="3" />
                            <InputError :message="form.errors.notes" />
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Summary Sidebar -->
            <div class="flex flex-col gap-4">
                <Card class="sticky top-4">
                    <CardHeader>
                        <CardTitle class="text-sm text-muted-foreground">Lease Summary</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm">
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Tenant</p>
                            <p class="mt-0.5 font-medium">{{ selectedTenant?.label ?? '—' }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Unit</p>
                            <p class="mt-0.5 font-medium">{{ selectedUnit?.unit_number ?? '—' }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Duration</p>
                            <p class="mt-0.5 font-medium">{{ leaseDuration ?? '—' }}</p>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total Monthly</p>
                            <p class="mt-0.5 text-lg font-bold">
                                {{ form.currency }} {{ totalMonthly.toFixed(2) }}
                            </p>
                            <div v-if="Number(form.rent_amount)" class="mt-1 space-y-0.5 text-xs text-muted-foreground">
                                <div class="flex justify-between">
                                    <span>Rent</span>
                                    <span>{{ Number(form.rent_amount).toFixed(2) }}</span>
                                </div>
                                <div v-if="Number(form.parking_fee)" class="flex justify-between">
                                    <span>Parking</span>
                                    <span>{{ Number(form.parking_fee).toFixed(2) }}</span>
                                </div>
                                <div v-if="Number(form.other_charges)" class="flex justify-between">
                                    <span>Other</span>
                                    <span>{{ Number(form.other_charges).toFixed(2) }}</span>
                                </div>
                            </div>
                        </div>
                        <Separator />
                        <div>
                            <p class="text-xs font-medium uppercase tracking-wide text-muted-foreground">Total Deposits</p>
                            <p class="mt-0.5 font-semibold">
                                {{ form.currency }} {{ (Number(form.deposit_amount) + Number(form.advance_payment) + Number(form.utility_deposit)).toFixed(2) }}
                            </p>
                        </div>
                    </CardContent>
                </Card>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    {{ form.processing ? 'Creating…' : 'Create Lease' }}
                </Button>
                <Button variant="outline" class="w-full" as-child>
                    <Link href="/leases">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
