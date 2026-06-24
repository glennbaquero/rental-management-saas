<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import AccountantSection from '@/components/dashboard/AccountantSection.vue';
import OwnerSection from '@/components/dashboard/OwnerSection.vue';
import PropertyManagerSection from '@/components/dashboard/PropertyManagerSection.vue';
import StaffSection from '@/components/dashboard/StaffSection.vue';
import { dashboard } from '@/routes';
import type {
    AccountantDashboardProps,
    DashboardRole,
    OwnerDashboardProps,
    PropertyManagerDashboardProps,
    StaffDashboardProps,
} from '@/types/dashboard';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: dashboard() },
        ],
    },
});

const props = withDefaults(defineProps<{
    role?: DashboardRole;
    [key: string]: unknown;
}>(), { role: 'staff' });

const titleMap: Record<DashboardRole, string> = {
    owner:            'Owner Dashboard',
    property_manager: 'Property Manager Dashboard',
    accountant:       'Accountant Dashboard',
    staff:            'Staff Dashboard',
};
</script>

<template>
    <Head :title="titleMap[role ?? 'staff'] ?? 'Dashboard'" />

    <OwnerSection
        v-if="role === 'owner'"
        v-bind="props as unknown as OwnerDashboardProps"
    />
    <PropertyManagerSection
        v-else-if="role === 'property_manager'"
        v-bind="props as unknown as PropertyManagerDashboardProps"
    />
    <AccountantSection
        v-else-if="role === 'accountant'"
        v-bind="props as unknown as AccountantDashboardProps"
    />
    <StaffSection
        v-else
        v-bind="props as unknown as StaffDashboardProps"
    />
</template>
