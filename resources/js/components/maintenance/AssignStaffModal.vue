<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { MaintenanceTicketDetail, SelectOption } from '@/types/maintenance';

const props = defineProps<{
    open: boolean;
    ticket: MaintenanceTicketDetail;
    staff: SelectOption[];
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const form = useForm({
    user_id: '',
    assignee_type: 'maintenance_staff',
    contractor_name: '',
    contractor_contact: '',
    assigned_date: new Date().toISOString().split('T')[0],
    estimated_completion: '',
    remarks: '',
    is_primary: true,
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    form.assigned_date = new Date().toISOString().split('T')[0];
    form.is_primary = true;
});

function submit() {
    form.post(`/maintenance/${props.ticket.id}/assign`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>Assign Staff</DialogTitle>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="space-y-1.5">
                    <Label>Assignee Type</Label>
                    <Select v-model="form.assignee_type">
                        <SelectTrigger>
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="property_manager">Property Manager</SelectItem>
                            <SelectItem value="maintenance_staff">Maintenance Staff</SelectItem>
                            <SelectItem value="external_contractor">External Contractor</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.assignee_type" />
                </div>

                <div v-if="form.assignee_type !== 'external_contractor'" class="space-y-1.5">
                    <Label>Staff Member</Label>
                    <Select v-model="form.user_id">
                        <SelectTrigger>
                            <SelectValue placeholder="Select staff" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="s in staff" :key="s.value" :value="s.value">
                                {{ s.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.user_id" />
                </div>

                <template v-else>
                    <div class="space-y-1.5">
                        <Label>Contractor Name <span class="text-destructive">*</span></Label>
                        <Input v-model="form.contractor_name" placeholder="Full name" />
                        <InputError :message="form.errors.contractor_name" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Contractor Contact</Label>
                        <Input v-model="form.contractor_contact" placeholder="Phone or email" />
                        <InputError :message="form.errors.contractor_contact" />
                    </div>
                </template>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Assigned Date <span class="text-destructive">*</span></Label>
                        <Input type="date" v-model="form.assigned_date" />
                        <InputError :message="form.errors.assigned_date" />
                    </div>
                    <div class="space-y-1.5">
                        <Label>Estimated Completion</Label>
                        <Input type="date" v-model="form.estimated_completion" />
                        <InputError :message="form.errors.estimated_completion" />
                    </div>
                </div>

                <div class="space-y-1.5">
                    <Label>Remarks</Label>
                    <Textarea v-model="form.remarks" rows="3" placeholder="Additional notes..." />
                    <InputError :message="form.errors.remarks" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="$emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Assigning…' : 'Assign Ticket' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
