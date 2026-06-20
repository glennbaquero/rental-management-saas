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
import type { SelectOption } from '@/types/tenant';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Tenants', href: '/tenants' },
            { title: 'Add Tenant', href: '/tenants/create' },
        ],
    },
});

defineProps<{
    statuses: SelectOption[];
    genders: SelectOption[];
    civilStatuses: SelectOption[];
}>();

const photoPreview = ref<string | null>(null);
const photoInput   = ref<HTMLInputElement | null>(null);

const form = useForm({
    first_name:      '',
    middle_name:     '',
    last_name:       '',
    email:           '',
    phone:           '',
    alternate_phone: '',
    date_of_birth:   '',
    gender:          '',
    civil_status:    '',
    nationality:     '',
    current_address: '',
    city:            '',
    province:        '',
    country:         '',
    postal_code:     '',
    occupation:      '',
    employer:        '',
    employer_address: '',
    monthly_income:  '',
    status:          'prospect',
    notes:           '',
    profile_photo:   null as File | null,
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
            gender:          data.gender || null,
            civil_status:    data.civil_status || null,
            status:          data.status || 'prospect',
            monthly_income:  data.monthly_income !== '' ? data.monthly_income : null,
            profile_photo:   data.profile_photo ?? undefined,
        }))
        .post('/tenants', {
            forceFormData: !!form.profile_photo,
        });
}

function getInitials(): string {
    return [form.first_name[0], form.last_name[0]].filter(Boolean).join('').toUpperCase() || 'T';
}
</script>

<template>
    <Head title="Add Tenant" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading title="Add Tenant" description="Fill in the tenant's personal and contact information." />

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

            <!-- Photo + Status sidebar -->
            <div class="flex flex-col gap-4">
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Profile Photo</CardTitle>
                    </CardHeader>
                    <CardContent class="flex flex-col items-center gap-4">
                        <Avatar class="h-24 w-24">
                            <AvatarImage :src="photoPreview ?? ''" />
                            <AvatarFallback class="text-xl">{{ getInitials() }}</AvatarFallback>
                        </Avatar>
                        <div class="flex gap-2">
                            <Button type="button" variant="outline" size="sm" @click="photoInput?.click()">
                                <Upload class="mr-2 h-4 w-4" />
                                Upload
                            </Button>
                            <Button v-if="photoPreview" type="button" variant="ghost" size="sm" @click="removePhoto">
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
                            <SelectTrigger>
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="form.errors.status" class="mt-1 text-xs text-destructive">{{ form.errors.status }}</p>
                    </CardContent>
                </Card>
            </div>

            <!-- Main form -->
            <div class="lg:col-span-2 flex flex-col gap-6">

                <!-- Personal Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Personal Information</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                        <div class="flex flex-col gap-1.5">
                            <Label for="first_name">First Name <span class="text-destructive">*</span></Label>
                            <Input id="first_name" v-model="form.first_name" placeholder="Juan" />
                            <p v-if="form.errors.first_name" class="text-xs text-destructive">{{ form.errors.first_name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="middle_name">Middle Name</Label>
                            <Input id="middle_name" v-model="form.middle_name" placeholder="Miguel" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="last_name">Last Name <span class="text-destructive">*</span></Label>
                            <Input id="last_name" v-model="form.last_name" placeholder="dela Cruz" />
                            <p v-if="form.errors.last_name" class="text-xs text-destructive">{{ form.errors.last_name }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="date_of_birth">Date of Birth</Label>
                            <Input id="date_of_birth" v-model="form.date_of_birth" type="date" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="gender">Gender</Label>
                            <Select v-model="form.gender">
                                <SelectTrigger><SelectValue placeholder="Select…" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="g in genders" :key="g.value" :value="g.value">{{ g.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="civil_status">Civil Status</Label>
                            <Select v-model="form.civil_status">
                                <SelectTrigger><SelectValue placeholder="Select…" /></SelectTrigger>
                                <SelectContent>
                                    <SelectItem v-for="cs in civilStatuses" :key="cs.value" :value="cs.value">{{ cs.label }}</SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                        <div class="flex flex-col gap-1.5 sm:col-span-3">
                            <Label for="nationality">Nationality</Label>
                            <Input id="nationality" v-model="form.nationality" placeholder="Filipino" class="sm:max-w-xs" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Contact Info -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Contact Information</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label for="email">Email</Label>
                            <Input id="email" v-model="form.email" type="email" placeholder="juan@email.com" />
                            <p v-if="form.errors.email" class="text-xs text-destructive">{{ form.errors.email }}</p>
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="phone">Mobile Number</Label>
                            <Input id="phone" v-model="form.phone" placeholder="0917-123-4567" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="alternate_phone">Alternate Number</Label>
                            <Input id="alternate_phone" v-model="form.alternate_phone" placeholder="0932-987-6543" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Address -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Current Address</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label for="current_address">Street Address</Label>
                            <Input id="current_address" v-model="form.current_address" placeholder="123 Main Street" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="city">City</Label>
                            <Input id="city" v-model="form.city" placeholder="Quezon City" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="province">Province</Label>
                            <Input id="province" v-model="form.province" placeholder="Metro Manila" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="country">Country</Label>
                            <Input id="country" v-model="form.country" placeholder="Philippines" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="postal_code">Postal Code</Label>
                            <Input id="postal_code" v-model="form.postal_code" placeholder="1100" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Employment -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Employment</CardTitle>
                    </CardHeader>
                    <CardContent class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="flex flex-col gap-1.5">
                            <Label for="occupation">Occupation</Label>
                            <Input id="occupation" v-model="form.occupation" placeholder="Software Engineer" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="employer">Employer Name</Label>
                            <Input id="employer" v-model="form.employer" placeholder="Acme Corp" />
                        </div>
                        <div class="flex flex-col gap-1.5 sm:col-span-2">
                            <Label for="employer_address">Employer Address</Label>
                            <Input id="employer_address" v-model="form.employer_address" placeholder="123 Makati Ave, Makati City" />
                        </div>
                        <div class="flex flex-col gap-1.5">
                            <Label for="monthly_income">Monthly Income (₱)</Label>
                            <Input id="monthly_income" v-model="form.monthly_income" type="number" min="0" step="0.01" placeholder="50000" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Notes -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-sm">Additional Notes</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <Textarea
                            v-model="form.notes"
                            placeholder="Any additional information about this tenant…"
                            rows="4"
                        />
                    </CardContent>
                </Card>

                <!-- Actions -->
                <div class="flex items-center justify-end gap-3">
                    <Button variant="outline" @click="router.visit('/tenants')">Cancel</Button>
                    <Button :disabled="form.processing" @click="submit">
                        {{ form.processing ? 'Saving…' : 'Add Tenant' }}
                    </Button>
                </div>
            </div>
        </div>
    </div>
</template>
