<script setup lang="ts">
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Upload, X } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { RentalTenant, SelectOption } from '@/types/tenant';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tenants', href: '/tenants' },
            { title: 'Edit Tenant', href: '#' },
        ],
    },
});

const props = defineProps<{
    tenant: RentalTenant;
    statuses: SelectOption[];
    genders: SelectOption[];
    civilStatuses: SelectOption[];
}>();

const photoPreview = ref<string | null>(props.tenant.profile_photo_url);
const photoInput   = ref<HTMLInputElement | null>(null);

const form = useForm({
    first_name:       props.tenant.first_name,
    middle_name:      props.tenant.middle_name ?? '',
    last_name:        props.tenant.last_name,
    email:            props.tenant.email ?? '',
    phone:            props.tenant.phone ?? '',
    alternate_phone:  props.tenant.alternate_phone ?? '',
    date_of_birth:    props.tenant.date_of_birth ?? '',
    gender:           props.tenant.gender ?? '',
    civil_status:     props.tenant.civil_status ?? '',
    nationality:      props.tenant.nationality ?? '',
    current_address:  props.tenant.current_address ?? '',
    city:             props.tenant.city ?? '',
    province:         props.tenant.province ?? '',
    country:          props.tenant.country ?? '',
    postal_code:      props.tenant.postal_code ?? '',
    occupation:       props.tenant.occupation ?? '',
    employer:         props.tenant.employer ?? '',
    employer_address: props.tenant.employer_address ?? '',
    monthly_income:   props.tenant.monthly_income?.toString() ?? '',
    status:           props.tenant.status ?? 'prospect',
    notes:            props.tenant.notes ?? '',
    profile_photo:    null as File | null,
});

function onPhotoChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.profile_photo = file;
    photoPreview.value = URL.createObjectURL(file);
}

function removePhoto() {
    form.profile_photo = null;
    photoPreview.value = null;
    if (photoInput.value) photoInput.value.value = '';
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            gender:         data.gender || null,
            civil_status:   data.civil_status || null,
            status:         data.status || 'prospect',
            monthly_income: data.monthly_income !== '' ? data.monthly_income : null,
            profile_photo:  data.profile_photo ?? undefined,
        }))
        .patch(`/tenants/${props.tenant.id}`, {
            forceFormData: !!form.profile_photo,
        });
}

function getInitials(): string {
    return [form.first_name[0], form.last_name[0]].filter(Boolean).join('').toUpperCase() || 'T';
}
</script>

<template>
    <Head :title="`Edit — ${tenant.full_name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            :title="`Edit: ${tenant.full_name}`"
            :description="`${tenant.tenant_code ?? ''} · Last updated information`"
        />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- Photo + Status sidebar -->
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Profile Photo</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col items-center gap-4">
                        <Avatar class="h-24 w-24">
                            <AvatarImage :src="photoPreview ?? ''" :alt="tenant.full_name" />
                            <AvatarFallback class="text-xl">{{ getInitials() }}</AvatarFallback>
                        </Avatar>
                        <div class="flex gap-2">
                            <Button type="button" variant="outline" size="sm" @click="photoInput?.click()">
                                <Upload class="mr-2 h-4 w-4" />
                                Change Photo
                            </Button>
                            <Button v-if="form.profile_photo" type="button" variant="ghost" size="sm" @click="removePhoto">
                                <X class="h-4 w-4" />
                            </Button>
                        </div>
                        <input ref="photoInput" type="file" accept="image/*" class="hidden" @change="onPhotoChange" />
                        <p v-if="form.errors.profile_photo" class="text-xs text-destructive">{{ form.errors.profile_photo }}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Status</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Select v-model="form.status">
                            <SelectTrigger><SelectValue /></SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>
            </div>

            <!-- Main form -->
            <div class="lg:col-span-2 flex flex-col gap-6">

                <Card>
                    <CardHeader><CardTitle class="text-sm">Personal Information</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1.5">
                            <Label>First Name <span class="text-destructive">*</span></Label>
                            <Input v-model="form.first_name" />
                            <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Middle Name</Label>
                            <Input v-model="form.middle_name" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Last Name <span class="text-destructive">*</span></Label>
                            <Input v-model="form.last_name" />
                            <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Date of Birth</Label>
                            <Input v-model="form.date_of_birth" type="date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Gender</Label>
                            <Select v-model="form.gender">
                                <SelectTrigger><SelectValue placeholder="Select…" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="g in genders" :key="g.value" :value="g.value">{{ g.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Civil Status</Label>
                            <Select v-model="form.civil_status">
                                <SelectTrigger><SelectValue placeholder="Select…" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="cs in civilStatuses" :key="cs.value" :value="cs.value">{{ cs.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Nationality</Label>
                            <Input v-model="form.nationality" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-sm">Contact Information</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label>Email</Label>
                            <Input v-model="form.email" type="email" />
                            <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Mobile Number</Label>
                            <Input v-model="form.phone" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Alternate Number</Label>
                            <Input v-model="form.alternate_phone" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-sm">Current Address</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label>Street Address</Label>
                            <Input v-model="form.current_address" />
                        </div>
                        <div class="flex flex-col gap-1.5"><Label>City</Label><Input v-model="form.city" /></div>
                        <div class="flex flex-col gap-1.5"><Label>Province</Label><Input v-model="form.province" /></div>
                        <div class="flex flex-col gap-1.5"><Label>Country</Label><Input v-model="form.country" /></div>
                        <div class="flex flex-col gap-1.5"><Label>Postal Code</Label><Input v-model="form.postal_code" /></div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-sm">Employment</CardTitle></CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5"><Label>Occupation</Label><Input v-model="form.occupation" /></div>
                        <div class="flex flex-col gap-1.5"><Label>Employer Name</Label><Input v-model="form.employer" /></div>
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label>Employer Address</Label>
                            <Input v-model="form.employer_address" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label>Monthly Income (₱)</Label>
                            <Input v-model="form.monthly_income" type="number" min="0" step="0.01" />
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-sm">Additional Notes</CardTitle></CardHeader>
                    <CardContent>
                        <Textarea v-model="form.notes" rows="4" />
                    </CardContent>
                </Card>

                <div class="flex items-center justify-end gap-3">
                    <Button variant="outline" @click="router.visit(`/tenants/${tenant.id}`)">Cancel</Button>
                    <Button :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
