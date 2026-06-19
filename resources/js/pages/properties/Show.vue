<script setup lang="ts">
import { ref, computed } from 'vue';
import { Head, Link, useForm, router } from '@inertiajs/vue3';
import {
    Building2, ChevronLeft, DoorOpen, MapPin, MoreHorizontal,
    Pencil, Plus, Trash2, Search,
} from '@lucide/vue';
import PropertyStatCards from '@/components/properties/PropertyStatCards.vue';
import PropertyStatusBadge from '@/components/properties/PropertyStatusBadge.vue';
import UnitStatusBadge from '@/components/properties/UnitStatusBadge.vue';
import BuildingFormModal from '@/components/properties/BuildingFormModal.vue';
import UnitFormModal from '@/components/properties/UnitFormModal.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import {
    DropdownMenu, DropdownMenuContent, DropdownMenuItem,
    DropdownMenuSeparator, DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { usePermission } from '@/composables/usePermission';
import type { Building, Property, Unit, UnitType, Amenity } from '@/types/property';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: '/properties' },
            { title: 'Property Details' },
        ],
    },
});

const props = defineProps<{
    property: Property;
    buildings: Building[];
    units: Unit[];
    unitTypes: UnitType[];
    allAmenities: Amenity[];
}>();

const { can } = usePermission();

const activeTab = ref('overview');

const buildingModalOpen = ref(false);
const editingBuilding   = ref<Building | null>(null);

const unitModalOpen = ref(false);
const editingUnit   = ref<Unit | null>(null);

const unitSearch         = ref('');
const unitBuildingFilter = ref('all');
const unitTypeFilter     = ref('all');
const unitStatusFilter   = ref('all');

const filteredUnits = computed(() => {
    return props.units.filter((u) => {
        if (unitSearch.value && !u.unit_number.toLowerCase().includes(unitSearch.value.toLowerCase())) return false;
        if (unitBuildingFilter.value !== 'all' && u.building?.id !== unitBuildingFilter.value) return false;
        if (unitTypeFilter.value !== 'all' && u.unit_type?.id !== unitTypeFilter.value) return false;
        if (unitStatusFilter.value !== 'all' && u.status !== unitStatusFilter.value) return false;
        return true;
    });
});

function openAddBuilding() {
    editingBuilding.value = null;
    buildingModalOpen.value = true;
}

function openEditBuilding(b: Building) {
    editingBuilding.value = b;
    buildingModalOpen.value = true;
}

function deleteBuilding(b: Building) {
    if (!confirm(`Delete building "${b.name}"? All units will be unlinked.`)) return;
    router.delete(`/properties/${props.property.id}/buildings/${b.id}`, { preserveScroll: true });
}

function openAddUnit() {
    editingUnit.value = null;
    unitModalOpen.value = true;
}

function openEditUnit(u: Unit) {
    editingUnit.value = u;
    unitModalOpen.value = true;
}

function deleteUnit(u: Unit) {
    if (!confirm(`Delete unit "${u.unit_number}"?`)) return;
    router.delete(`/properties/${props.property.id}/units/${u.id}`, { preserveScroll: true });
}

const amenitySyncForm = useForm({ amenity_ids: props.property.amenities.map(a => a.id) });

function syncAmenities() {
    amenitySyncForm.post(`/properties/${props.property.id}/amenities/sync`, {
        preserveScroll: true,
    });
}

const propertyAmenities = computed(() =>
    props.allAmenities.filter(a => a.category === 'property' || a.category === 'both')
);

const unitAmenities = computed(() =>
    props.allAmenities.filter(a => a.category === 'unit' || a.category === 'both')
);

const occupancyRate = computed(() => {
    const total = props.property.total_units;
    if (!total) return 0;
    return Math.round((props.property.occupied_units / total) * 100);
});
</script>

