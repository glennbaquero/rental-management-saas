<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Plus, Search, SlidersHorizontal } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MaintenanceStatusBadge from '@/components/maintenance/MaintenanceStatusBadge.vue';
import MaintenancePriorityBadge from '@/components/maintenance/MaintenancePriorityBadge.vue';
import AssignStaffModal from '@/components/maintenance/AssignStaffModal.vue';
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
import type {
    MaintenanceFilters,
    MaintenanceTicketDetail,
    MaintenanceTicketRow,
    PaginatedMaintenanceTickets,
    PriorityOption,
    SelectOption,
} from '@/types/maintenance';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Maintenance Requests', href: '/maintenance' }],
    },
});

const props = defineProps<{
    tickets: PaginatedMaintenanceTickets;
    filters: MaintenanceFilters;
    statuses: SelectOption[];
    priorities: PriorityOption[];
    categories: SelectOption[];
    properties: SelectOption[];
    staff: SelectOption[];
}>();

const { can } = usePermission();

const search           = ref(props.filters.search ?? '');
const statusFilter     = ref(props.filters.status ?? 'all');
const priorityFilter   = ref(props.filters.priority ?? 'all');
const propertyFilter   = ref(props.filters.property_id ?? 'all');
const categoryFilter   = ref(props.filters.category ?? 'all');
const staffFilter      = ref(props.filters.assigned_to ?? 'all');

const assignTarget = ref<MaintenanceTicketDetail | null>(null);
const assignOpen   = ref(false);

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/maintenance', {
        search: search.value || undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        priority: priorityFilter.value !== 'all' ? priorityFilter.value : undefined,
        property_id: propertyFilter.value !== 'all' ? propertyFilter.value : undefined,
        category: categoryFilter.value !== 'all' ? categoryFilter.value : undefined,
        assigned_to: staffFilter.value !== 'all' ? staffFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 400);
});

watch([statusFilter, priorityFilter, propertyFilter, categoryFilter, staffFilter], applyFilters);

function clearFilters() {
    search.value         = '';
    statusFilter.value   = 'all';
    priorityFilter.value = 'all';
    propertyFilter.value = 'all';
    categoryFilter.value = 'all';
    staffFilter.value    = 'all';
}

const hasActiveFilters = computed(() =>
    !!(search.value
        || statusFilter.value !== 'all'
        || priorityFilter.value !== 'all'
        || propertyFilter.value !== 'all'
        || categoryFilter.value !== 'all'
        || staffFilter.value !== 'all')
);

function openAssign(ticket: MaintenanceTicketRow) {
    assignTarget.value = ticket as unknown as MaintenanceTicketDetail;
    assignOpen.value   = true;
}

