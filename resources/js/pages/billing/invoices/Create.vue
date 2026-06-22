<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Trash2 } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { LeaseOption, SelectOption } from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Invoices', href: '/billing/invoices' },
            { title: 'New Invoice', href: '#' },
        ],
    },
});

const props = defineProps<{
    leases: LeaseOption[];
    invoiceTypes: SelectOption[];
    statuses: SelectOption[];
}>();

const selectedLease = ref<LeaseOption | null>(null);

const form = useForm({
    lease_id:             '',
    type:                 'rent',
    issue_date:           new Date().toISOString().split('T')[0],
    due_date:             '',
    billing_period_start: '',
    billing_period_end:   '',
    discount_amount:      0,
    tax_amount:           0,
    notes:                '',
    items: [
        { description: '', quantity: 1, unit_price: 0, type: 'rent' },
    ] as { description: string; quantity: number; unit_price: number; type: string }[],
});

watch(() => form.lease_id, (leaseId) => {
    selectedLease.value = props.leases.find(l => l.value === leaseId) ?? null;
    if (selectedLease.value && form.items.length === 1 && form.items[0].description === '') {
        form.items[0].description = 'Monthly Rent';
        form.items[0].unit_price  = selectedLease.value.rent_amount;
        form.items[0].type        = 'rent';
    }
});

function addItem() {
    form.items.push({ description: '', quantity: 1, unit_price: 0, type: 'rent' });
}

function removeItem(index: number) {
    form.items.splice(index, 1);
}

const subtotal = computed(() =>
    form.items.reduce((sum, item) => sum + item.quantity * item.unit_price, 0)
);

const grandTotal = computed(() =>
    subtotal.value - Number(form.discount_amount) + Number(form.tax_amount)
);

function submit() {
    form.post('/billing/invoices');
}
</script>

<template>
    <Head title="Create Invoice" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/billing/invoices">
                    <ArrowLeft class="mr-1 h-4 w-4" />
                    Back
                </Link>
            </Button>
            <Separator orientation="vertical" class="h-6" />
            <Heading title="Add Invoice" description="Create a manual invoice for a lease." />
        </div>

        <form class="grid grid-cols-1 gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="flex flex-col gap-6 lg:col-span-2">

                <!-- Invoice Info -->
                <Card>
                    <CardHeader><CardTitle class="text-base">Invoice Information</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div class="space-y-1.5">
                            <Label>Lease *</Label>
                            <Select v-model="form.lease_id">
                                <SelectTrigger>
                                    <SelectValue placeholder="Select a lease…" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="l in leases" :key="l.value" :value="l.value">
                                        {{ l.label }}
                                    </SelectItem>
                                </SelectContent>
                            </Select>
                            <InputError :message="form.errors.lease_id" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Invoice Type *</Label>
                                <Select v-model="form.type">
                                    <SelectTrigger>
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="t in invoiceTypes" :key="t.value" :value="t.value">
                                            {{ t.label }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>
                            <div class="space-y-1.5">
                                <Label>Issue Date</Label>
                                <Input v-model="form.issue_date" type="date" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Billing Period Start</Label>
                                <Input v-model="form.billing_period_start" type="date" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Billing Period End</Label>
                                <Input v-model="form.billing_period_end" type="date" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Due Date *</Label>
                            <Input v-model="form.due_date" type="date" />
                            <InputError :message="form.errors.due_date" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Line Items -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-base">Line Items</CardTitle>
                        <Button type="button" variant="outline" size="sm" @click="addItem">
                            <Plus class="mr-1 h-4 w-4" />
                            Add Item
                        </Button>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="(item, i) in form.items"
                            :key="i"
                            class="grid grid-cols-12 gap-2 items-end"
                        >
                            <div class="col-span-5 space-y-1.5">
                                <Label v-if="i === 0">Description</Label>
                                <Input v-model="item.description" placeholder="e.g. Monthly Rent" />
                            </div>
                            <div class="col-span-2 space-y-1.5">
                                <Label v-if="i === 0">Qty</Label>
                                <Input v-model.number="item.quantity" type="number" min="0.01" step="0.01" />
                            </div>
                            <div class="col-span-3 space-y-1.5">
                                <Label v-if="i === 0">Unit Price</Label>
                                <Input v-model.number="item.unit_price" type="number" min="0" step="0.01" />
                            </div>
                            <div class="col-span-1 space-y-1.5 text-right text-sm font-medium">
                                <div v-if="i === 0" class="text-xs text-muted-foreground">Total</div>
                                <div>{{ (item.quantity * item.unit_price).toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</div>
                            </div>
                            <div class="col-span-1">
                                <Button
                                    v-if="form.items.length > 1"
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    class="h-9 w-9 p-0 text-destructive hover:text-destructive"
                                    @click="removeItem(i)"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                        </div>

                        <Separator />
                        <div class="space-y-1 text-sm">
                            <div class="flex justify-between">
                                <span class="text-muted-foreground">Subtotal</span>
                                <span class="font-medium">₱{{ subtotal.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <Label class="text-muted-foreground w-24 shrink-0">Discount</Label>
                                <Input v-model.number="form.discount_amount" type="number" min="0" step="0.01" class="h-8 w-32" />
                            </div>
                            <div class="flex items-center justify-between gap-4">
                                <Label class="text-muted-foreground w-24 shrink-0">Tax</Label>
                                <Input v-model.number="form.tax_amount" type="number" min="0" step="0.01" class="h-8 w-32" />
                            </div>
                            <Separator />
                            <div class="flex justify-between text-base font-bold">
                                <span>Total</span>
                                <span>₱{{ grandTotal.toLocaleString('en-PH', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader><CardTitle class="text-base">Notes</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea v-model="form.notes" placeholder="Optional notes for this invoice…" rows="4" />
                    </CardContent>
                </Card>

                <Button type="submit" class="w-full" :disabled="form.processing">
                    {{ form.processing ? 'Creating…' : 'Create Invoice' }}
                </Button>
                <Button variant="outline" as-child class="w-full">
                    <Link href="/billing/invoices">Cancel</Link>
                </Button>
            </div>
        </form>
    </div>
</template>
