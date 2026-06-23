<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, Plus, Search, SlidersHorizontal } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import LeaseStatusBadge from '@/components/leases/LeaseStatusBadge.vue';
import RenewLeaseModal from '@/components/leases/RenewLeaseModal.vue';
import TerminateLeaseModal from '@/components/leases/TerminateLeaseModal.vue';
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
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { usePermission } from '@/composables/usePermission';
import type { Lease, LeaseFilters, PaginatedLeases } from '@/types/lease';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Leases', href: '/leases' }],
    },
});

const props = defineProps<{
    leases: PaginatedLeases;
    filters: LeaseFilters;
    statuses: { value: string; label: string }[];
    leaseTypes: { value: string; label: string }[];
    properties: { value: string; label: string }[];
}>();

const { can } = usePermission();

const search         = ref(props.filters.search ?? '');
const statusFilter   = ref(props.filters.status ?? 'all');
const typeFilter     = ref(props.filters.lease_type ?? 'all');
const propertyFilter = ref(props.filters.property_id ?? 'all');

const renewTarget    = ref<Lease | null>(null);
const renewOpen      = ref(false);
const terminateTarget = ref<Lease | null>(null);
const terminateOpen  = ref(false);

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/leases', {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        lease_type: typeFilter.value !== 'all' ? typeFilter.value : undefined,
        property_id: propertyFilter.value !== 'all' ? propertyFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch([statusFilter, typeFilter, propertyFilter], applyFilters);

function clearFilters() {
    search.value         = '';
    statusFilter.value   = 'all';
    typeFilter.value     = 'all';
    propertyFilter.value = 'all';
}

const hasActiveFilters = computed(() =>
    !!(search.value || statusFilter.value !== 'all' || typeFilter.value !== 'all' || propertyFilter.value !== 'all')
);

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function formatCurrency(amount: number, currency = 'PHP'): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency }).format(amount);
}

function openRenew(lease: Lease) {
    renewTarget.value = lease;
    renewOpen.value   = true;
}

function openTerminate(lease: Lease) {
    terminateTarget.value = lease;
    terminateOpen.value   = true;
}

function confirmDelete(lease: Lease) {
    if (confirm(`Delete lease ${lease.lease_number}? This cannot be undone.`)) {
        router.delete(`/leases/${lease.id}`);
    }
}
</script>

<template>
    <Head title="Leases" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Leases" description="Manage rental agreements across your properties." />
            <Button v-if="can('leases.create')" as-child>
                <Link href="/leases/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Create Lease
                </Link>
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search by lease #, tenant name or email…" class="pl-9" />
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

            <Select v-model="typeFilter">
                <SelectTrigger class="w-full sm:w-40">
                    <SelectValue placeholder="Type" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Types</SelectItem>
                    <SelectItem v-for="t in leaseTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="propertyFilter">
                <SelectTrigger class="w-full sm:w-48">
                    <SelectValue placeholder="Property" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Properties</SelectItem>
                    <SelectItem v-for="p in properties" :key="p.value" :value="p.value">{{ p.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                Clear
            </Button>
        </div>

        <p class="text-sm text-muted-foreground">
            Showing {{ leases.from ?? 0 }}–{{ leases.to ?? 0 }} of {{ leases.total }} leases
        </p>

        <!-- Empty State -->
        <div
            v-if="leases.data.length === 0"
            class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center"
        >
            <FileText class="mb-4 h-12 w-12 text-muted-foreground/40" />
            <p class="text-base font-medium">No leases found</p>
            <p class="mt-1 text-sm text-muted-foreground">Try adjusting your search or filters.</p>
            <Button v-if="can('leases.create')" class="mt-4" as-child>
                <Link href="/leases/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Create your first lease
                </Link>
            </Button>
        </div>

        <!-- Table -->
        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Lease #</TableHead>
                        <TableHead>Tenant</TableHead>
                        <TableHead>Property / Unit</TableHead>
                        <TableHead>Period</TableHead>
                        <TableHead>Monthly Rent</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="lease in leases.data" :key="lease.id">
                        <TableCell>
                            <span class="font-mono text-sm font-medium">{{ lease.lease_number }}</span>
                            <div v-if="lease.lease_type_label" class="text-xs text-muted-foreground">{{ lease.lease_type_label }}</div>
                        </TableCell>
                        <TableCell>
                            <div v-if="lease.tenant" class="flex items-center gap-2">
                                <Avatar class="h-7 w-7">
                                    <AvatarImage :src="lease.tenant.profile_photo_url ?? ''" :alt="lease.tenant.full_name" />
                                    <AvatarFallback class="text-xs">{{ getInitials(lease.tenant.full_name) }}</AvatarFallback>
                                </Avatar>
                                <div>
                                    <div class="text-sm font-medium">{{ lease.tenant.full_name }}</div>
                                    <div class="text-xs text-muted-foreground">{{ lease.tenant.email }}</div>
                                </div>
                            </div>
                            <span v-else class="text-muted-foreground">—</span>
                        </TableCell>
                        <TableCell class="text-sm">
                            <div class="font-medium">{{ lease.property_name ?? '—' }}</div>
                            <div class="text-xs text-muted-foreground">
                                Unit {{ lease.unit_number }}
                                <template v-if="lease.building_name"> · {{ lease.building_name }}</template>
                            </div>
                        </TableCell>
                        <TableCell class="text-sm">
                            <div>{{ lease.start_date }}</div>
                            <div class="text-xs text-muted-foreground">→ {{ lease.end_date }}</div>
                            <div v-if="lease.days_remaining > 0" class="text-xs" :class="lease.days_remaining <= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-muted-foreground'">
                                {{ lease.days_remaining }}d remaining
                            </div>
                        </TableCell>
                        <TableCell class="text-sm font-medium">
                            {{ formatCurrency(lease.rent_amount, lease.currency) }}
                        </TableCell>
                        <TableCell>
                            <LeaseStatusBadge :status="lease.status" :label="lease.status_label" />
                        </TableCell>
                        <TableCell class="text-right">
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm">⋯</Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="`/leases/${lease.id}`">View</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="can('leases.edit')" as-child>
                                        <Link :href="`/leases/${lease.id}/edit`">Edit</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="can('leases.renew') && ['active', 'expiring_soon'].includes(lease.status ?? '')"
                                        @click="openRenew(lease)"
                                    >
                                        Renew
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator v-if="can('leases.terminate') || can('leases.delete')" />
                                    <DropdownMenuItem
                                        v-if="can('leases.terminate') && ['active', 'expiring_soon'].includes(lease.status ?? '')"
                                        class="text-destructive focus:text-destructive"
                                        @click="openTerminate(lease)"
                                    >
                                        Terminate
                                    </DropdownMenuItem>
                                    <DropdownMenuItem
                                        v-if="can('leases.delete') && ['draft', 'pending'].includes(lease.status ?? '')"
                                        class="text-destructive focus:text-destructive"
                                        @click="confirmDelete(lease)"
                                    >
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
        <div v-if="leases.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in leases.links"
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

    <!-- Modals -->
    <RenewLeaseModal
        v-if="renewTarget"
        :open="renewOpen"
        :lease="renewTarget"
        @update:open="renewOpen = $event"
    />

    <TerminateLeaseModal
        v-if="terminateTarget"
        :open="terminateOpen"
        :lease="terminateTarget"
        :termination-reasons="[]"
        @update:open="terminateOpen = $event"
    />
</template>
