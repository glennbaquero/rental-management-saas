<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { ArrowLeft } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import type { PriorityOption, SelectOption } from '@/types/maintenance';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Maintenance Requests', href: '/maintenance' },
            { title: 'Create Ticket', href: '/maintenance/create' },
        ],
    },
});

interface PropertyOption {
    id: string;
    name: string;
    buildings: { id: string; name: string; property_id: string }[];
    units: { id: string; unit_number: string; property_id: string }[];
}

interface TenantOption {
    id: string;
    first_name: string;
    last_name: string;
    email: string;
}

const props = defineProps<{
    categories: SelectOption[];
    priorities: PriorityOption[];
    properties: PropertyOption[];
    tenants: TenantOption[];
}>();

const form = useForm({
    property_id: '',
    building_id: '',
    unit_id: '',
    rental_tenant_id: '',
    category: '',
    title: '',
    description: '',
    priority: 'low',
    preferred_schedule: '',
    notes: '',
    attachments: [] as File[],
});

const selectedProperty = computed(() =>
    props.properties.find(p => p.id === form.property_id)
);

const availableBuildings = computed(() =>
    selectedProperty.value?.buildings ?? []
);

const availableUnits = computed(() =>
    selectedProperty.value?.units.filter(u =>
        !form.building_id || true
    ) ?? []
);

watch(() => form.property_id, () => {
    form.building_id = '';
    form.unit_id     = '';
});

watch(() => form.building_id, () => {
    form.unit_id = '';
});

function onFilesChange(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files ?? []);
    form.attachments = files;
}

function submit() {
    form.post('/maintenance', {
        forceFormData: true,
    });
}
</script>

<template>
    <Head title="Create Maintenance Ticket" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center gap-3">
            <Button variant="ghost" size="icon" @click="router.visit('/maintenance')">
                <ArrowLeft class="h-4 w-4" />
            </Button>
            <Heading title="Create Maintenance Ticket" description="Submit a new maintenance request." />
        </div>

        <form @submit.prevent="submit">
            <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
                <!-- Main Form -->
                <div class="space-y-6 lg:col-span-2">
                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Location</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="space-y-1.5">
                                <Label>Property <span class="text-destructive">*</span></Label>
                                <Select v-model="form.property_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select property" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="p in properties" :key="p.id" :value="p.id">
                                            {{ p.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.property_id" />
                            </div>

                            <div v-if="availableBuildings.length > 0" class="space-y-1.5">
                                <Label>Building</Label>
                                <Select
                                    :model-value="form.building_id || '_none'"
                                    @update:model-value="form.building_id = $event === '_none' ? '' : $event"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select building (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none">No building</SelectItem>
                                        <SelectItem v-for="b in availableBuildings" :key="b.id" :value="b.id">
                                            {{ b.name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.building_id" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Unit <span class="text-destructive">*</span></Label>
                                <Select v-model="form.unit_id" :disabled="!form.property_id">
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select unit" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem v-for="u in availableUnits" :key="u.id" :value="u.id">
                                            {{ u.unit_number }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.unit_id" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Tenant</Label>
                                <Select
                                    :model-value="form.rental_tenant_id || '_none'"
                                    @update:model-value="form.rental_tenant_id = $event === '_none' ? '' : $event"
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select tenant (optional)" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="_none">No tenant</SelectItem>
                                        <SelectItem v-for="t in tenants" :key="t.id" :value="t.id">
                                            {{ t.first_name }} {{ t.last_name }}
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError :message="form.errors.rental_tenant_id" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Issue Details</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>Category <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.category">
                                        <SelectTrigger>
                                            <SelectValue placeholder="Select category" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="c in categories" :key="c.value" :value="c.value">
                                                {{ c.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.category" />
                                </div>

                                <div class="space-y-1.5">
                                    <Label>Priority <span class="text-destructive">*</span></Label>
                                    <Select v-model="form.priority">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="p in priorities" :key="p.value" :value="p.value">
                                                {{ p.icon }} {{ p.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError :message="form.errors.priority" />
                                </div>
                            </div>

                            <div class="space-y-1.5">
                                <Label>Issue Title <span class="text-destructive">*</span></Label>
                                <Input v-model="form.title" placeholder="Brief title of the issue" />
                                <InputError :message="form.errors.title" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Description <span class="text-destructive">*</span></Label>
                                <Textarea
                                    v-model="form.description"
                                    rows="5"
                                    placeholder="Describe the issue in detail..."
                                />
                                <InputError :message="form.errors.description" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Preferred Schedule</Label>
                                <Input type="datetime-local" v-model="form.preferred_schedule" />
                                <InputError :message="form.errors.preferred_schedule" />
                            </div>

                            <div class="space-y-1.5">
                                <Label>Additional Notes</Label>
                                <Textarea v-model="form.notes" rows="3" placeholder="Any additional notes..." />
                                <InputError :message="form.errors.notes" />
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-base">Attachments</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-3">
                            <p class="text-xs text-muted-foreground">Upload images, videos, or PDF files (max 10 files, 50MB each).</p>
                            <Input
                                type="file"
                                multiple
                                accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4,.mov"
                                @change="onFilesChange"
                            />
                            <InputError :message="form.errors.attachments" />
                        </CardContent>
                    </Card>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">
                    <Card>
                        <CardContent class="pt-6 space-y-3">
                            <Button type="submit" class="w-full" :disabled="form.processing">
                                {{ form.processing ? 'Creating…' : 'Create Ticket' }}
                            </Button>
                            <Button type="button" variant="outline" class="w-full" @click="router.visit('/maintenance')">
                                Cancel
                            </Button>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle class="text-sm">Priority Guide</CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-2 text-xs text-muted-foreground">
                            <p>🟢 <strong>Low</strong> — Minor issues, non-urgent</p>
                            <p>🟡 <strong>Medium</strong> — Moderate inconvenience</p>
                            <p>🟠 <strong>High</strong> — Significant impact</p>
                            <p>🔴 <strong>Urgent</strong> — Needs attention ASAP</p>
                            <p>❗ <strong>Emergency</strong> — Water flooding, fire hazard, electrical danger</p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </form>
    </div>
</template>
