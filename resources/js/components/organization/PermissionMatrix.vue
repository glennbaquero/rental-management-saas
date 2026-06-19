<script setup lang="ts">
import type { PermissionCategories, Role } from '@/types/organization';

const props = defineProps<{
    roles: Role[];
    categories: PermissionCategories;
}>();

function hasPermission(role: Role, category: string, action: string): boolean {
    const perm = `${category}.${action}`;
    return (role.permissions ?? []).includes(perm);
}

function categoryLabel(cat: string): string {
    return cat.charAt(0).toUpperCase() + cat.slice(1).replace(/_/g, ' ');
}

function actionLabel(action: string): string {
    return action.charAt(0).toUpperCase() + action.slice(1).replace(/_/g, ' ');
}
</script>

<template>
    <div class="overflow-x-auto rounded-xl border">
        <table class="w-full text-sm">
            <thead>
                <tr class="border-b bg-muted/50">
                    <th class="sticky left-0 z-10 bg-muted/50 px-4 py-3 text-left font-semibold backdrop-blur min-w-[180px]">
                        Permission
                    </th>
                    <th
                        v-for="role in roles"
                        :key="role.id"
                        class="px-4 py-3 text-center font-semibold min-w-[110px]"
                    >
                        {{ role.display_name }}
                        <span class="block text-xs font-normal text-muted-foreground">{{ role.users_count }} users</span>
                    </th>
                </tr>
            </thead>
            <tbody>
                <template v-for="(actions, category) in categories" :key="category">
                    <!-- Category header row -->
                    <tr class="border-b bg-muted/20">
                        <td
                            :colspan="roles.length + 1"
                            class="sticky left-0 px-4 py-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground backdrop-blur"
                        >
                            {{ categoryLabel(category) }}
                        </td>
                    </tr>
                    <!-- Permission rows -->
                    <tr
                        v-for="action in actions"
                        :key="`${category}.${action}`"
                        class="border-b transition-colors hover:bg-muted/30"
                    >
                        <td class="sticky left-0 bg-card px-4 py-2.5 text-sm backdrop-blur">
                            {{ actionLabel(action) }}
                        </td>
                        <td
                            v-for="role in roles"
                            :key="role.id"
                            class="px-4 py-2.5 text-center"
                        >
                            <span
                                v-if="hasPermission(role, category, action)"
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/40 dark:text-green-400"
                                title="Allowed"
                            >
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                                </svg>
                            </span>
                            <span
                                v-else
                                class="inline-flex h-5 w-5 items-center justify-center rounded-full text-muted-foreground"
                                title="Not allowed"
                            >
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </span>
                        </td>
                    </tr>
                </template>
            </tbody>
        </table>
    </div>
</template>
