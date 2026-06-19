<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import type { OrgUser } from '@/types/organization';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const props = defineProps<{ user: OrgUser | null }>();
const emit = defineEmits<{ close: [] }>();

const form = useForm({});

function confirm() {
    if (!props.user) return;
    form.delete(`/organization/users/${props.user.id}`, {
        onSuccess: () => emit('close'),
    });
}
</script>

<template>
    <Dialog :open="!!user" @update:open="emit('close')">
        <DialogContent class="sm:max-w-sm">
            <DialogHeader>
                <DialogTitle>Delete User</DialogTitle>
                <DialogDescription>
                    Are you sure you want to delete <strong>{{ user?.name }}</strong>?
                    This action cannot be undone.
                </DialogDescription>
            </DialogHeader>

            <DialogFooter class="gap-2">
                <Button variant="outline" @click="emit('close')">Cancel</Button>
                <Button variant="destructive" :disabled="form.processing" @click="confirm">
                    {{ form.processing ? 'Deleting…' : 'Delete User' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
