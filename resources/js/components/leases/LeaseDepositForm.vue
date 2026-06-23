<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    open: boolean;
    leaseId: string;
    currency?: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const depositTypes   = [
    { value: 'security', label: 'Security Deposit' },
    { value: 'advance',  label: 'Advance Rent' },
    { value: 'utility',  label: 'Utility Deposit' },
];

const depositStatuses = [
    { value: 'pending',        label: 'Pending' },
    { value: 'paid',           label: 'Paid' },
    { value: 'partially_paid', label: 'Partially Paid' },
    { value: 'refunded',       label: 'Refunded' },
];

const form = useForm({
    type:             'security',
    amount:           '',
    payment_date:     '',
    status:           'pending',
    refund_amount:    '',
    deduction_amount: '',
    refund_date:      '',
    notes:            '',
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    form.type   = 'security';
    form.status = 'pending';
});

function submit() {
    form.post(`/leases/${props.leaseId}/deposits`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Record Deposit</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label>Deposit Type <span class="text-destructive">*</span></Label>
                        <Select v-model="form.type">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="t in depositTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Status <span class="text-destructive">*</span></Label>
                        <Select v-model="form.status">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in depositStatuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Amount ({{ currency ?? 'PHP' }}) <span class="text-destructive">*</span></Label>
                        <Input v-model="form.amount" type="number" min="0" step="0.01" placeholder="0.00" />
                        <InputError :message="form.errors.amount" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Payment Date</Label>
                        <Input v-model="form.payment_date" type="date" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Refund Amount</Label>
                        <Input v-model="form.refund_amount" type="number" min="0" step="0.01" placeholder="0.00" />
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Deduction Amount</Label>
                        <Input v-model="form.deduction_amount" type="number" min="0" step="0.01" placeholder="0.00" />
                    </div>

                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label>Refund Date</Label>
                        <Input v-model="form.refund_date" type="date" />
                    </div>

                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label>Notes</Label>
                        <Textarea v-model="form.notes" placeholder="Additional notes…" rows="2" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Record Deposit' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
