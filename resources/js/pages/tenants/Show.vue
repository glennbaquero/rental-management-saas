<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Building2, FileText, Mail, MapPin, Phone, User } from '@lucide/vue';
import TenantStatusBadge from '@/components/tenants/TenantStatusBadge.vue';
import IdDocumentCard from '@/components/tenants/IdDocumentCard.vue';
import IdDocumentModal from '@/components/tenants/IdDocumentModal.vue';
import EmergencyContactModal from '@/components/tenants/EmergencyContactModal.vue';
import TenantFileUploader from '@/components/tenants/TenantFileUploader.vue';
import RentalHistoryTimeline from '@/components/tenants/RentalHistoryTimeline.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { usePermission } from '@/composables/usePermission';
import type {
    EmergencyContact,
    RentalHistoryEntry,
    RentalTenant,
    TenantFile,
    TenantIdDocument,
} from '@/types/tenant';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tenants', href: '/tenants' },
            { title: 'Tenant Details', href: '#' },
        ],
    },
});

const props = defineProps<{
    tenant: RentalTenant;
    idDocuments: TenantIdDocument[];
    emergencyContacts: EmergencyContact[];
    tenantFiles: TenantFile[];
    rentalHistory: RentalHistoryEntry[];
}>();

const { can } = usePermission();

const docModalOpen    = ref(false);
const editingDoc      = ref<TenantIdDocument | null>(null);
const contactModalOpen = ref(false);
const editingContact  = ref<EmergencyContact | null>(null);

function openAddDoc() {
    editingDoc.value   = null;
    docModalOpen.value = true;
}
function openEditDoc(doc: TenantIdDocument) {
    editingDoc.value   = doc;
    docModalOpen.value = true;
}

function openAddContact() {
    editingContact.value   = null;
    contactModalOpen.value = true;
}
function openEditContact(contact: EmergencyContact) {
    editingContact.value   = contact;
    contactModalOpen.value = true;
}
function deleteContact(contact: EmergencyContact) {
    if (confirm(`Remove ${contact.name} from emergency contacts?`)) {
        router.delete(`/tenants/${props.tenant.id}/emergency-contacts/${contact.id}`);
    }
}

function confirmArchive() {
    if (confirm(`Archive ${props.tenant.full_name}? This action can be undone.`)) {
        router.delete(`/tenants/${props.tenant.id}`);
    }
}

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function formatCurrency(value: number | null): string {
    if (value === null || value === undefined) return '—';
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(value);
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}
</script>

