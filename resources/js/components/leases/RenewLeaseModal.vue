<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { Lease } from '@/types/lease';

const props = defineProps<{
    open: boolean;
    lease: Lease;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    new_end_date:    '',
    new_rent_amount: '',
    reason:          '',
    notes:           '',
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    form.new_rent_amount = String(props.lease.rent_amount ?? '');
});

function submit() {
    form.post(`/leases/${props.lease.id}/renew`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Renew Lease</DialogTitle>
            </DialogHeader>

            <div class="rounded-lg bg-muted/50 p-3 text-sm">
                <p class="font-medium">{{ lease.lease_number }}</p>
                <p class="text-muted-foreground">Current end date: <span class="font-medium text-foreground">{{ lease.end_date }}</span></p>
            </div>

            <Separator />

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label>New End Date <span class="text-destructive">*</span></Label>
                    <Input v-model="form.new_end_date" type="date" :min="lease.end_date ?? undefined" />
                    <InputError :message="form.errors.new_end_date" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>New Monthly Rent <span class="text-destructive">*</span></Label>
                    <Input v-model="form.new_rent_amount" type="number" min="0" step="0.01" placeholder="0.00" />
                    <InputError :message="form.errors.new_rent_amount" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>Reason for Renewal</Label>
                    <Textarea v-model="form.reason" placeholder="Optional reason…" rows="2" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>Notes</Label>
                    <Textarea v-model="form.notes" placeholder="Additional notes…" rows="2" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Renewing…' : 'Renew Lease' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