function formatDate(dt: string): string {
    return new Date(dt).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function closeTicket(ticket: MaintenanceTicketRow) {
    if (!confirm(`Close ticket ${ticket.ticket_number}?`)) return;
    router.put(`/maintenance/${ticket.id}`, {
        status: 'cancelled',
        property_id: '', unit_id: '', category: '', title: ticket.title, description: '', priority: ticket.priority,
    }, { preserveState: false });
}
</script>

<template>
    <Head title="Maintenance Requests" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between">
            <Heading title="Maintenance Requests" description="Manage and track all maintenance tickets." />
            <Button v-if="can('maintenance.create')" as-child>
                <Link href="/maintenance/create">
                    <Plus class="mr-1 h-4 w-4" />
                    Create Ticket
                </Link>
            </Button>
        </div>

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative flex-1 min-w-[200px]">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search ticket, tenant, unit..." class="pl-9" />
            </div>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-[140px]">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="priorityFilter">
                <SelectTrigger class="w-[130px]">
                    <SelectValue placeholder="Priority" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Priorities</SelectItem>
                    <SelectItem v-for="p in priorities" :key="p.value" :value="p.value">
                        {{ p.icon }} {{ p.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="propertyFilter">
                <SelectTrigger class="w-[150px]">
                    <SelectValue placeholder="Property" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Properties</SelectItem>
                    <SelectItem v-for="p in properties" :key="p.value" :value="p.value">
                        {{ p.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="categoryFilter">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="Category" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Categories</SelectItem>
                    <SelectItem v-for="c in categories" :key="c.value" :value="c.value">
                        {{ c.label }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button v-if="hasActiveFilters" variant="ghost" size="sm" @click="clearFilters">
                Clear filters
            </Button>
        </div>

        <!-- Table -->
        <div class="rounded-lg border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Ticket #</TableHead>
                        <TableHead>Tenant</TableHead>
                        <TableHead>Property / Unit</TableHead>
                        <TableHead>Category</TableHead>
                        <TableHead>Priority</TableHead>
                        <TableHead>Assigned To</TableHead>
                        <TableHead>Submitted</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-if="tickets.data.length === 0">
                        <TableCell colspan="9" class="py-12 text-center text-muted-foreground">
                            No maintenance tickets found.
                        </TableCell>
                    </TableRow>

                    <TableRow
                        v-for="ticket in tickets.data"
                        :key="ticket.id"
                        class="cursor-pointer hover:bg-muted/50"
                        @click="router.visit(`/maintenance/${ticket.id}`)"
                    >
                        <TableCell class="font-mono text-xs font-medium">{{ ticket.ticket_number }}</TableCell>
                        <TableCell class="text-sm">{{ ticket.tenant_name }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">
                            {{ ticket.property_name }}<span v-if="ticket.unit_number !== '—'"> · {{ ticket.unit_number }}</span>
                        </TableCell>
                        <TableCell class="text-sm">{{ ticket.category_label }}</TableCell>
                        <TableCell>
                            <MaintenancePriorityBadge
                                :priority="ticket.priority"
                                :label="ticket.priority_label"
                                :icon="ticket.priority_icon"
                            />
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ ticket.assigned_to }}</TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ formatDate(ticket.date_submitted) }}</TableCell>
                        <TableCell>
                            <MaintenanceStatusBadge :status="ticket.status" :label="ticket.status_label" />
                        </TableCell>
                        <TableCell class="text-right" @click.stop>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <Button variant="ghost" size="sm">···</Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem as-child>
                                        <Link :href="`/maintenance/${ticket.id}`">View</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="can('maintenance.edit')" as-child>
                                        <Link :href="`/maintenance/${ticket.id}/edit`">Edit</Link>
                                    </DropdownMenuItem>
                                    <DropdownMenuItem v-if="can('maintenance.manage')" @click="openAssign(ticket)">
                                        Assign
                                    </DropdownMenuItem>
                                    <DropdownMenuSeparator v-if="can('maintenance.edit')" />
                                    <DropdownMenuItem
                                        v-if="can('maintenance.edit') && ticket.status !== 'cancelled' && ticket.status !== 'completed'"
                                        class="text-destructive"
                                        @click="closeTicket(ticket)"
                                    >
                                        Close Ticket
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>

            <!-- Pagination -->
            <div v-if="tickets.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
                <p class="text-sm text-muted-foreground">
                    Showing {{ tickets.from }}–{{ tickets.to }} of {{ tickets.total }}
                </p>
                <div class="flex gap-1">
                    <Button
                        v-for="link in tickets.links"
                        :key="link.label"
                        variant="outline"
                        size="sm"
                        :disabled="!link.url"
                        :class="{ 'bg-primary text-primary-foreground': link.active }"
                        @click="link.url && router.visit(link.url)"
                        v-html="link.label"
                    />
                </div>
            </div>
        </div>
    </div>

    <AssignStaffModal
        v-if="assignTarget"
        :open="assignOpen"
        :ticket="assignTarget"
        :staff="staff"
        @update:open="assignOpen = $event"
    />
</template>
