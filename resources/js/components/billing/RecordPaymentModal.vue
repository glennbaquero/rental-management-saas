<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { Invoice, SelectOption } from '@/types/billing';

const props = defineProps<{
    open: boolean;
    invoice: Invoice;
    paymentMethods: SelectOption[];
}>();

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
}>();

const proofPreview = ref<string | null>(null);
const fileInput    = ref<HTMLInputElement | null>(null);

const form = useForm({
    amount:           props.invoice.balance_due,
    payment_method:   'cash',
    payment_date:     new Date().toISOString().split('T')[0],
    reference_number: '',
    transaction_id:   '',
    notes:            '',
    proof_of_payment: null as File | null,
});

watch(() => props.open, (val) => {
    if (val) {
        form.reset();
        form.amount         = props.invoice.balance_due;
        form.payment_date   = new Date().toISOString().split('T')[0];
        proofPreview.value  = null;
    }
});

function handleProofChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.proof_of_payment = file;
    if (file.type.startsWith('image/')) {
        proofPreview.value = URL.createObjectURL(file);
    } else {
        proofPreview.value = null;
    }
}

function submit() {
    form.post(`/billing/invoices/${props.invoice.id}/record-payment`, {
        forceFormData: true,
        onSuccess: () => {
            emit('update:open', false);
        },
    });
}

function formatCurrency(amount: number) {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Record Payment</DialogTitle>
            </DialogHeader>

            <div class="space-y-1 rounded-lg bg-muted/50 px-4 py-3 text-sm">
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Invoice</span>
                    <span class="font-medium">{{ invoice.invoice_number }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-muted-foreground">Balance Due</span>
                    <span class="font-bold text-destructive">{{ formatCurrency(invoice.balance_due) }}</span>
                </div>
            </div>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label for="amount">Amount *</Label>
                    <Input
                        id="amount"
                        v-model="form.amount"
                        type="number"
                        step="0.01"
                        min="0.01"
                        :max="invoice.balance_due"
                        placeholder="0.00"
                    />
                    <InputError :message="form.errors.amount" />
                </div>

                <div class="space-y-1.5">
                    <Label for="payment_method">Payment Method *</Label>
                    <Select v-model="form.payment_method">
                        <SelectTrigger id="payment_method">
                            <SelectValue placeholder="Select method" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="m in paymentMethods" :key="m.value" :value="m.value">
                                {{ m.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.payment_method" />
                </div>

                <div class="space-y-1.5">
                    <Label for="payment_date">Payment Date *</Label>
                    <Input id="payment_date" v-model="form.payment_date" type="date" />
                    <InputError :message="form.errors.payment_date" />
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div class="space-y-1.5">
                        <Label for="reference_number">Reference No.</Label>
                        <Input id="reference_number" v-model="form.reference_number" placeholder="REF-001" />
                    </div>
                    <div class="space-y-1.5">
                        <Label for="transaction_id">Transaction ID</Label>
                        <Input id="transaction_id" v-model="form.transaction_id" placeholder="TXN-001" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label for="notes">Notes</Label>
                    <Textarea id="notes" v-model="form.notes" placeholder="Optional notes…" rows="2" />
                </div>

                <div class="space-y-1.5">
                    <Label>Proof of Payment</Label>
                    <div
                        class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-lg border-2 border-dashed border-border p-4 transition-colors hover:border-primary/50"
                        @click="fileInput?.click()"
                    >
                        <img v-if="proofPreview" :src="proofPreview" class="max-h-24 rounded object-contain" />
                        <Upload v-else class="h-6 w-6 text-muted-foreground" />
                        <p class="text-xs text-muted-foreground">
                            {{ form.proof_of_payment ? form.proof_of_payment.name : 'JPG, PNG or PDF — max 5MB' }}
                        </p>
                        <input
                            ref="fileInput"
                            type="file"
                            accept="image/*,application/pdf"
                            class="hidden"
                            @change="handleProofChange"
                        />
                    </div>
                    <InputError :message="form.errors.proof_of_payment" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="$emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save Payment' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
