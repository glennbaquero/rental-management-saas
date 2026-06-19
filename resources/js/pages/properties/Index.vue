<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, LayoutGrid, List, Plus, Search, SlidersHorizontal } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import PropertyCard from '@/components/properties/PropertyCard.vue';
import DeletePropertyModal from '@/components/properties/DeletePropertyModal.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import PropertyStatusBadge from '@/components/properties/PropertyStatusBadge.vue';
import { usePermission } from '@/composables/usePermission';
import type { PaginatedProperties, Property, SelectOption } from '@/types/property';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: '/properties' },
        ],
    },
});

const props = defineProps<{
    properties: PaginatedProperties;
    filters: { search?: string; type?: string; status?: string; city?: string };
    cities: string[];
    types: SelectOption[];
    statuses: SelectOption[];
}>();

const { can } = usePermission();

const viewMode = ref<'card' | 'table'>('card');
const search   = ref(props.filters.search ?? '');
const typeFilter   = ref(props.filters.type ?? 'all');
const statusFilter = ref(props.filters.status ?? 'all');
const cityFilter   = ref(props.filters.city ?? 'all');

const deleteTarget = ref<Property | null>(null);
const deleteOpen   = ref(false);

let searchTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get('/properties', {
        search: search.value || undefined,
        type:   typeFilter.value !== 'all' ? typeFilter.value : undefined,
        status: statusFilter.value !== 'all' ? statusFilter.value : undefined,
        city:   cityFilter.value !== 'all' ? cityFilter.value : undefined,
    }, { preserveState: true, replace: true });
}

watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(applyFilters, 350);
});

watch([typeFilter, statusFilter, cityFilter], applyFilters);

function openDelete(property: Property) {
    deleteTarget.value = property;
    deleteOpen.value   = true;
}

function clearFilters() {
    search.value       = '';
    typeFilter.value   = 'all';
    statusFilter.value = 'all';
    cityFilter.value   = 'all';
}

const hasActiveFilters = () =>
    !!(search.value || typeFilter.value !== 'all' || statusFilter.value !== 'all' || cityFilter.value !== 'all');
</script>

<template>
    <Head title="Properties" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <Heading title="Properties" description="Manage your real estate portfolio." />
            <Button v-if="can('properties.create')" as-child>
                <Link href="/properties/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Property
                </Link>
            </Button>
        </div>

        <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
            <div class="relative flex-1">
                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="search" placeholder="Search properties…" class="pl-9" />
            </div>

            <Select v-model="typeFilter">
                <SelectTrigger class="w-full sm:w-40">
                    <SlidersHorizontal class="mr-2 h-4 w-4 text-muted-foreground" />
                    <SelectValue placeholder="Type" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Types</SelectItem>
                    <SelectItem v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-full sm:w-40">
                    <SelectValue placeholder="Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="cityFilter">
                <SelectTrigger class="w-full sm:w-36">
                    <SelectValue placeholder="City" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Cities</SelectItem>
                    <SelectItem v-for="city in cities" :key="city" :value="city">{{ city }}</SelectItem>
                </SelectContent>
            </Select>

            <Button v-if="hasActiveFilters()" variant="ghost" size="sm" @click="clearFilters">
                Clear
            </Button>

            <div class="hidden items-center gap-1 sm:flex">
                <Button
                    size="icon"
                    :variant="viewMode === 'card' ? 'default' : 'ghost'"
                    class="h-9 w-9"
                    @click="viewMode = 'card'"
                >
                    <LayoutGrid class="h-4 w-4" />
                </Button>
                <Button
                    size="icon"
                    :variant="viewMode === 'table' ? 'default' : 'ghost'"
                    class="h-9 w-9"
                    @click="viewMode = 'table'"
                >
                    <List class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <p class="text-sm text-muted-foreground">
            Showing {{ properties.from ?? 0 }}–{{ properties.to ?? 0 }} of {{ properties.total }} properties
        </p>

        <div v-if="properties.data.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-16 text-center">
            <Building2 class="mb-4 h-12 w-12 text-muted-foreground/40" />
            <p class="text-base font-medium">No properties found</p>
            <p class="mt-1 text-sm text-muted-foreground">Try adjusting your search or filters.</p>
            <Button v-if="can('properties.create')" class="mt-4" as-child>
                <Link href="/properties/create">
                    <Plus class="mr-2 h-4 w-4" />
                    Add your first property
                </Link>
            </Button>
        </div>

        <div v-else-if="viewMode === 'card'" class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
            <PropertyCard
                v-for="property in properties.data"
                :key="property.id"
                :property="property"
                :can-edit="can('properties.edit')"
                :can-delete="can('properties.delete')"
                @delete="openDelete"
            />
        </div>

        <div v-else class="rounded-lg border">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Property</TableHead>
                        <TableHead>Type</TableHead>
                        <TableHead>City</TableHead>
                        <TableHead class="text-center">Buildings</TableHead>
                        <TableHead class="text-center">Units</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead class="text-right">Actions</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="property in properties.data" :key="property.id">
                        <TableCell>
                            <div class="font-medium">{{ property.name }}</div>
                            <div v-if="property.code" class="text-xs text-muted-foreground">{{ property.code }}</div>
                        </TableCell>
                        <TableCell class="text-sm text-muted-foreground">{{ property.type_label }}</TableCell>
                        <TableCell class="text-sm">{{ property.city }}</TableCell>
                        <TableCell class="text-center text-sm">{{ property.total_buildings }}</TableCell>
                        <TableCell class="text-center text-sm">{{ property.total_units }}</TableCell>
                        <TableCell>
                            <PropertyStatusBadge :status="property.status" :label="property.status_label" />
                        </TableCell>
                        <TableCell class="text-right">
                            <div class="flex justify-end gap-2">
                                <Button size="sm" variant="outline" as-child>
                                    <Link :href="`/properties/${property.id}`">View</Link>
                                </Button>
                                <Button v-if="can('properties.edit')" size="sm" variant="outline" as-child>
                                    <Link :href="`/properties/${property.id}/edit`">Edit</Link>
                                </Button>
                                <Button
                                    v-if="can('properties.delete')"
                                    size="sm"
                                    variant="outline"
                                    class="text-destructive hover:text-destructive"
                                    @click="openDelete(property)"
                                >
                                    Delete
                                </Button>
                            </div>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
        </div>

        <div v-if="properties.last_page > 1" class="flex justify-center gap-1">
            <Button
                v-for="link in properties.links"
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

    <DeletePropertyModal
        :property="deleteTarget"
        :open="deleteOpen"
        @update:open="deleteOpen = $event"
    />
</template>
