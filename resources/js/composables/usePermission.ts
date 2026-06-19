import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Permission } from '@/types/organization';

type AuthUser = {
    role?: {
        name: string;
        permissions?: string[];
    } | null;
};

export function usePermission() {
    const page = usePage();

    const authUser = computed<AuthUser | null>(() => (page.props.auth as { user: AuthUser | null })?.user ?? null);

    const permissions = computed<string[]>(() => authUser.value?.role?.permissions ?? []);

    const isOwner = computed<boolean>(() => authUser.value?.role?.name === 'owner');

    function can(permission: Permission): boolean {
        if (isOwner.value) return true;
        return permissions.value.includes(permission);
    }

    function canAny(...perms: Permission[]): boolean {
        return perms.some((p) => can(p));
    }

    function canAll(...perms: Permission[]): boolean {
        return perms.every((p) => can(p));
    }

    return { can, canAny, canAll, isOwner, permissions };
}
