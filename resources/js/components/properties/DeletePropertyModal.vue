<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import {
    Dialog, DialogContent, DialogDescription, DialogFooter,
    DialogHeader, DialogTitle,
} from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import type { Property } from '@/types/property';

const props = defineProps<{
    property: Property | null;
    open: boolean;
}>();

const emit = defineEmits<{
    'update:open': [val: boolean];
}>();

const form = useForm({});

function confirm() {
    if (!props.property) return;
    form.delete(`/properties/${props.property.id}`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Delete Property</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete <strong>{{ property?.name }}</strong>?
                    This action cannot be undone.
                </DialogDescription>
            </DialogHeader>
            <DialogFooter>
                <Button variant="outline" @click="emit('update:open', false)">Cancel</Button>
                <Button variant="destructive" :disabled="form.processing" @click="confirm">
                    {{ form.processing ? 'Deleting…' : 'Delete Property' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
