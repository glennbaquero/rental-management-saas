<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, SlidersHorizontal, Users } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import TenantStatusBadge from '@/components/tenants/TenantStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';
import type { PaginatedTenants, RentalTenant, SelectOption, TenantFilters } from '@/types/tenant';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tenants', href: '/tenants' },
        ],
    },
});

const props = defineProps<{
    tenants: PaginatedTenants;
    filters: TenantFilters;
    statuses: SelectOption[];
    properties: SelectOption[];
}>();

const { can } = usePermission();

const search       = ref(props.filters.search ?? '');
const statusFilter = ref(props.filters.status ?? 'all');

const archiveTarget = ref<RentalTenant | null>(null);
const archiveOpen   = ref(false);

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/tenants', {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch(statusFilter, applyFilters);

function clearFilters() {
    search.value       = '';
    statusFilter.value = 'all';
}

const hasActiveFilters = computed(() => !!(search.value || statusFilter.value !== 'all'));

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function confirmArchive(tenant: RentalTenant) {
    if (confirm(`Archive ${tenant.full_name}? They will be soft-deleted and can be restored later.`)) {
        router.delete(`/tenants/${tenant.id}`);
    }
}
</script>

<template>
    <Head title="Tenants" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Tenants" description="Manage rental tenants across your properties." />
            <div class="flex gap-2">
                <Button variant="outline" disabled>
                    Import CSV
                </Button>
                <Button v-if="can('tenants.create')" as-child>
                    <Link href="/tenants/create">
                        <Plus class="mr-2 h-4 w-4" />
                        Add Tenant
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search by name, email, phone, or code…" class="pl-9" />
            </div>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-full sm:w-44">
                    <SlidersHorizontal class="mr-2 h-4 w-4 text-muted-foreground" />
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                Clear
            </Button>
        </div>

        <p class="text-sm text-muted-foreground">
            Showing {{ tenants.from ?? 0 }}–{{ tenants.to ?? 0 }} of {{ tenants.total }} tenants
        </p>

        <!-- Empty State -->
        <div
            v-if="tenants.data.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
            <Users class="mb-4 h-12 w-12 text-muted-foreground/40" />
            <p class="text-base font-medium">No tenants found</p>
            <p class="mt-1 text-sm text-muted-foreground">Try adjusting your search or filters.</p>
            <Button v-if="can('tenants.create')" class="mt-4" as-child>
                <Link href="/tenants/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Add your first tenant
                </Link>
            </Button>
        </div>

        <!-- Table -->
        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead class="w-10" />
                        <TableHead>Tenant</TableHead>
                        <TableHead>Email</TableHead>
                        <TableHead>Phone</TableHead>
                        <TableHead>Current Unit</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="tenant in tenants.data" :key="tenant.id">
                        <TableCell>
                            <Avatar class="h-8 w-8">
                                <AvatarImage :src="tenant.profile_photo_url ?? ''" :alt="tenant.full_name" />
                                <AvatarFallback class="text-xs">{{ getInitials(tenant.full_name) }}</AvatarFallback>
                            </Avatar>
                        </TableCell>
                        <TableCell>
                            <div class="font-medium">{{ tenant.full_name }}</div>
                            <div v-if="tenant.tenant_code" class="text-xs text-muted-foreground">
                                {{ tenant.tenant_code }}
                            </div>
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ tenant.email ?? '—' }}
                        </TableCell>
                        <TableCell class="text-sm">
                            {{ tenant.phone ?? '—' }}
                        </TableCell>
                        <TableCell class="text-sm">
                            <template v-if="tenant.current_lease">
                                <div class="font-medium">{{ tenant.current_lease.property_name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    Unit {{ tenant.current_lease.unit_number }}
                                    <template v-if="tenant.current_lease.building_name">
                                        · {{ tenant.current_lease.building_name }}
                                    </template>
                                </div>
                            </template>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell>
                            <TenantStatusBadge :status="tenant.status" :label="tenant.status_label" />
                        </TableCell>
                        <TableCell class="text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm">⋯</Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="`/tenants/${tenant.id}`">View</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="can('tenants.edit')" as-child>
                                        <Link :href="`/tenants/${tenant.id}/edit`">Edit</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator v-if="can('tenants.delete')" />
                                    <DropdownMenuItem
                                        v-if="can('tenants.delete')"
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmArchive(tenant)"
                                    >
                                        Archive
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <!-- Pagination -->
        <div v-if="tenants.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in tenants.links"
                :key="link.label"
                size="sm"
                :variant="link.active ? 'default' : 'outline'"
                :disabled="!link.url"
                as-child
            >
                <Link v-if="link.url" :href="link.url" preserve-scroll v-html="link.label" />
                <span v-else v-html="link.label" />
            </Button>
        </div>
    </div>
</template>
