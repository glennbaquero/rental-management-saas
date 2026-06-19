<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { usePermission } from '@/composables/usePermission';
import type { Organization, OrgSubscription } from '@/types/organization';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Organization', href: '/organization/settings' },
            { title: 'Settings', href: '/organization/settings' },
        ],
    },
});

const props = defineProps<{
    organization: Organization;
    subscription: OrgSubscription | null;
    plans: { id: string; name: string; price: number; billing_cycle: string }[];
}>();

const { can } = usePermission();

const canEdit = can('organization.manage_settings');

const form = useForm({
    company_name: props.organization.company_name,
    company_email: props.organization.company_email,
    company_phone: props.organization.company_phone ?? '',
    address: props.organization.address ?? '',
    tax_id: props.organization.tax_id ?? '',
    timezone: props.organization.timezone,
    currency: props.organization.currency,
});

const logoForm = useForm({ logo: null as File | null });
const logoPreview = ref<string | null>(props.organization.logo);
const logoInputRef = ref<HTMLInputElement | null>(null);

function onLogoChange(event: Event) {
    const file = (event.target as HTMLInputElement).files?.[0];
    if (!file) return;
    logoForm.logo = file;
    logoPreview.value = URL.createObjectURL(file);
}

function uploadLogo() {
    logoForm.post('/organization/settings/logo', {
        forceFormData: true,
        onSuccess: () => { logoForm.reset(); },
    });
}

function submit() {
    form.patch('/organization/settings');
}

const statusVariant: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    trial: 'secondary',
    active: 'default',
    past_due: 'destructive',
    canceled: 'outline',
    suspended: 'destructive',
};

const subscriptionStatusLabel: Record<string, string> = {
    trial: 'Trial',
    active: 'Active',
    past_due: 'Past Due',
    canceled: 'Canceled',
};

function formatDate(iso: string | null) {
    if (!iso) return '—';
    return new Date(iso).toLocaleDateString('en-US', { year: 'numeric', month: 'long', day: 'numeric' });
}

const commonTimezones = [
    'Asia/Manila', 'America/New_York', 'America/Chicago', 'America/Denver', 'America/Los_Angeles',
    'Europe/London', 'Europe/Paris', 'Europe/Berlin', 'Asia/Tokyo', 'Asia/Singapore',
    'Asia/Dubai', 'Australia/Sydney', 'Pacific/Auckland', 'UTC',
];

const currencies = ['PHP', 'USD', 'EUR', 'GBP', 'AUD', 'CAD', 'SGD', 'MYR', 'INR', 'JPY'];
</script>

