<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

defineOptions({ layout: null });

const props = defineProps<{
    invitation: {
        token: string;
        email: string;
        role: string;
        expires_at: string;
    };
}>();

const form = useForm({
    name: '',
    password: '',
    password_confirmation: '',
});

function submit() {
    form.post(`/accept-invitation/${props.invitation.token}`);
}

function formatDate(iso: string) {
    return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}
</script>

<template>
    <Head title="Accept Invitation" />

    <div class="flex min-h-screen items-center justify-center bg-muted/40 p-4">
        <div class="w-full max-w-md">
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm">
                <!-- Header -->
                <div class="border-b bg-card px-8 py-6">
                    <h1 class="text-xl font-semibold tracking-tight">You've been invited!</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Create your account to join as
                        <strong class="text-foreground">{{ invitation.role }}</strong>.
                    </p>
                </div>

                <!-- Form -->
                <form class="space-y-5 px-8 py-6" @submit.prevent="submit">
                    <div class="grid gap-1.5">
                        <Label for="email">Email address</Label>
                        <Input id="email" :value="invitation.email" type="email" disabled class="opacity-70" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="name">Full Name <span class="text-destructive">*</span></Label>
                        <Input id="name" v-model="form.name" type="text" placeholder="Your full name" autocomplete="name" autofocus />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="password">Password <span class="text-destructive">*</span></Label>
                        <Input id="password" v-model="form.password" type="password" placeholder="Create a password" autocomplete="new-password" />
                        <InputError :message="form.errors.password" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="password_confirmation">Confirm Password <span class="text-destructive">*</span></Label>
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" placeholder="Confirm your password" autocomplete="new-password" />
                        <InputError :message="form.errors.password_confirmation" />
                    </div>

                    <Button type="submit" class="w-full" :disabled="form.processing">
                        {{ form.processing ? 'Creating account…' : 'Accept Invitation' }}
                    </Button>

                    <p class="text-center text-xs text-muted-foreground">
                        Invitation expires {{ formatDate(invitation.expires_at) }}.
                    </p>
                </form>
            </div>
        </div>
    </div>
</template>
