<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ChevronLeft, Upload } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { Property, SelectOption } from '@/types/property';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: '/properties' },
            { title: 'Edit Property' },
        ],
    },
});

const props = defineProps<{
    property: Property;
    types: SelectOption[];
    statuses: SelectOption[];
}>();

const form = useForm({
    name:           props.property.name,
    code:           props.property.code ?? '',
    type:           props.property.type ?? '',
    description:    props.property.description ?? '',
    address:        props.property.address,
    city:           props.property.city,
    province:       props.property.province ?? '',
    country:        props.property.country,
    postal_code:    props.property.postal_code ?? '',
    latitude:       props.property.latitude?.toString() ?? '',
    longitude:      props.property.longitude?.toString() ?? '',
    status:         props.property.status ?? 'active',
    featured_image: null as File | null,
});

const imagePreview = ref<string | null>(props.property.featured_image_url);

function onImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.featured_image = file;
    imagePreview.value = URL.createObjectURL(file);
}

function submit() {
    form.transform((data) => ({ ...data, _method: 'PATCH' }))
        .post(`/properties/${props.property.id}`, { forceFormData: true });
}
</script>

<template>
    <Head :title="`Edit ${property.name}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" as-child>
                <Link :href="`/properties/${property.id}`"><ChevronLeft class="h-4 w-4" /></Link>
            </Button>
            <Heading :title="`Edit: ${property.name}`" description="Update property information." />
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader><CardTitle class="text-base">Basic Information</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label>Property Name <span class="text-destructive">*</span></Label>
                                <Input v-model="form.name" />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label>Property Code</Label>
                                <Input v-model="form.code" />
                                <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                            </div>

                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label>Property Type <span class="text-destructive">*</span></Label>
                                <Select v-model="form.type">
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                            </div>

                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label>Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="col-span-2 space-y-1.5">
                                <Label>Description</Label>
                                <Textarea v-model="form.description" rows="3" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Address</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 space-y-1.5">
                                <Label>Street Address <span class="text-destructive">*</span></Label>
                                <Input v-model="form.address" />
                                <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>City <span class="text-destructive">*</span></Label>
                                <Input v-model="form.city" />
                                <p v-if="form.errors.city" class="text-xs text-destructive">{{ form.errors.city }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Province / State</Label>
                                <Input v-model="form.province" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Postal Code</Label>
                                <Input v-model="form.postal_code" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Country</Label>
                                <Input v-model="form.country" maxlength="2" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Latitude</Label>
                                <Input v-model="form.latitude" type="number" step="any" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Longitude</Label>
                                <Input v-model="form.longitude" type="number" step="any" />
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>

            <div class="space-y-6">
                <Card>
                    <CardHeader><CardTitle class="text-base">Featured Image</CardTitle></CardHeader>
                    <CardContent>
                        <label
                            for="featured_image_edit"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-border p-4 transition-colors hover:border-primary hover:bg-muted/50"
                        >
                            <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="mb-3 h-32 w-full rounded object-cover" />
                            <Upload v-else class="mb-2 h-8 w-8 text-muted-foreground" />
                            <span class="text-sm font-medium">{{ imagePreview ? 'Change image' : 'Upload image' }}</span>
                            <span class="mt-1 text-xs text-muted-foreground">PNG, JPG up to 4MB</span>
                            <input id="featured_image_edit" type="file" accept="image/*" class="sr-only" @change="onImageChange" />
                        </label>
                    </CardContent>
                </Card>

                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" as-child>
                        <Link :href="`/properties/${property.id}`">Cancel</Link>
                    </Button>
                    <Button type="submit" class="flex-1" :disabled="form.processing">
                        {{ form.processing ? 'Saving…' : 'Save Changes' }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