<template>
    <Head :title="tenant.full_name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Page header -->
        <div class="flex items-center justify-between">
            <Button variant="ghost" size="sm" as-child>
                <Link href="/tenants">← Tenants</Link>
            </Button>
            <div class="flex gap-2">
                <Button v-if="can('tenants.edit')" variant="outline" as-child>
                    <Link :href="`/tenants/${tenant.id}/edit`">Edit</Link>
                </Button>
                <Button
                    v-if="can('tenants.delete')"
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="confirmArchive"
                >
                    Archive
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-4">

            <!-- Profile Card -->
            <Card class="lg:col-span-1 h-fit">
                <CardContent class="flex flex-col items-center gap-4 pt-6">
                    <Avatar class="h-20 w-20">
                        <AvatarImage :src="tenant.profile_photo_url ?? ''" :alt="tenant.full_name" />
                        <AvatarFallback class="text-2xl">{{ getInitials(tenant.full_name) }}</AvatarFallback>
                    </Avatar>
                    <div class="text-center">
                        <p class="text-lg font-semibold">{{ tenant.full_name }}</p>
                        <p v-if="tenant.tenant_code" class="text-sm text-muted-foreground">{{ tenant.tenant_code }}</p>
                    </div>
                    <TenantStatusBadge :status="tenant.status" :label="tenant.status_label" />
                    <Separator />
                    <div class="w-full space-y-2 text-sm">
                        <div v-if="tenant.email" class="flex items-center gap-2 text-muted-foreground">
                            <Mail class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ tenant.email }}</span>
                        </div>
                        <div v-if="tenant.phone" class="flex items-center gap-2 text-muted-foreground">
                            <Phone class="h-4 w-4 shrink-0" />
                            <span>{{ tenant.phone }}</span>
                        </div>
                        <div v-if="tenant.occupation" class="flex items-center gap-2 text-muted-foreground">
                            <User class="h-4 w-4 shrink-0" />
                            <span>{{ tenant.occupation }}</span>
                        </div>
                        <div v-if="tenant.employer" class="flex items-center gap-2 text-muted-foreground">
                            <Building2 class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ tenant.employer }}</span>
                        </div>
                    </div>
                    <template v-if="tenant.current_lease">
                        <Separator />
                        <div class="w-full text-sm">
                            <p class="mb-1 text-xs font-medium uppercase tracking-wide text-muted-foreground">Current Property</p>
                            <p class="font-medium">{{ tenant.current_lease.property_name }}</p>
                            <p class="text-muted-foreground">
                                Unit {{ tenant.current_lease.unit_number }}
                                <template v-if="tenant.current_lease.building_name">
                                    · {{ tenant.current_lease.building_name }}
                                </template>
                            </p>
                            <p class="mt-1 font-medium text-primary">
                                {{ formatCurrency(tenant.current_lease.rent_amount) }}<span class="text-xs text-muted-foreground">/mo</span>
                            </p>
                        </div>
                    </template>
                </CardContent>
            </Card>

            <!-- Tabs content -->
            <div class="lg:col-span-3">
                <Tabs default-value="overview">
                    <TabsList class="mb-4 flex-wrap h-auto gap-1">
                        <TabsTrigger value="overview">Overview</TabsTrigger>
                        <TabsTrigger value="documents">
                            Documents
                            <Badge v-if="tenant.id_documents_count > 0" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ tenant.id_documents_count }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="contacts">
                            Emergency Contacts
                            <Badge v-if="tenant.emergency_contacts_count > 0" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ tenant.emergency_contacts_count }}
                            </Badge>
                        </TabsTrigger>
                        <TabsTrigger value="history">Rental History</TabsTrigger>
                        <TabsTrigger value="leases">
                            Leases
                            <Badge v-if="tenant.leases_count > 0" variant="secondary" class="ml-1.5 h-5 px-1.5 text-xs">
                                {{ tenant.leases_count }}
                            </Badge>
                        </TabsTrigger>
                    </TabsList>

                    <!-- Overview Tab -->
                    <TabsContent value="overview" class="flex flex-col gap-4">
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <!-- Personal -->
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm text-muted-foreground">Personal Info</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Date of Birth</p>
                                        <p>{{ formatDate(tenant.date_of_birth) }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Gender</p>
                                        <p>{{ tenant.gender_label ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Civil Status</p>
                                        <p>{{ tenant.civil_status_label ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Nationality</p>
                                        <p>{{ tenant.nationality ?? '—' }}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Contact -->
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm text-muted-foreground">Contact</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Email</p>
                                        <p class="truncate">{{ tenant.email ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Mobile</p>
                                        <p>{{ tenant.phone ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Alternate</p>
                                        <p>{{ tenant.alternate_phone ?? '—' }}</p>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Employment -->
                            <Card>
                                <CardHeader class="pb-2">
                                    <CardTitle class="text-sm text-muted-foreground">Employment</CardTitle>
                                </CardHeader>
                                <CardContent class="space-y-2 text-sm">
                                    <div>
                                        <p class="text-xs text-muted-foreground">Occupation</p>
                                        <p>{{ tenant.occupation ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Employer</p>
                                        <p>{{ tenant.employer ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs text-muted-foreground">Monthly Income</p>
                                        <p>{{ formatCurrency(tenant.monthly_income) }}</p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Address -->
                        <Card v-if="tenant.current_address || tenant.city">
                            <CardHeader class="pb-2">
                                <CardTitle class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <MapPin class="h-4 w-4" /> Current Address
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="text-sm">
                                <p>{{ [tenant.current_address, tenant.city, tenant.province, tenant.postal_code, tenant.country].filter(Boolean).join(', ') }}</p>
                            </CardContent>
                        </Card>

                        <!-- Employer Address -->
                        <Card v-if="tenant.employer_address">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm text-muted-foreground">Employer Address</CardTitle>
                            </CardHeader>
                            <CardContent class="text-sm">
                                <p>{{ tenant.employer_address }}</p>
                            </CardContent>
                        </Card>

                        <!-- Notes -->
                        <Card v-if="tenant.notes">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-sm text-muted-foreground">Notes</CardTitle>
                            </CardHeader>
                            <CardContent class="text-sm whitespace-pre-line">
                                {{ tenant.notes }}
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Documents Tab -->
                    <TabsContent value="documents" class="flex flex-col gap-6">
                        <!-- ID Documents -->
                        <div>
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-medium">ID Documents</h3>
                                <Button v-if="can('tenants.edit')" size="sm" variant="outline" @click="openAddDoc">
                                    + Add Document
                                </Button>
                            </div>
                            <div v-if="idDocuments.length === 0" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                                No ID documents uploaded yet.
                            </div>
                            <div v-else class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <IdDocumentCard
                                    v-for="doc in idDocuments"
                                    :key="doc.id"
                                    :document="doc"
                                    :can-edit="can('tenants.edit')"
                                    :tenant-id="tenant.id"
                                    @edit="openEditDoc"
                                />
                            </div>
                        </div>

                        <Separator />

                        <!-- Additional Files -->
                        <div>
                            <h3 class="mb-3 text-sm font-medium">Additional Files</h3>
                            <TenantFileUploader
                                :tenant-id="tenant.id"
                                :files="tenantFiles"
                                :can-upload="can('tenants.edit')"
                            />
                        </div>
                    </TabsContent>

                    <!-- Emergency Contacts Tab -->
                    <TabsContent value="contacts" class="flex flex-col gap-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-muted-foreground">{{ emergencyContacts.length }} contact(s) on file</p>
                            <Button v-if="can('tenants.edit')" size="sm" variant="outline" @click="openAddContact">
                                + Add Contact
                            </Button>
                        </div>
                        <div v-if="emergencyContacts.length === 0" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                            No emergency contacts added yet.
                        </div>
                        <div v-else class="rounded-lg border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Relationship</TableHead>
                                        <TableHead>Phone</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead class="text-center">Primary</TableHead>
                                        <TableHead v-if="can('tenants.edit')" class="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-for="contact in emergencyContacts" :key="contact.id">
                                        <TableCell class="font-medium">{{ contact.name }}</TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ contact.relationship }}</TableCell>
                                        <TableCell class="text-sm">
                                            <div>{{ contact.phone }}</div>
                                            <div v-if="contact.alternate_number" class="text-xs text-muted-foreground">{{ contact.alternate_number }}</div>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ contact.email ?? '—' }}</TableCell>
                                        <TableCell class="text-center">
                                            <span v-if="contact.is_primary" class="text-primary">★</span>
                                            <span v-else class="text-muted-foreground/30">☆</span>
                                        </TableCell>
                                        <TableCell v-if="can('tenants.edit')" class="text-right">
                                            <div class="flex justify-end gap-2">
                                                <Button size="sm" variant="ghost" @click="openEditContact(contact)">Edit</Button>
                                                <Button size="sm" variant="ghost" class="text-destructive hover:text-destructive" @click="deleteContact(contact)">
                                                    Remove
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </TabsContent>

                    <!-- Rental History Tab -->
                    <TabsContent value="history">
                        <div v-if="rentalHistory.length === 0" class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                            No rental history found.
                        </div>
                        <RentalHistoryTimeline v-else :history="rentalHistory" />
                    </TabsContent>

                    <!-- Leases Tab -->
                    <TabsContent value="leases">
                        <div class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground">
                            <FileText class="mx-auto mb-3 h-8 w-8 text-muted-foreground/40" />
                            <p>Lease management is coming soon.</p>
                            <p class="mt-1">This tenant has <strong>{{ tenant.leases_count }}</strong> lease(s) on record.</p>
                        </div>
                    </TabsContent>
                </Tabs>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <IdDocumentModal
        :open="docModalOpen"
        :tenant-id="tenant.id"
        :document="editingDoc"
        @update:open="docModalOpen = $event"
    />
    <EmergencyContactModal
        :open="contactModalOpen"
        :tenant-id="tenant.id"
        :contact="editingContact"
        @update:open="contactModalOpen = $event"
    />
</template>
