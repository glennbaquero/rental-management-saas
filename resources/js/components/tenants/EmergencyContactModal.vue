<script setup lang="ts">
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
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
import { Checkbox } from '@/components/ui/checkbox';
import { Textarea } from '@/components/ui/textarea';
import type { EmergencyContact } from '@/types/tenant';

const props = defineProps<{
    open: boolean;
    tenantId: string;
    contact?: EmergencyContact | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isEdit = () => !!props.contact;

const RELATIONSHIP_SUGGESTIONS = ['Father', 'Mother', 'Sibling', 'Spouse', 'Child', 'Friend', 'Relative', 'Guardian'];

const form = useForm({
    name:             '',
    relationship:     '',
    phone:            '',
    alternate_number: '',
    email:            '',
    address:          '',
    is_primary:       false,
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    const c = props.contact;
    if (c) {
        form.name             = c.name;
        form.relationship     = c.relationship;
        form.phone            = c.phone;
        form.alternate_number = c.alternate_number ?? '';
        form.email            = c.email ?? '';
        form.address          = c.address ?? '';
        form.is_primary       = c.is_primary;
    }
});

function submit() {
    const url = isEdit()
        ? `/tenants/${props.tenantId}/emergency-contacts/${props.contact!.id}`
        : `/tenants/${props.tenantId}/emergency-contacts`;

    const options = {
        onSuccess: () => emit('update:open', false),
    };

    if (isEdit()) {
        form.patch(url, options);
    } else {
        form.post(url, options);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Edit Contact' : 'Add Emergency Contact' }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label>Full Name <span class="text-destructive">*</span></Label>
                        <Input v-model="form.name" placeholder="Maria Cruz" />
                        <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                    </div>

                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label>Relationship <span class="text-destructive">*</span></Label>
                        <Input
                            v-model="form.relationship"
                            placeholder="Mother"
                            list="relationship-suggestions"
                        />
                        <datalist id="relationship-suggestions">
                            <option v-for="s in RELATIONSHIP_SUGGESTIONS" :key="s" :value="s" />
                        </datalist>
                        <p v-if="form.errors.relationship" class="text-xs text-destructive">{{ form.errors.relationship }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Mobile Number <span class="text-destructive">*</span></Label>
                        <Input v-model="form.phone" placeholder="0917-123-4567" />
                        <p v-if="form.errors.phone" class="text-xs text-destructive">{{ form.errors.phone }}</p>
                    </div>

                    <div class="flex flex-col gap-1.5">
                        <Label>Alternate Number</Label>
                        <Input v-model="form.alternate_number" placeholder="0932-000-0000" />
                    </div>

                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label>Email</Label>
                        <Input v-model="form.email" type="email" placeholder="maria@email.com" />
                    </div>

                    <div class="col-span-2 flex flex-col gap-1.5">
                        <Label>Address</Label>
                        <Textarea v-model="form.address" placeholder="123 Main St, Quezon City" rows="2" />
                    </div>

                    <div class="col-span-2 flex items-center gap-3 rounded-lg border p-3">
                        <Checkbox
                            id="is_primary"
                            :checked="form.is_primary"
                            @update:checked="form.is_primary = $event"
                        />
                        <div>
                            <label for="is_primary" class="text-sm font-medium cursor-pointer">Primary Contact</label>
                            <p class="text-xs text-muted-foreground">Mark as the main emergency contact</p>
                        </div>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : (isEdit() ? 'Save Changes' : 'Add Contact') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