<template>
    <Head title="Organization Settings" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <div class="flex items-center justify-between gap-4">
            <Heading title="Organization Settings" description="Manage your organization profile and preferences." />
            <Button v-if="canEdit" :disabled="form.processing" @click="submit">
                {{ form.processing ? 'Saving…' : 'Save Changes' }}
            </Button>
        </div>

        <!-- Organization Profile -->
        <Card>
            <CardHeader>
                <CardTitle>Organization Profile</CardTitle>
                <CardDescription>Update your organization's public information.</CardDescription>
            </CardHeader>
            <CardContent class="space-y-6">
                <!-- Logo -->
                <div class="flex items-center gap-6">
                    <div class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border bg-muted">
                        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-cover" />
                        <span v-else class="text-2xl font-bold text-muted-foreground">
                            {{ organization.company_name?.charAt(0)?.toUpperCase() ?? '?' }}
                        </span>
                    </div>
                    <div class="flex flex-col gap-2">
                        <p class="text-sm font-medium">Organization Logo</p>
                        <p class="text-xs text-muted-foreground">JPG, PNG, WEBP or SVG. Max 2MB.</p>
                        <div class="flex gap-2">
                            <Button v-if="canEdit" variant="outline" size="sm" @click="logoInputRef?.click()">
                                Choose File
                            </Button>
                            <Button v-if="canEdit && logoForm.logo" size="sm" :disabled="logoForm.processing" @click="uploadLogo">
                                {{ logoForm.processing ? 'Uploading…' : 'Upload' }}
                            </Button>
                        </div>
                        <input ref="logoInputRef" type="file" accept="image/*" class="hidden" @change="onLogoChange" />
                        <InputError :message="logoForm.errors.logo" />
                    </div>
                </div>

                <!-- Form fields -->
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-1.5">
                        <Label for="company_name">Company Name <span class="text-destructive">*</span></Label>
                        <Input id="company_name" v-model="form.company_name" :disabled="!canEdit" placeholder="Acme Property Management" />
                        <InputError :message="form.errors.company_name" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="company_email">Business Email <span class="text-destructive">*</span></Label>
                        <Input id="company_email" v-model="form.company_email" type="email" :disabled="!canEdit" placeholder="hello@yourcompany.com" />
                        <InputError :message="form.errors.company_email" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="company_phone">Contact Number</Label>
                        <Input id="company_phone" v-model="form.company_phone" :disabled="!canEdit" placeholder="+63 912 345 6789" />
                        <InputError :message="form.errors.company_phone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="tax_id">Tax ID / VAT Number</Label>
                        <Input id="tax_id" v-model="form.tax_id" :disabled="!canEdit" placeholder="000-000-000-000" />
                        <InputError :message="form.errors.tax_id" />
                    </div>

                    <div class="grid gap-1.5 sm:col-span-2">
                        <Label for="address">Business Address</Label>
                        <Input id="address" v-model="form.address" :disabled="!canEdit" placeholder="123 Main St, City, Country" />
                        <InputError :message="form.errors.address" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="timezone">Timezone <span class="text-destructive">*</span></Label>
                        <Select v-model="form.timezone" :disabled="!canEdit">
                            <SelectTrigger id="timezone">
                                <SelectValue placeholder="Select timezone" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="tz in commonTimezones" :key="tz" :value="tz">{{ tz }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.timezone" />
                    </div>

                    <div class="grid gap-1.5">
                        <Label for="currency">Currency <span class="text-destructive">*</span></Label>
                        <Select v-model="form.currency" :disabled="!canEdit">
                            <SelectTrigger id="currency">
                                <SelectValue placeholder="Select currency" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="c in currencies" :key="c" :value="c">{{ c }}</SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="form.errors.currency" />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Subscription -->
        <Card v-if="subscription">
            <CardHeader>
                <CardTitle>Subscription</CardTitle>
                <CardDescription>Your current plan and billing details.</CardDescription>
            </CardHeader>
            <CardContent>
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div class="space-y-3">
                        <div class="flex items-center gap-3">
                            <p class="text-xl font-bold">{{ subscription.plan_name }}</p>
                            <Badge :variant="statusVariant[subscription.status] ?? 'secondary'">
                                {{ subscriptionStatusLabel[subscription.status] ?? subscription.status }}
                            </Badge>
                        </div>
                        <div class="grid grid-cols-1 gap-1.5 text-sm text-muted-foreground sm:grid-cols-2">
                            <span>Billing cycle: <strong class="text-foreground">{{ subscription.billing_cycle === 'annual' ? 'Yearly' : 'Monthly' }}</strong></span>
                            <span>Amount: <strong class="text-foreground">${{ subscription.price }}/{{ subscription.billing_cycle === 'annual' ? 'yr' : 'mo' }}</strong></span>
                            <span v-if="subscription.trial_ends_at">
                                Trial ends: <strong class="text-foreground">{{ formatDate(subscription.trial_ends_at) }}</strong>
                            </span>
                            <span v-if="subscription.period_end">
                                Next billing: <strong class="text-foreground">{{ formatDate(subscription.period_end) }}</strong>
                            </span>
                        </div>
                    </div>
                    <div v-if="can('organization.manage_billing')" class="flex gap-2 shrink-0">
                        <Button variant="outline" size="sm">Downgrade</Button>
                        <Button size="sm">Upgrade Plan</Button>
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- No subscription -->
        <Card v-else>
            <CardContent class="py-8 text-center text-muted-foreground">
                <p>No active subscription found.</p>
            </CardContent>
        </Card>
    </div>
</template>
