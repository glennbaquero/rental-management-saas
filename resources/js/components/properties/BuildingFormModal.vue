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
import type { Building } from '@/types/property';

const props = defineProps<{
    open: boolean;
    propertyId: string;
    building?: Building | null;
}>();

const emit = defineEmits<{
    'update:open': [val: boolean];
}>();

const form = useForm({
    name:        '',
    code:        '',
    floors:      1,
    description: '',
    status:      'active',
});

watch(() => props.building, (b) => {
    if (b) {
        form.name        = b.name;
        form.code        = b.code ?? '';
        form.floors      = b.floors;
        form.description = b.description ?? '';
        form.status      = b.status ?? 'active';
    } else {
        form.reset();
    }
}, { immediate: true });

function submit() {
    const url = props.building
        ? `/properties/${props.propertyId}/buildings/${props.building.id}`
        : `/properties/${props.propertyId}/buildings`;

    const method = props.building ? form.patch.bind(form) : form.post.bind(form);

    method(url, {
        onSuccess: () => {
            emit('update:open', false);
            form.reset();
        },
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ building ? 'Edit Building' : 'Add Building' }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 space-y-1.5">
                        <Label for="bld-name">Building Name <span class="text-destructive">*</span></Label>
                        <Input id="bld-name" v-model="form.name" placeholder="Tower A" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="space-y-1.5">
                        <Label for="bld-code">Code</Label>
                        <Input id="bld-code" v-model="form.code" placeholder="BLD-A" />
                    </div>

                    <div class="space-y-1.5">
                        <Label for="bld-floors">Floors <span class="text-destructive">*</span></Label>
                        <Input id="bld-floors" v-model.number="form.floors" type="number" min="1" max="200" />
                        <p v-if="form.errors.floors" class="text-xs text-destructive">{{ form.errors.floors }}</p>
                    </div>

                    <div class="col-span-2 space-y-1.5">
                        <Label for="bld-status">Status</Label>
                        <Select v-model="form.status">
                            <SelectTrigger id="bld-status"><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem value="active">Active</SelectItem>
                                <SelectItem value="inactive">Inactive</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : (building ? 'Update' : 'Add Building') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
