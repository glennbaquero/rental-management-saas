<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch, onUnmounted } from 'vue';
import Heading from '@/components/Heading.vue';
import DeleteUserModal from '@/components/organization/DeleteUserModal.vue';
import EditUserModal from '@/components/organization/EditUserModal.vue';
import InviteUserModal from '@/components/organization/InviteUserModal.vue';
import RoleBadge from '@/components/organization/RoleBadge.vue';
import UserStatusBadge from '@/components/organization/UserStatusBadge.vue';
import { usePermission } from '@/composables/usePermission';
import type { OrgUser, PaginatedUsers, Role } from '@/types/organization';

const USERS_URL = '/organization/users';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Organization', href: '/organization/settings' },
            { title: 'Users', href: USERS_URL },
        ],
    },
});

const props = defineProps<{
    users: PaginatedUsers;
    roles: Role[];
    filters: { search?: string; role?: string };
}>();

const { can } = usePermission();
const canManage = can('organization.manage_users');

const search = ref(props.filters.search ?? '');
const roleFilter = ref(props.filters.role ?? '');

let searchTimer: ReturnType<typeof setTimeout>;
watch(search, (val) => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        router.get(USERS_URL, { search: val || undefined, role: roleFilter.value || undefined }, { preserveState: true, replace: true });
    }, 350);
});

const isLeaving = ref(false);

const removeBeforeListener = router.on('before', (event: Event) => {
    clearTimeout(searchTimer);
    const visit = (event as CustomEvent<{ visit: { url: URL } }>).detail?.visit;
    if (!visit?.url?.pathname?.startsWith(USERS_URL)) {
        isLeaving.value = true;
    }
});

const removeFinishListener = router.on('finish', () => {
    isLeaving.value = false;
});

onUnmounted(() => {
    clearTimeout(searchTimer);
    removeBeforeListener();
    removeFinishListener();
});

function handleRoleFilterChange(val: string) {
    roleFilter.value = val;
    router.get(USERS_URL, { search: search.value || undefined, role: val || undefined }, { preserveState: true, replace: true });
}

function getInitials(name: string) {
    return name.split(' ').map((n) => n[0]).slice(0, 2).join('').toUpperCase();
}

// Modal state
const showInvite = ref(false);
const editingUser = ref<OrgUser | null>(null);
const deletingUser = ref<OrgUser | null>(null);

function openEdit(user: OrgUser) { editingUser.value = user; }
function openDelete(user: OrgUser) { deletingUser.value = user; }

function resetPassword(user: OrgUser) {
    router.post(`/organization/users/${user.id}/reset-password`, {}, {
        onSuccess: () => {},
    });
}

function toggleStatus(user: OrgUser) {
    router.patch(`/organization/users/${user.id}/toggle-status`);
}
</script>

<template>
    <Head title="Users" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex items-center justify-between gap-4">
            <Heading title="Users" description="Manage your team members and their access." />
            <Button v-if="canManage" @click="showInvite = true">
                + Invite User
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <Input v-model="search" placeholder="Search by name or email…" class="max-w-sm" />
            </div>
            <!-- <Select v-if="!isLeaving" :model-value="roleFilter" @update:model-value="handleRoleFilterChange">
                <SelectTrigger class="w-full sm:w-44">
                    <SelectValue placeholder="All roles" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="">All roles</SelectItem>
                    <SelectItem v-for="role in roles" :key="role.id" :value="role.name">
                        {{ role.display_name }}
                    </SelectItem>
                </SelectContent>
            </Select> -->
        </div>

        <!-- Table -->
        <div class="overflow-hidden rounded-xl border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>User</TableHead>
                        <TableHead class="hidden sm:table-cell">Role</TableHead>
                        <TableHead class="hidden md:table-cell">Status</TableHead>
                        <TableHead class="hidden lg:table-cell">Last Login</TableHead>
                        <TableHead v-if="canManage" class="w-10" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <!-- Empty state -->
                    <TableRow v-if="users.data.length === 0">
                        <TableCell :colspan="canManage ? 5 : 4" class="py-16 text-center">
                            <div class="flex flex-col items-center gap-2">
                                <p class="text-lg font-medium text-foreground">No users found</p>
                                <p class="text-sm text-muted-foreground">
                                    {{ search || roleFilter ? 'Try adjusting your search or filter.' : 'Invite your first team member to get started.' }}
                                </p>
                                <Button v-if="!search && !roleFilter && canManage" class="mt-2" @click="showInvite = true">
                                    Invite User
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>

                    <!-- User rows -->
                    <TableRow v-for="user in users.data" :key="user.id">
                        <TableCell>
                            <div class="flex items-center gap-3">
                                <Avatar class="h-9 w-9 shrink-0">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback class="text-xs font-semibold">{{ getInitials(user.name) }}</AvatarFallback>
                                </Avatar>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-medium">{{ user.name }}</p>
                                    <p class="truncate text-xs text-muted-foreground">{{ user.email }}</p>
                                </div>
                            </div>
                        </TableCell>
                        <TableCell class="hidden sm:table-cell">
                            <RoleBadge :role="user.role?.name" :label="user.role?.display_name" />
                        </TableCell>
                        <TableCell class="hidden md:table-cell">
                            <UserStatusBadge :active="user.is_active" />
                        </TableCell>
                        <TableCell class="hidden lg:table-cell text-sm text-muted-foreground">
                            {{ user.last_login_at ?? 'Never' }}
                        </TableCell>
                        <TableCell v-if="canManage">
                            <DropdownMenu v-if="!isLeaving">
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="icon" class="h-8 w-8">
                                        <span class="sr-only">Open menu</span>
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 16 16">
                                            <circle cx="8" cy="3" r="1.5"/>
                                            <circle cx="8" cy="8" r="1.5"/>
                                            <circle cx="8" cy="13" r="1.5"/>
                                        </svg>
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem @click="openEdit(user)">Edit</DropdownMenuItem>
                                    <DropdownMenuItem @click="resetPassword(user)">Reset Password</DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem @click="toggleStatus(user)">
                                        {{ user.is_active ? 'Deactivate' : 'Activate' }}
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator />
                                    <DropdownMenuItem class="text-destructive" @click="openDelete(user)">
                                        Delete
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="users.last_page > 1" class="flex items-center justify-between gap-2">
            <p class="text-sm text-muted-foreground">
                Showing {{ users.from }}–{{ users.to }} of {{ users.total }} users
            </p>
            <div class="flex gap-1">
                <template v-for="link in users.links" :key="link.label">
                    <Link
                        v-if="link.url"
                        :href="link.url"
                        preserve-state
                        :class="[
                            'inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm transition-colors',
                            link.active
                                ? 'bg-primary text-primary-foreground font-medium'
                                : 'hover:bg-muted text-muted-foreground'
                        ]"
                        v-html="link.label"
                    />
                    <span
                        v-else
                        class="inline-flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm text-muted-foreground opacity-50"
                        v-html="link.label"
                    />
                </template>
            </div>
        </div>
    </div>

    <!-- Modals: v-if ensures Teleport nodes are fully unmounted when not in use,
         preventing null-vnode errors during Inertia navigation -->
    <InviteUserModal v-if="!isLeaving && showInvite" :open="showInvite" :roles="roles" @close="showInvite = false" />
    <EditUserModal v-if="!isLeaving && editingUser" :user="editingUser" :roles="roles" @close="editingUser = null" />
    <DeleteUserModal v-if="!isLeaving && deletingUser" :user="deletingUser" @close="deletingUser = null" />
</template>
