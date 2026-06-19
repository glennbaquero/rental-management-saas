<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import type { Role } from '@/types/organization';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

const props = defineProps<{
    open: boolean;
    roles: Role[];
}>();

const emit = defineEmits<{ close: [] }>();

const form = useForm({ email: '', role_id: '' });

function submit() {
    form.post('/organization/users/invite', {
        onSuccess: () => {
            form.reset();
            emit('close');
        },
    });
}

function close() {
    form.reset();
    form.clearErrors();
    emit('close');
}
</script>

<template>
    <Dialog :open="open" @update:open="close">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Invite Team Member</DialogTitle>
                <DialogDescription>
                    Send an invitation email to add a new user to your organization.
                </DialogDescription>
            </DialogHeader>

            <form class="space-y-4" @submit.prevent="submit">
                <div class="grid gap-1.5">
                    <Label for="invite-email">Email address <span class="text-destructive">*</span></Label>
                    <Input id="invite-email" v-model="form.email" type="email" placeholder="colleague@example.com" autofocus />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-1.5">
                    <Label for="invite-role">Role <span class="text-destructive">*</span></Label>
                    <Select v-model="form.role_id">
                        <SelectTrigger id="invite-role">
                            <SelectValue placeholder="Select a role" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="role in roles.filter(r => r.name !== 'owner')"
                                :key="role.id"
                                :value="role.id"
                            >
                                {{ role.display_name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.role_id" />
                </div>

                <DialogFooter class="gap-2">
                    <Button type="button" variant="outline" @click="close">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Sending…' : 'Send Invitation' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
