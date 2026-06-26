<script setup lang="ts">
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { RefreshCw, Search } from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

interface WebhookEvent {
    id: string;
    stripe_event_id: string;
    event_type: string;
    tenant_id: string | null;
    tenant_name: string | null;
    status: string;
    status_label: string;
    status_color: 'yellow' | 'blue' | 'green' | 'red';
    error_message: string | null;
    attempts: number;
    processed_at: string | null;
    created_at: string;
    payload: Record<string, unknown>;
}

interface PaginatedEvents {
    data: WebhookEvent[];
    links: { url: string | null; label: string; active: boolean }[];
    from: number;
    to: number;
    total: number;
    last_page: number;
}

interface StatusOption {
    value: string;
    label: string;
}

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Webhook Monitor', href: '/stripe/webhooks' },
        ],
    },
});

const props = defineProps<{
    events: PaginatedEvents;
    filters: { status?: string; event_type?: string; date?: string };
    statuses: StatusOption[];
}>();

const statusFilter    = ref(props.filters.status ?? 'all');
const eventTypeFilter = ref(props.filters.event_type ?? '');

const payloadDialogOpen = ref(false);
const selectedPayload   = ref<Record<string, unknown> | null>(null);
const selectedEventId   = ref('');

let filterTimer: ReturnType<typeof setTimeout>;

function applyFilters() {
    router.get(
        '/stripe/webhooks',
        {
            status:     statusFilter.value === 'all' ? undefined : statusFilter.value,
            event_type: eventTypeFilter.value || undefined,
        },
        { preserveState: true, replace: true },
    );
}

watch(statusFilter, applyFilters);
watch(eventTypeFilter, () => {
    clearTimeout(filterTimer);
    filterTimer = setTimeout(applyFilters, 400);
});

function viewPayload(event: WebhookEvent) {
    selectedPayload.value  = event.payload;
    selectedEventId.value  = event.stripe_event_id;
    payloadDialogOpen.value = true;
}

function retryEvent(eventId: string) {
    router.post(`/stripe/webhooks/${eventId}/retry`);
}

function formatDate(iso: string | null): string {
    if (!iso) return '—';
    return new Intl.DateTimeFormat('en-US', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(iso));
}

const badgeVariantMap: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    pending:    'outline',
    processing: 'secondary',
    processed:  'default',
    failed:     'destructive',
};
</script>

<template>
    <Head title="Webhook Monitor" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <Heading
            title="Stripe Webhook Monitor"
            description="Track and manage incoming Stripe webhook events."
        />

        <!-- Filters -->
        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-64">
                <Search class="absolute left-2.5 top-2.5 h-4 w-4 text-muted-foreground" />
                <Input
                    v-model="eventTypeFilter"
                    placeholder="Filter by event type…"
                    class="pl-8"
                />
            </div>

            <Select v-model="statusFilter">
                <SelectTrigger class="w-40">
                    <SelectValue placeholder="All statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                        {{ s.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
        </div>

        <!-- Table -->
        <Card>
            <CardHeader>
                <CardTitle class="text-base">
                    Webhook Events
                    <span class="ml-2 text-sm font-normal text-muted-foreground">
                        ({{ events.total }} total)
                    </span>
                </CardTitle>
            </CardHeader>
            <CardContent class="p-0">
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Stripe Event ID</TableHead>
                            <TableHead>Type</TableHead>
                            <TableHead>Organization</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead class="text-center">Attempts</TableHead>
                            <TableHead>Processed At</TableHead>
                            <TableHead>Received At</TableHead>
                            <TableHead class="text-right">Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        <TableRow v-if="events.data.length === 0">
                            <TableCell colspan="8" class="py-12 text-center text-muted-foreground">
                                No webhook events found.
                            </TableCell>
                        </TableRow>
                        <TableRow v-for="event in events.data" :key="event.id">
                            <TableCell class="font-mono text-xs">
                                {{ event.stripe_event_id.slice(0, 20) }}…
                            </TableCell>
                            <TableCell class="font-mono text-xs text-muted-foreground">
                                {{ event.event_type }}
                            </TableCell>
                            <TableCell class="text-sm">
                                {{ event.tenant_name ?? event.tenant_id ?? '—' }}
                            </TableCell>
                            <TableCell>
                                <Badge :variant="badgeVariantMap[event.status] ?? 'outline'">
                                    {{ event.status_label }}
                                </Badge>
                            </TableCell>
                            <TableCell class="text-center text-sm">
                                {{ event.attempts }}
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDate(event.processed_at) }}
                            </TableCell>
                            <TableCell class="text-sm text-muted-foreground">
                                {{ formatDate(event.created_at) }}
                            </TableCell>
                            <TableCell class="text-right">
                                <div class="flex justify-end gap-1">
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        @click="viewPayload(event)"
                                    >
                                        Payload
                                    </Button>
                                    <Button
                                        v-if="event.status === 'failed' || event.status === 'pending'"
                                        variant="outline"
                                        size="sm"
                                        @click="retryEvent(event.id)"
                                    >
                                        <RefreshCw class="mr-1 h-3.5 w-3.5" />
                                        Retry
                                    </Button>
                                </div>
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>

                <!-- Pagination -->
                <div v-if="events.last_page > 1" class="flex items-center justify-between border-t px-4 py-3">
                    <p class="text-sm text-muted-foreground">
                        Showing {{ events.from }}–{{ events.to }} of {{ events.total }}
                    </p>
                    <div class="flex gap-1">
                        <Button
                            v-for="link in events.links"
                            :key="link.label"
                            variant="outline"
                            size="sm"
                            :disabled="!link.url"
                            :class="{ 'bg-primary text-primary-foreground': link.active }"
                            v-html="link.label"
                            @click="link.url && router.visit(link.url)"
                        />
                    </div>
                </div>
            </CardContent>
        </Card>

        <!-- Error message for failed events -->
        <Card v-if="events.data.some(e => e.status === 'failed' && e.error_message)">
            <CardHeader>
                <CardTitle class="text-base text-destructive">Recent Errors</CardTitle>
            </CardHeader>
            <CardContent class="flex flex-col gap-3">
                <div
                    v-for="event in events.data.filter(e => e.status === 'failed' && e.error_message)"
                    :key="event.id"
                    class="rounded-md border border-destructive/30 bg-destructive/5 p-3"
                >
                    <p class="font-mono text-xs text-muted-foreground">{{ event.stripe_event_id }}</p>
                    <p class="mt-1 text-sm text-destructive">{{ event.error_message }}</p>
                </div>
            </CardContent>
        </Card>
    </div>

    <!-- Payload Dialog -->
    <Dialog v-model:open="payloadDialogOpen">
        <DialogContent class="max-h-[80vh] max-w-2xl overflow-y-auto">
            <DialogHeader>
                <DialogTitle class="font-mono text-sm">{{ selectedEventId }}</DialogTitle>
            </DialogHeader>
            <pre
                v-if="selectedPayload"
                class="overflow-x-auto rounded-md bg-muted p-4 text-xs"
            >{{ JSON.stringify(selectedPayload, null, 2) }}</pre>
        </DialogContent>
    </Dialog>
</template>