<template>
    <Head :title="property.name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <Button variant="ghost" size="icon" as-child>
                    <Link href="/properties"><ChevronLeft class="h-4 w-4" /></Link>
                </Button>
                <div>
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-semibold tracking-tight">{{ property.name }}</h1>
                        <PropertyStatusBadge :status="property.status" :label="property.status_label" />
                    </div>
                    <p class="text-sm text-muted-foreground">
                        {{ property.type_label }}
                        <span v-if="property.code"> · {{ property.code }}</span>
                    </p>
                </div>
            </div>

            <div v-if="can('properties.edit')" class="flex items-center gap-2">
                <Button variant="outline" size="sm" as-child>
                    <Link :href="`/properties/${property.id}/edit`">
                        <Pencil class="mr-1.5 h-3.5 w-3.5" />
                        Edit
                    </Link>
                </Button>
            </div>
        </div>

        <div
            v-if="property.featured_image_url"
            class="relative h-48 overflow-hidden rounded-xl sm:h-64"
        >
            <img
                :src="property.featured_image_url"
                :alt="property.name"
                class="h-full w-full object-cover"
            />
            <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent" />
            <div class="absolute bottom-4 left-4 flex items-center gap-1.5 text-sm text-white">
                <MapPin class="h-4 w-4" />
                <span>{{ property.address }}, {{ property.city }}</span>
            </div>
        </div>

        <PropertyStatCards
            :total-buildings="property.total_buildings"
            :total-units="property.total_units"
            :available-units="property.available_units"
            :occupied-units="property.occupied_units"
            :monthly-revenue="property.monthly_revenue"
        />

        <Tabs v-model="activeTab">
            <TabsList class="w-full sm:w-auto">
                <TabsTrigger value="overview">Overview</TabsTrigger>
                <TabsTrigger value="buildings">
                    Buildings
                    <Badge v-if="property.total_buildings > 0" variant="secondary" class="ml-1.5 text-xs">
                        {{ property.total_buildings }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="units">
                    Units
                    <Badge v-if="property.total_units > 0" variant="secondary" class="ml-1.5 text-xs">
                        {{ property.total_units }}
                    </Badge>
                </TabsTrigger>
                <TabsTrigger value="amenities">Amenities</TabsTrigger>
                <TabsTrigger value="gallery">Gallery</TabsTrigger>
            </TabsList>

            <TabsContent value="overview" class="mt-6">
                <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <Card>
                        <CardHeader><CardTitle class="text-base">Property Information</CardTitle></CardHeader>
                        <CardContent class="space-y-3">
                            <div class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                                <span class="text-muted-foreground">Type</span>
                                <span class="font-medium">{{ property.type_label }}</span>

                                <span class="text-muted-foreground">Status</span>
                                <PropertyStatusBadge :status="property.status" :label="property.status_label" />

                                <span class="text-muted-foreground">Code</span>
                                <span class="font-medium">{{ property.code ?? '—' }}</span>

                                <span class="text-muted-foreground">Address</span>
                                <span class="font-medium">{{ property.address }}</span>

                                <span class="text-muted-foreground">City</span>
                                <span class="font-medium">{{ property.city }}</span>

                                <span class="text-muted-foreground">Province</span>
                                <span class="font-medium">{{ property.province ?? '—' }}</span>

                                <span class="text-muted-foreground">Country</span>
                                <span class="font-medium">{{ property.country }}</span>

                                <span class="text-muted-foreground">Postal Code</span>
                                <span class="font-medium">{{ property.postal_code ?? '—' }}</span>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader><CardTitle class="text-base">Occupancy Overview</CardTitle></CardHeader>
                        <CardContent>
                            <div class="mb-4">
                                <div class="flex items-center justify-between text-sm">
                                    <span class="text-muted-foreground">Occupancy Rate</span>
                                    <span class="font-semibold">{{ occupancyRate }}%</span>
                                </div>
                                <div class="mt-2 h-2 rounded-full bg-muted">
                                    <div
                                        class="h-2 rounded-full bg-primary transition-all"
                                        :style="{ width: `${occupancyRate}%` }"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-3 text-sm">
                                <div class="rounded-lg bg-green-50 p-3 dark:bg-green-950/30">
                                    <div class="font-semibold text-green-700 dark:text-green-400">{{ property.available_units }}</div>
                                    <div class="text-xs text-green-600 dark:text-green-500">Available</div>
                                </div>
                                <div class="rounded-lg bg-red-50 p-3 dark:bg-red-950/30">
                                    <div class="font-semibold text-red-700 dark:text-red-400">{{ property.occupied_units }}</div>
                                    <div class="text-xs text-red-600 dark:text-red-500">Occupied</div>
                                </div>
                                <div class="rounded-lg bg-blue-50 p-3 dark:bg-blue-950/30">
                                    <div class="font-semibold text-blue-700 dark:text-blue-400">{{ property.reserved_units }}</div>
                                    <div class="text-xs text-blue-600 dark:text-blue-500">Reserved</div>
                                </div>
                                <div class="rounded-lg bg-yellow-50 p-3 dark:bg-yellow-950/30">
                                    <div class="font-semibold text-yellow-700 dark:text-yellow-400">{{ property.maintenance_units }}</div>
                                    <div class="text-xs text-yellow-600 dark:text-yellow-500">Maintenance</div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </TabsContent>

            <TabsContent value="buildings" class="mt-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-medium">Buildings ({{ buildings.length }})</h3>
                    <Button v-if="can('properties.edit')" size="sm" @click="openAddBuilding">
                        <Plus class="mr-1.5 h-3.5 w-3.5" />
                        Add Building
                    </Button>
                </div>

                <div v-if="buildings.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                    <Building2 class="mb-3 h-10 w-10 text-muted-foreground/40" />
                    <p class="text-sm font-medium">No buildings yet</p>
                    <Button v-if="can('properties.edit')" size="sm" class="mt-3" @click="openAddBuilding">
                        <Plus class="mr-1.5 h-3.5 w-3.5" />
                        Add Building
                    </Button>
                </div>

                <div v-else class="rounded-lg border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Building</TableHead>
                                <TableHead class="text-center">Floors</TableHead>
                                <TableHead class="text-center">Total Units</TableHead>
                                <TableHead class="text-center">Occupied</TableHead>
                                <TableHead class="text-center">Available</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead class="text-right">Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            <TableRow v-for="b in buildings" :key="b.id">
                                <TableCell>
                                    <div class="font-medium">{{ b.name }}</div>
                                    <div v-if="b.code" class="text-xs text-muted-foreground">{{ b.code }}</div>
                                </TableCell>
                                <TableCell class="text-center text-sm">{{ b.floors }}</TableCell>
                                <TableCell class="text-center text-sm">{{ b.total_units }}</TableCell>
                                <TableCell class="text-center text-sm">{{ b.occupied_units }}</TableCell>
                                <TableCell class="text-center text-sm">{{ b.available_units }}</TableCell>
                                <TableCell>
                                    <Badge :variant="b.status === 'active' ? 'default' : 'secondary'" class="text-xs">
                                        {{ b.status_label }}
                                    </Badge>
                                </TableCell>
                                <TableCell class="text-right">
                                    <DropdownMenu v-if="can('properties.edit')">
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <MoreHorizontal class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end">
                                            <DropdownMenuItem @click="openEditBuilding(b)">Edit</DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem class="text-destructive" @click="deleteBuilding(b)">Delete</DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </TableCell>
                            </TableRow>
                        </TableBody>
                    </Table>
                </div>
            </TabsContent>

            <TabsContent value="units" class="mt-6">
                <div class="flex flex-col gap-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-1 flex-col gap-2 sm:flex-row">
                            <div class="relative flex-1">
                                <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <Input v-model="unitSearch" placeholder="Search unit number…" class="pl-9" />
                            </div>
                            <Select v-model="unitBuildingFilter">
                                <SelectTrigger class="w-full sm:w-36"><SelectValue placeholder="Building" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Buildings</SelectItem>
                                    <SelectItem v-for="b in buildings" :key="b.id" :value="b.id">{{ b.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="unitTypeFilter">
                                <SelectTrigger class="w-full sm:w-36"><SelectValue placeholder="Type" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Types</SelectItem>
                                    <SelectItem v-for="t in unitTypes" :key="t.id" :value="t.id">{{ t.name }}</SelectItem>
                                </SelectContent>
                            </Select>
                            <Select v-model="unitStatusFilter">
                                <SelectTrigger class="w-full sm:w-36"><SelectValue placeholder="Status" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="all">All Statuses</SelectItem>
                                    <SelectItem value="available">Available</SelectItem>
                                    <SelectItem value="occupied">Occupied</SelectItem>
                                    <SelectItem value="reserved">Reserved</SelectItem>
                                    <SelectItem value="maintenance">Maintenance</SelectItem>
                                    <SelectItem value="unavailable">Unavailable</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <Button v-if="can('properties.edit')" size="sm" @click="openAddUnit">
                            <Plus class="mr-1.5 h-3.5 w-3.5" />
                            Add Unit
                        </Button>
                    </div>

                    <div v-if="property.total_units === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                        <DoorOpen class="mb-3 h-10 w-10 text-muted-foreground/40" />
                        <p class="text-sm font-medium">No units yet</p>
                        <Button v-if="can('properties.edit')" size="sm" class="mt-3" @click="openAddUnit">
                            <Plus class="mr-1.5 h-3.5 w-3.5" />
                            Add Unit
                        </Button>
                    </div>

                    <div v-else class="rounded-lg border">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Unit</TableHead>
                                    <TableHead>Building</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Floor</TableHead>
                                    <TableHead class="text-right">Rent</TableHead>
                                    <TableHead class="text-right">Deposit</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead class="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                <TableRow v-if="filteredUnits.length === 0">
                                    <TableCell colspan="8" class="py-8 text-center text-sm text-muted-foreground">
                                        No units match the current filters.
                                    </TableCell>
                                </TableRow>
                                <TableRow v-for="u in filteredUnits" :key="u.id">
                                    <TableCell class="font-medium">{{ u.unit_number }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ u.building?.name ?? '—' }}</TableCell>
                                    <TableCell class="text-sm text-muted-foreground">{{ u.unit_type?.name ?? '—' }}</TableCell>
                                    <TableCell class="text-sm">{{ u.floor ?? '—' }}</TableCell>
                                    <TableCell class="text-right text-sm">
                                        ₱{{ u.rent_amount.toLocaleString('en-PH') }}
                                    </TableCell>
                                    <TableCell class="text-right text-sm">
                                        ₱{{ u.deposit_amount.toLocaleString('en-PH') }}
                                    </TableCell>
                                    <TableCell>
                                        <UnitStatusBadge :status="u.status" :label="u.status_label" />
                                    </TableCell>
                                    <TableCell class="text-right">
                                        <DropdownMenu v-if="can('properties.edit')">
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <MoreHorizontal class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end">
                                                <DropdownMenuItem @click="openEditUnit(u)">Edit</DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem class="text-destructive" @click="deleteUnit(u)">Delete</DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </TableCell>
                                </TableRow>
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </TabsContent>

            <TabsContent value="amenities" class="mt-6">
                <div class="space-y-6">
                    <div>
                        <h3 class="mb-3 font-medium">Property Amenities</h3>
                        <div class="grid grid-cols-2 gap-2 sm:grid-cols-3 md:grid-cols-4">
                            <label
                                v-for="amenity in propertyAmenities"
                                :key="amenity.id"
                                class="flex cursor-pointer items-center gap-2 rounded-lg border p-3 transition-colors hover:bg-muted"
                                :class="amenitySyncForm.amenity_ids.includes(amenity.id) ? 'border-primary bg-primary/5' : 'border-border'"
                            >
                                <input
                                    type="checkbox"
                                    class="accent-primary"
                                    :value="amenity.id"
                                    v-model="amenitySyncForm.amenity_ids"
                                />
                                <span class="text-sm">{{ amenity.name }}</span>
                            </label>
                        </div>
                    </div>

                    <Button
                        v-if="can('properties.edit')"
                        :disabled="amenitySyncForm.processing"
                        @click="syncAmenities"
                    >
                        {{ amenitySyncForm.processing ? 'Saving…' : 'Save Amenities' }}
                    </Button>
                </div>
            </TabsContent>

            <TabsContent value="gallery" class="mt-6">
                <div class="space-y-4">
                    <div v-if="property.images.length === 0" class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
                        <p class="text-sm font-medium text-muted-foreground">No gallery images yet</p>
                    </div>

                    <div v-else class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                        <div v-for="img in property.images" :key="img.id" class="group relative aspect-square overflow-hidden rounded-lg">
                            <img :src="img.url" :alt="img.caption ?? ''" class="h-full w-full object-cover" />
                            <div class="absolute inset-0 flex items-center justify-center bg-black/50 opacity-0 transition-opacity group-hover:opacity-100">
                                <Button
                                    v-if="can('properties.edit')"
                                    size="icon"
                                    variant="destructive"
                                    class="h-8 w-8"
                                    @click="router.delete(`/properties/${property.id}/images/${img.id}`, { preserveScroll: true })"
                                >
                                    <Trash2 class="h-4 w-4" />
                                </Button>
                            </div>
                            <p v-if="img.caption" class="absolute bottom-0 left-0 right-0 bg-black/60 px-2 py-1 text-xs text-white truncate">
                                {{ img.caption }}
                            </p>
                        </div>
                    </div>

                    <div v-if="can('properties.edit')">
                        <form @submit.prevent="() => {}" enctype="multipart/form-data">
                            <label
                                for="gallery-upload"
                                class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-border py-8 transition-colors hover:border-primary hover:bg-muted/50"
                            >
                                <Plus class="mb-2 h-8 w-8 text-muted-foreground" />
                                <span class="text-sm font-medium">Upload Images</span>
                                <span class="mt-1 text-xs text-muted-foreground">PNG, JPG up to 8MB each</span>
                                <input
                                    id="gallery-upload"
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    class="sr-only"
                                    @change="(e: Event) => {
                                        const files = (e.target as HTMLInputElement).files;
                                        if (!files?.length) return;
                                        const fd = new FormData();
                                        Array.from(files).forEach((f, i) => fd.append(`images[${i}]`, f));
                                        router.post(`/properties/${property.id}/images`, fd, { preserveScroll: true });
                                    }"
                                />
                            </label>
                        </form>
                    </div>
                </div>
            </TabsContent>
        </Tabs>
    </div>

    <BuildingFormModal
        :open="buildingModalOpen"
        :property-id="property.id"
        :building="editingBuilding"
        @update:open="buildingModalOpen = $event"
    />

    <UnitFormModal
        :open="unitModalOpen"
        :property-id="property.id"
        :buildings="buildings"
        :unit-types="unitTypes"
        :unit="editingUnit"
        @update:open="unitModalOpen = $event"
    />
</template>
