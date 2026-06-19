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
import type { SelectOption } from '@/types/property';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Properties', href: '/properties' },
            { title: 'Add Property', href: '/properties/create' },
        ],
    },
});

defineProps<{
    types: SelectOption[];
    statuses: SelectOption[];
}>();

const form = useForm({
    name:           '',
    code:           '',
    type:           '',
    description:    '',
    address:        '',
    city:           '',
    province:       '',
    country:        'PH',
    postal_code:    '',
    latitude:       '',
    longitude:      '',
    status:         'active',
    featured_image: null as File | null,
});

const imagePreview = ref<string | null>(null);

function onImageChange(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    form.featured_image = file;
    imagePreview.value = URL.createObjectURL(file);
}

function submit() {
    form.post('/properties', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Add Property" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center gap-4">
            <Button variant="ghost" size="icon" as-child>
                <Link href="/properties"><ChevronLeft class="h-4 w-4" /></Link>
            </Button>
            <Heading title="Add Property" description="Create a new property in your portfolio." />
        </div>

        <form @submit.prevent="submit" class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="space-y-6 lg:col-span-2">
                <Card>
                    <CardHeader><CardTitle class="text-base">Basic Information</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label for="name">Property Name <span class="text-destructive">*</span></Label>
                                <Input id="name" v-model="form.name" placeholder="Skyline Apartments" />
                                <p v-if="form.errors.name" class="text-xs text-destructive">{{ form.errors.name }}</p>
                            </div>

                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label for="code">Property Code</Label>
                                <Input id="code" v-model="form.code" placeholder="PROP-001" />
                                <p v-if="form.errors.code" class="text-xs text-destructive">{{ form.errors.code }}</p>
                            </div>

                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label for="type">Property Type <span class="text-destructive">*</span></Label>
                                <Select v-model="form.type">
                                    <SelectTrigger id="type"><SelectValue placeholder="Select type" /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="t in types" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                                <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                            </div>

                            <div class="col-span-2 space-y-1.5 sm:col-span-1">
                                <Label for="status">Status</Label>
                                <Select v-model="form.status">
                                    <SelectTrigger id="status"><SelectValue /></SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                                    </SelectContent>
                                </Select>
                            </div>

                            <div class="col-span-2 space-y-1.5">
                                <Label for="description">Description</Label>
                                <Textarea id="description" v-model="form.description" placeholder="Describe the property…" rows="3" />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader><CardTitle class="text-base">Address</CardTitle></CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2 space-y-1.5">
                                <Label for="address">Street Address <span class="text-destructive">*</span></Label>
                                <Input id="address" v-model="form.address" placeholder="123 Ayala Avenue" />
                                <p v-if="form.errors.address" class="text-xs text-destructive">{{ form.errors.address }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="city">City <span class="text-destructive">*</span></Label>
                                <Input id="city" v-model="form.city" placeholder="Makati" />
                                <p v-if="form.errors.city" class="text-xs text-destructive">{{ form.errors.city }}</p>
                            </div>

                            <div class="space-y-1.5">
                                <Label for="province">Province / State</Label>
                                <Input id="province" v-model="form.province" placeholder="Metro Manila" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="postal_code">Postal Code</Label>
                                <Input id="postal_code" v-model="form.postal_code" placeholder="1226" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="country">Country</Label>
                                <Input id="country" v-model="form.country" placeholder="PH" maxlength="2" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="latitude">Latitude</Label>
                                <Input id="latitude" v-model="form.latitude" type="number" step="any" placeholder="14.5547" />
                            </div>

                            <div class="space-y-1.5">
                                <Label for="longitude">Longitude</Label>
                                <Input id="longitude" v-model="form.longitude" type="number" step="any" placeholder="121.0244" />
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
                            for="featured_image"
                            class="flex cursor-pointer flex-col items-center justify-center rounded-lg border-2 border-dashed border-border p-6 transition-colors hover:border-primary hover:bg-muted/50"
                        >
                            <img v-if="imagePreview" :src="imagePreview" alt="Preview" class="mb-3 h-32 w-full rounded object-cover" />
                            <Upload v-else class="mb-2 h-8 w-8 text-muted-foreground" />
                            <span class="text-sm font-medium">{{ imagePreview ? 'Change image' : 'Upload image' }}</span>
                            <span class="mt-1 text-xs text-muted-foreground">PNG, JPG up to 4MB</span>
                            <input id="featured_image" type="file" accept="image/*" class="sr-only" @change="onImageChange" />
                        </label>
                        <p v-if="form.errors.featured_image" class="mt-1 text-xs text-destructive">{{ form.errors.featured_image }}</p>
                    </CardContent>
                </Card>

                <div class="flex gap-3">
                    <Button type="button" variant="outline" class="flex-1" as-child>
                        <Link href="/properties">Cancel</Link>
                    </Button>
                    <Button type="submit" class="flex-1" :disabled="form.processing">
                        {{ form.processing ? 'Creating…' : 'Create Property' }}
                    </Button>
                </div>
            </div>
        </form>
    </div>
</template>
