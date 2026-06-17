<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Building2, CreditCard, Users, Wrench } from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

const page = usePage();
const name = page.props.name;

defineProps<{
    title?: string;
    description?: string;
}>();

const features = [
    { icon: Building2, text: 'Manage properties and units effortlessly' },
    { icon: CreditCard, text: 'Track rent payments and invoices' },
    { icon: Wrench, text: 'Handle maintenance requests end-to-end' },
    { icon: Users, text: 'Multi-tenant, built to scale with your business' },
];
</script>

<template>
    <div class="relative grid h-dvh flex-col items-center justify-center px-8 sm:px-0 lg:max-w-none lg:grid-cols-2 lg:px-0">
        <!-- Left branding panel -->
        <div class="relative hidden h-full flex-col bg-muted p-10 text-white lg:flex dark:border-r">
            <div class="absolute inset-0 bg-zinc-900" />

            <Link
                :href="home()"
                class="relative z-20 flex items-center gap-2 text-lg font-semibold"
            >
                <AppLogoIcon class="size-8 fill-current text-white" />
                {{ name }}
            </Link>

            <div class="relative z-20 mt-auto">
                <p class="mb-8 text-2xl font-light leading-snug text-zinc-100">
                    Modern Property Management<br />
                    <span class="text-zinc-400">for Everyone.</span>
                </p>

                <ul class="space-y-5">
                    <li
                        v-for="feature in features"
                        :key="feature.text"
                        class="flex items-center gap-3"
                    >
                        <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-md bg-zinc-800">
                            <component :is="feature.icon" class="size-4 text-zinc-300" />
                        </div>
                        <span class="text-sm text-zinc-300">{{ feature.text }}</span>
                    </li>
                </ul>
            </div>

            <p class="relative z-20 mt-10 text-xs text-zinc-600">
                &copy; {{ new Date().getFullYear() }} {{ name }}. All rights reserved.
            </p>
        </div>

        <!-- Right form panel -->
        <div class="flex h-full items-center justify-center overflow-y-auto py-10 lg:p-8">
            <div class="mx-auto flex w-full flex-col justify-center space-y-6 sm:w-[440px]">
                <div v-if="title || description" class="flex flex-col space-y-2 text-center">
                    <h1 v-if="title" class="text-xl font-medium tracking-tight">
                        {{ title }}
                    </h1>
                    <p v-if="description" class="text-sm text-muted-foreground">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
