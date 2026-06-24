<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Building2, CreditCard, DollarSign, FileText, Plus, Ticket, UserRound } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import type { DashboardRole } from '@/types/dashboard';

const props = defineProps<{
    role: DashboardRole;
}>();

interface Action {
    label: string;
    href: string;
    icon: typeof Plus;
    roles: DashboardRole[];
    color: string;
}

const actions: Action[] = [
    {
        label: 'Add Property',
        href:  '/properties/create',
        icon:  Building2,
        roles: ['owner', 'property_manager'],
        color: 'text-blue-600 dark:text-blue-400',
    },
    {
        label: 'Add Tenant',
        href:  '/tenants/create',
        icon:  UserRound,
        roles: ['owner', 'property_manager'],
        color: 'text-violet-600 dark:text-violet-400',
    },
    {
        label: 'Create Lease',
        href:  '/leases/create',
        icon:  FileText,
        roles: ['owner', 'property_manager'],
        color: 'text-emerald-600 dark:text-emerald-400',
    },
    {
        label: 'Generate Invoice',
        href:  '/billing/invoices/create',
        icon:  CreditCard,
        roles: ['owner', 'accountant'],
        color: 'text-indigo-600 dark:text-indigo-400',
    },
    {
        label: 'Record Payment',
        href:  '/billing/payments/create',
        icon:  DollarSign,
        roles: ['owner', 'accountant'],
        color: 'text-teal-600 dark:text-teal-400',
    },
    {
        label: 'New Maintenance Ticket',
        href:  '/maintenance/create',
        icon:  Ticket,
        roles: ['owner', 'property_manager', 'staff'],
        color: 'text-amber-600 dark:text-amber-400',
    },
];

const visible = actions.filter(a => a.roles.includes(props.role));
</script>

<template>
    <div class="grid grid-cols-2 gap-2 sm:grid-cols-3">
        <Button
            v-for="action in visible"
            :key="action.label"
            variant="outline"
            class="h-auto flex-col gap-1.5 py-3 text-center"
            as-child
        >
            <Link :href="action.href">
                <component :is="action.icon" class="h-4 w-4" :class="action.color" />
                <span class="text-xs font-medium leading-tight">{{ action.label }}</span>
            </Link>
        </Button>
    </div>
</template>
