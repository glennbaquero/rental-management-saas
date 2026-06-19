<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PermissionMatrix from '@/components/organization/PermissionMatrix.vue';
import type { PermissionCategories, Role } from '@/types/organization';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Organization', href: '/organization/settings' },
            { title: 'Roles & Permissions', href: '/organization/roles' },
        ],
    },
});

defineProps<{
    roles: Role[];
    permissionCategories: PermissionCategories;
}>();

const roleIcons: Record<string, string> = {
    owner: '👑',
    property_manager: '🏢',
    staff: '👤',
    accountant: '📊',
};

const roleDescriptions: Record<string, string> = {
    owner: 'Full system access including billing, users, and all features.',
    property_manager: 'Manages properties, tenants, leases, and maintenance.',
    staff: 'Handles maintenance requests and views tenant information.',
    accountant: 'Manages invoices, records payments, and generates financial reports.',
};
</script>

<template>
    <Head title="Roles & Permissions" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading title="Roles & Permissions" description="View the access levels for each role in your organization." />

        <!-- Role cards -->
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <Card v-for="role in roles" :key="role.id" class="relative overflow-hidden">
                <CardHeader class="pb-2">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <span class="text-2xl">{{ roleIcons[role.name] ?? '🔒' }}</span>
                            <CardTitle class="mt-2 text-base">{{ role.display_name }}</CardTitle>
                        </div>
                        <Badge variant="secondary" class="shrink-0 text-xs">
                            {{ role.users_count }} {{ role.users_count === 1 ? 'user' : 'users' }}
                        </Badge>
                    </div>
                </CardHeader>
                <CardContent>
                    <p class="text-xs text-muted-foreground leading-relaxed">
                        {{ roleDescriptions[role.name] ?? 'Custom role with specific permissions.' }}
                    </p>
                    <p class="mt-3 text-xs font-medium text-muted-foreground">
                        {{ (role.permissions ?? []).length }} permissions
                    </p>
                </CardContent>
            </Card>
        </div>

        <!-- Permission matrix -->
        <div>
            <h3 class="mb-4 text-lg font-semibold tracking-tight">Permission Matrix</h3>
            <PermissionMatrix :roles="roles" :categories="permissionCategories" />
        </div>
    </div>
</template>
