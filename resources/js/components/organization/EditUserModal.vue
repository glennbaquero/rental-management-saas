<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import InputError from '@/components/InputError.vue';
import type { OrgUser, Role } from '@/types/organization';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
    user: OrgUser | null;
    roles: Role[];
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({ name: '', phone: '', role_id: '' });

watch(() => props.user, (user) => {
    if (user) {
        form.name = user.name;
        form.phone = user.phone ?? '';
        form.role_id = user.role?.id ?? '';
        form.clearErrors();
    }
});

function submit() {
    if (!props.user) return;
    form.patch(`/organization/users/${props.user.id}`, {
        onSuccess: () => emit('close'),
    });
}

function close() {
    form.clearErrors();
    emit('close');
}
</script>

<template>
    <Dialog :open="!!user" @update:open="close">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Edit User</DialogTitle>
                <DialogDescription>Update {{ user?.name }}'s information and role.</DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="edit-name">Full Name <span class="text-destructive">*</span></Label>
                    <Input id="edit-name" v-model="form.name" placeholder="Full name" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="edit-phone">Phone</Label>
                    <Input id="edit-phone" v-model="form.phone" placeholder="+63 912 345 6789" />
                    <InputError :message="form.errors.phone" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="edit-role">Role <span class="text-destructive">*</span></Label>
                    <Select v-model="form.role_id">
                        <SelectTrigger id="edit-role">
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="role in roles" :key="role.id" :value="role.id">
                                {{ role.display_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role_id" />
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="outline" @click="close">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
