<script setup lang="ts">
import { computed } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Switch } from '@/components/ui/switch';
import InputError from '@/components/InputError.vue';
import type { BillingSettings, SelectOption } from '@/types/billing';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Billing', href: '/billing/dashboard' },
            { title: 'Settings', href: '/billing/settings' },
        ],
    },
});

const props = defineProps<{
    settings: BillingSettings;
    lateFeeTypes: SelectOption[];
    reminderChannels: SelectOption[];
}>();

const form = useForm({ ...props.settings });

const reminderDayOptions = [7, 3, 1, -1, -7];
const reminderDayLabels: Record<number, string> = {
    7: '7 days before',
    3: '3 days before',
    1: '1 day before',
    '-1': '1 day overdue',
    '-7': '7 days overdue',
};

function toggleReminderDay(day: number) {
    const days = [...(form.reminder_days_before ?? [])];
    const idx  = days.indexOf(day);
    if (idx >= 0) {
        days.splice(idx, 1);
    } else {
        days.push(day);
    }
    form.reminder_days_before = days;
}

function toggleChannel(channel: string) {
    const channels = [...(form.reminder_channels ?? [])];
    const idx      = channels.indexOf(channel);
    if (idx >= 0) {
        channels.splice(idx, 1);
    } else {
        channels.push(channel);
    }
    form.reminder_channels = channels;
}

function submit() {
    form.patch('/billing/settings');
}
</script>

<template>
    <Head title="Billing Settings" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading title="Billing Settings" description="Configure how invoices are generated and how reminders are sent." />

        <form class="grid grid-cols-1 gap-6 lg:grid-cols-3" @submit.prevent="submit">
            <div class="flex flex-col gap-6 lg:col-span-2">

                <!-- General -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">General</CardTitle>
                        <CardDescription>Currency, timezone, and invoice numbering.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Currency</Label>
                                <Input v-model="form.currency" placeholder="PHP" maxlength="3" />
                                <InputError :message="form.errors.currency" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Timezone</Label>
                                <Input v-model="form.timezone" placeholder="Asia/Manila" />
                                <InputError :message="form.errors.timezone" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-1.5">
                                <Label>Invoice Prefix</Label>
                                <Input v-model="form.invoice_prefix" placeholder="INV" maxlength="10" />
                                <InputError :message="form.errors.invoice_prefix" />
                            </div>
                            <div class="space-y-1.5">
                                <Label>Invoice Number Format</Label>
                                <Input v-model="form.invoice_number_format" placeholder="{PREFIX}-{YEAR}-{SEQ5}" />
                                <p class="text-xs text-muted-foreground">
                                    Use {PREFIX}, {YEAR}, {SEQ5} as placeholders.
                                </p>
                                <InputError :message="form.errors.invoice_number_format" />
                            </div>
                        </div>

                        <div class="space-y-1.5">
                            <Label>Grace Period (Days)</Label>
                            <Input v-model.number="form.grace_period_days" type="number" min="0" max="30" class="w-32" />
                            <p class="text-xs text-muted-foreground">Days after due date before marking overdue.</p>
                            <InputError :message="form.errors.grace_period_days" />
                        </div>
                    </CardContent>
                </Card>

                <!-- Late Fees -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Late Fees</CardTitle>
                        <CardDescription>Automatically apply fees to overdue invoices.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <Label>Enable Late Fees</Label>
                                <p class="text-xs text-muted-foreground">Automatically charge late fees on overdue invoices.</p>
                            </div>
                            <Switch v-model="form.late_fee_enabled" />
                        </div>

                        <template v-if="form.late_fee_enabled">
                            <Separator />
                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>Late Fee Type</Label>
                                    <Select v-model="form.late_fee_type">
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem v-for="t in lateFeeTypes" :key="t.value" :value="t.value">
                                                {{ t.label }}
                                            </SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div class="space-y-1.5">
                                    <Label v-if="form.late_fee_type === 'fixed'">Fixed Amount</Label>
                                    <Label v-else>Percentage (%)</Label>
                                    <Input
                                        v-if="form.late_fee_type === 'fixed'"
                                        v-model.number="form.late_fee_amount"
                                        type="number"
                                        min="0"
                                        step="0.01"
                                    />
                                    <Input
                                        v-else
                                        v-model.number="form.late_fee_percentage"
                                        type="number"
                                        min="0"
                                        max="100"
                                        step="0.01"
                                    />
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div class="space-y-1.5">
                                    <Label>Apply After (Days Overdue)</Label>
                                    <Input v-model.number="form.apply_late_fee_after_days" type="number" min="1" max="90" />
                                </div>
                                <div class="flex items-center justify-between pt-6">
                                    <Label>Monthly Compounding</Label>
                                    <Switch v-model="form.compound_monthly" />
                                </div>
                            </div>
                        </template>
                    </CardContent>
                </Card>

                <!-- Automation & Reminders -->
                <Card>
                    <CardHeader>
                        <CardTitle class="text-base">Automation & Reminders</CardTitle>
                        <CardDescription>Configure automated invoice generation and reminders.</CardDescription>
                    </CardHeader>
                    <CardContent class="space-y-5">
                        <div class="flex items-center justify-between">
                            <div>
                                <Label>Auto-Generate Invoices</Label>
                                <p class="text-xs text-muted-foreground">Automatically generate invoices from active leases.</p>
                            </div>
                            <Switch v-model="form.auto_generate_invoices" />
                        </div>

                        <Separator />

                        <div class="flex items-center justify-between">
                            <div>
                                <Label>Auto-Send Reminders</Label>
                                <p class="text-xs text-muted-foreground">Send automated payment reminders.</p>
                            </div>
                            <Switch v-model="form.auto_send_reminders" />
                        </div>

                        <template v-if="form.auto_send_reminders">
                            <Separator />
                            <div class="space-y-2">
                                <Label>Send Reminders</Label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="day in reminderDayOptions"
                                        :key="day"
                                        type="button"
                                        :class="[
                                            'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                            form.reminder_days_before?.includes(day)
                                                ? 'bg-primary text-primary-foreground border-primary'
                                                : 'border-border text-muted-foreground hover:border-primary/50',
                                        ]"
                                        @click="toggleReminderDay(day)"
                                    >
                                        {{ reminderDayLabels[day] }}
                                    </button>
                                </div>
                            </div>

                            <div class="space-y-2">
                                <Label>Reminder Channels</Label>
                                <div class="flex flex-wrap gap-2">
                                    <button
                                        v-for="ch in reminderChannels"
                                        :key="ch.value"
                                        type="button"
                                        :class="[
                                            'rounded-full border px-3 py-1 text-xs font-medium transition-colors',
                                            form.reminder_channels?.includes(ch.value)
                                                ? 'bg-primary text-primary-foreground border-primary'
                                                : 'border-border text-muted-foreground hover:border-primary/50',
                                        ]"
                                        @click="toggleChannel(ch.value)"
                                    >
                                        {{ ch.label }}
                                    </button>
                                </div>
                            </div>
                        </template>
                    </CardContent>
                </Card>
            </div>

            <!-- Sidebar -->
            <div class="flex flex-col gap-4">
                <Card class="sticky top-4">
                    <CardHeader><CardTitle class="text-base">Save Settings</CardTitle></CardHeader>
                    <CardContent class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            Changes apply to all new invoices and automated processes.
                        </p>
                        <Button type="submit" class="w-full" :disabled="form.processing">
                            {{ form.processing ? 'Saving…' : 'Save Settings' }}
                        </Button>
                    </CardContent>
                </Card>
            </div>
        </form>
    </div>
</template>
