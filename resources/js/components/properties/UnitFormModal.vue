<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import {
    Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { Building, Unit, UnitType } from '@/types/property';

const props = defineProps<{
    open: boolean;
    propertyId: string;
    buildings: Building[];
    unitTypes: UnitType[];
    unit?: Unit | null;
}>();

const emit = defineEmits<{
    'update:open': [val: boolean];
}>();

const form = useForm({
    unit_number:    '',
    building_id:    'none',
    unit_type_id:   'none',
    floor:          '',
    area_sqm:       '',
    bedrooms:       0,
    bathrooms:      0,
    max_occupants:  2,
    rent_amount:    '',
    deposit_amount: '',
    status:         'available',
    notes:          '',
});

watch(() => props.unit, (u) => {
    if (u) {
        form.unit_number    = u.unit_number;
        form.building_id    = u.building?.id ?? 'none';
        form.unit_type_id   = u.unit_type?.id ?? 'none';
        form.floor          = u.floor ?? '';
        form.area_sqm       = u.area_sqm?.toString() ?? '';
        form.bedrooms       = u.bedrooms;
        form.bathrooms      = u.bathrooms;
        form.max_occupants  = u.max_occupants;
        form.rent_amount    = u.rent_amount.toString();
        form.deposit_amount = u.deposit_amount.toString();
        form.status         = u.status ?? 'available';
        form.notes          = u.notes ?? '';
    } else {
        form.reset();
        form.status = 'available';
    }
}, { immediate: true });

function submit() {
    const url = props.unit
        ? `/properties/${props.propertyId}/units/${props.unit.id}`
        : `/properties/${props.propertyId}/units`;

    const method = props.unit ? form.patch.bind(form) : form.post.bind(form);

    form.transform((data) => ({
        ...data,
        building_id:  data.building_id === 'none' ? '' : data.building_id,
        unit_type_id: data.unit_type_id === 'none' ? '' : data.unit_type_id,
    }));

    method(url, {
        onSuccess: () => {
            emit('update:open', false);
            form.reset();
            form.status = 'available';
        },
    });
}

const statuses = [
    { value: 'available',   label: 'Available' },
    { value: 'occupied',    label: 'Occupied' },
    { value: 'reserved',    label: 'Reserved' },
    { value: 'maintenance', label: 'Under Maintenance' },
    { value: 'unavailable', label: 'Unavailable' },
];
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ unit ? 'Edit Unit' : 'Add Unit' }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-1.5">
                        <Label>Unit Number <span class="text-destructive">*</span></Label>
                        <Input v-model="form.unit_number" placeholder="101" />
                        <p v-if="form.errors.unit_number" class="text-xs text-destructive">{{ form.errors.unit_number }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Floor</Label>
                        <Input v-model="form.floor" placeholder="1" />
                    </div>

                    <div class="space-y-1.5">
                        <Label>Building</Label>
                        <Select v-model="form.building_id">
                            <SelectTrigger><SelectValue placeholder="Select building" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem v-for="b in buildings" :key="b.id" :value="b.id">
                                    {{ b.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Unit Type</Label>
                        <Select v-model="form.unit_type_id">
                            <SelectTrigger><SelectValue placeholder="Select type" /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="none">None</SelectItem>
                                <SelectItem v-for="t in unitTypes" :key="t.id" :value="t.id">
                                    {{ t.name }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Monthly Rent (₱) <span class="text-destructive">*</span></Label>
                        <Input v-model="form.rent_amount" type="number" min="0" step="0.01" placeholder="18000" />
                        <p v-if="form.errors.rent_amount" class="text-xs text-destructive">{{ form.errors.rent_amount }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Security Deposit (₱) <span class="text-destructive">*</span></Label>
                        <Input v-model="form.deposit_amount" type="number" min="0" step="0.01" placeholder="36000" />
                        <p v-if="form.errors.deposit_amount" class="text-xs text-destructive">{{ form.errors.deposit_amount }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label>Floor Area (sqm)</Label>
                        <Input v-model="form.area_sqm" type="number" min="0" step="0.01" placeholder="42" />
                    </div>

                    <div class="space-y-1.5">
                        <Label>Max Occupants</Label>
                        <Input v-model.number="form.max_occupants" type="number" min="1" />
                    </div>

                    <div class="space-y-1.5">
                        <Label>Bedrooms</Label>
                        <Input v-model.number="form.bedrooms" type="number" min="0" />
                    </div>

                    <div class="space-y-1.5">
                        <Label>Bathrooms</Label>
                        <Input v-model.number="form.bathrooms" type="number" min="0" />
                    </div>

                    <div class="col-span-2 space-y-1.5">
                        <Label>Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                                    {{ s.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : (unit ? 'Update Unit' : 'Add Unit') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
