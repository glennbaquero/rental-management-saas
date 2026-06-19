<script setup lang="ts">
import { Head } from '@inertiajs/vue3';

defineOptions({ layout: null });
</script>

<template>
    <Head title="Invitation Expired" />

    <div class="flex min-h-screen items-center justify-center bg-muted/40 p-4">
        <div class="w-full max-w-md text-center">
            <div class="overflow-hidden rounded-2xl border bg-card shadow-sm px-8 py-12">
                <div class="text-5xl mb-4">⏰</div>
                <h1 class="text-2xl font-bold tracking-tight">Invitation Expired</h1>
                <p class="mt-3 text-muted-foreground leading-relaxed">
                    This invitation link has expired or is no longer valid.
                    Please contact your organization administrator to request a new invitation.
                </p>
                <p class="mt-6 text-sm text-muted-foreground">
                    Already have an account?
                    <a href="/login" class="font-medium text-foreground underline underline-offset-4">Sign in</a>
                </p>
            </div>
        </div>
    </div>
</template>
