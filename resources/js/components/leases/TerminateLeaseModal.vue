<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { Lease } from '@/types/lease';

const props = defineProps<{
    open: boolean;
    lease: Lease;
    terminationReasons: { value: string; label: string }[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    termination_date:   '',
    termination_reason: '',
    move_out_date:      '',
    notes:              '',
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    form.termination_date = new Date().toISOString().split('T')[0];
});

function submit() {
    form.post(`/leases/${props.lease.id}/terminate`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Terminate Lease</DialogTitle>
            </DialogHeader>

            <div class="rounded-lg border border-destructive/30 bg-destructive/5 p-3 text-sm">
                <p class="font-medium text-destructive">⚠️ This action cannot be undone</p>
                <p class="text-muted-foreground">Lease <span class="font-mono font-medium text-foreground">{{ lease.lease_number }}</span> will be marked as terminated.</p>
            </div>

            <Separator />

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label>Termination Date <span class="text-destructive">*</span></Label>
                    <Input v-model="form.termination_date" type="date" />
                    <InputError :message="form.errors.termination_date" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>Reason <span class="text-destructive">*</span></Label>
                    <Select v-model="form.termination_reason">
                        <SelectTrigger>
                            <SelectValue placeholder="Select reason" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="r in terminationReasons" :key="r.value" :value="r.value">{{ r.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.termination_reason" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>Expected Move-out Date</Label>
                    <Input v-model="form.move_out_date" type="date" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>Remarks</Label>
                    <Textarea v-model="form.notes" placeholder="Additional remarks about the termination…" rows="3" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" variant="destructive" :disabled="form.processing">
                        {{ form.processing ? 'Terminating…' : 'Terminate Lease' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
