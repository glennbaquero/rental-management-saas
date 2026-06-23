<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';
import type { MaintenanceTicketDetail } from '@/types/maintenance';

const props = defineProps<{
    open: boolean;
    ticket: MaintenanceTicketDetail;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    cost_type: '',
    description: '',
    amount: '',
    status: 'pending',
    receipt: null as File | null,
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
});

function onReceiptChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    form.receipt = file ?? null;
}

function submit() {
    form.post(`/maintenance/${props.ticket.id}/costs`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Add Cost Entry</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label>Cost Type <span class="text-destructive">*</span></Label>
                    <Select v-model="form.cost_type">
                        <SelectTrigger>
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="labor">Labor</SelectItem>
                            <SelectItem value="material">Material</SelectItem>
                            <SelectItem value="contractor_fee">Contractor Fee</SelectItem>
                            <SelectItem value="transportation">Transportation</SelectItem>
                            <SelectItem value="miscellaneous">Miscellaneous</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.cost_type" />
                </div>

                <div class="space-y-1.5">
                    <Label>Description <span class="text-destructive">*</span></Label>
                    <Input v-model="form.description" placeholder="e.g. Labor cost for plumber" />
                    <InputError :message="form.errors.description" />
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Amount (₱) <span class="text-destructive">*</span></Label>
                        <Input type="number" v-model="form.amount" min="0" step="0.01" placeholder="0.00" />
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="pending">Pending</SelectItem>
                                <SelectItem value="approved">Approved</SelectItem>
                                <SelectItem value="paid">Paid</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.status" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label>Receipt (optional)</Label>
                    <Input type="file" accept=".jpg,.jpeg,.png,.pdf" @change="onReceiptChange" />
                    <InputError :message="form.errors.receipt" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="$emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Add Cost' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
