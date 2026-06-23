<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft, Pencil, UserPlus, XCircle, MessageSquare,
    Paperclip, DollarSign, History, Star,
} from '@lucide/vue';
import Heading from '@/components/Heading.vue';
import MaintenanceStatusBadge from '@/components/maintenance/MaintenanceStatusBadge.vue';
import MaintenancePriorityBadge from '@/components/maintenance/MaintenancePriorityBadge.vue';
import ActivityTimeline from '@/components/maintenance/ActivityTimeline.vue';
import AssignStaffModal from '@/components/maintenance/AssignStaffModal.vue';
import AddCostModal from '@/components/maintenance/AddCostModal.vue';
import SatisfactionRatingModal from '@/components/maintenance/SatisfactionRatingModal.vue';
import { Avatar, AvatarFallback } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import { usePermission } from '@/composables/usePermission';
import type {
    MaintenanceTicketDetail,
    PriorityOption,
    SelectOption,
} from '@/types/maintenance';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Maintenance Requests', href: '/maintenance' },
            { title: 'Ticket Details', href: '#' },
        ],
    },
});

const props = defineProps<{
    ticket: MaintenanceTicketDetail;
    statuses: SelectOption[];
    priorities: PriorityOption[];
    staff: SelectOption[];
}>();

const { can } = usePermission();

const assignOpen = ref(false);
const costOpen   = ref(false);
const ratingOpen = ref(false);

const commentForm = useForm({
    body: '',
    comment_type: 'staff',
    is_pinned: false,
});

const uploadForm = useForm({
    files: [] as File[],
});

function submitComment() {
    commentForm.post(`/maintenance/${props.ticket.id}/comments`, {
        onSuccess: () => commentForm.reset(),
    });
}

function onFilesChange(e: Event) {
    const files = Array.from((e.target as HTMLInputElement).files ?? []);
    uploadForm.files = files;
}

function uploadAttachments() {
    uploadForm.post(`/maintenance/${props.ticket.id}/attachments`, {
        forceFormData: true,
        onSuccess: () => uploadForm.reset(),
    });
}

function deleteAttachment(attachmentId: string) {
    if (!confirm('Delete this attachment?')) return;
    router.delete(`/maintenance/${props.ticket.id}/attachments/${attachmentId}`);
}

function deleteComment(commentId: string) {
    if (!confirm('Delete this comment?')) return;
    router.delete(`/maintenance/${props.ticket.id}/comments/${commentId}`);
}

function deleteCost(costId: string) {
    if (!confirm('Delete this cost entry?')) return;
    router.delete(`/maintenance/${props.ticket.id}/costs/${costId}`);
}

function updateStatus(status: string) {
    router.put(`/maintenance/${props.ticket.id}`, {
        status,
        property_id: props.ticket.property?.id ?? '',
        unit_id: props.ticket.unit?.id ?? '',
        category: props.ticket.category,
        title: props.ticket.title,
        description: props.ticket.description,
        priority: props.ticket.priority,
    });
}

function getInitials(name: string): string {
    return name.split(' ').slice(0, 2).map(n => n[0]).join('').toUpperCase();
}

function formatCurrency(amount: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' }).format(amount);
}

function formatDate(dt: string | null | undefined): string {
    if (!dt) return '—';
    return new Date(dt).toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatDateTime(dt: string | null | undefined): string {
    if (!dt) return '—';
    return new Date(dt).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function getCommentTypeClass(type: string): string {
    return {
        internal: 'border-l-4 border-yellow-400 bg-yellow-50 dark:bg-yellow-900/10',
        tenant: 'border-l-4 border-blue-400 bg-blue-50 dark:bg-blue-900/10',
        staff: 'border-l-4 border-gray-300 bg-gray-50 dark:bg-gray-800/30',
    }[type] ?? '';
}

function getCostStatusClass(status: string): string {
    return {
        pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        approved: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        paid: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    }[status] ?? 'bg-gray-100 text-gray-600';
}
</script>

<template>
    <Head :title="`Ticket ${ticket.ticket_number}`" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <!-- Header -->
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <Button variant="ghost" size="icon" @click="router.visit('/maintenance')">
                    <ArrowLeft class="h-4 w-4" />
                </Button>
                <div>
                    <div class="flex flex-wrap items-center gap-2">
                        <h1 class="font-mono text-sm text-muted-foreground">{{ ticket.ticket_number }}</h1>
                        <MaintenancePriorityBadge
                            :priority="ticket.priority"
                            :label="ticket.priority_label"
                            :icon="ticket.priority_icon"
                        />
                        <MaintenanceStatusBadge :status="ticket.status" :label="ticket.status_label" />
                        <span
                            v-if="ticket.is_overdue"
                            class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-800 dark:bg-red-900/30 dark:text-red-300"
                        >
                            ⚠ Overdue
                        </span>
                    </div>
                    <h2 class="mt-1 text-xl font-semibold">{{ ticket.title }}</h2>
                </div>
            </div>

            <div class="flex flex-wrap gap-2">
                <Button
                    v-if="can('maintenance.manage') && !ticket.rating && (ticket.status === 'completed' || ticket.status === 'resolved')"
                    variant="outline"
                    size="sm"
                    @click="ratingOpen = true"
                >
                    <Star class="mr-1 h-4 w-4" />
                    Rate
                </Button>
                <Button
                    v-if="can('maintenance.manage')"
                    variant="outline"
                    size="sm"
                    @click="assignOpen = true"
                >
                    <UserPlus class="mr-1 h-4 w-4" />
                    Assign
                </Button>
                <Button
                    v-if="can('maintenance.edit')"
                    variant="outline"
                    size="sm"
                    as-child
                >
                    <Link :href="`/maintenance/${ticket.id}/edit`">
                        <Pencil class="mr-1 h-4 w-4" />
                        Edit
                    </Link>
                </Button>
                <Button
                    v-if="can('maintenance.edit') && ticket.status !== 'cancelled' && ticket.status !== 'completed'"
                    variant="destructive"
                    size="sm"
                    @click="updateStatus('cancelled')"
                >
                    <XCircle class="mr-1 h-4 w-4" />
                    Close
                </Button>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 xl:grid-cols-4">
            <!-- Main Content (Tabs) -->
            <div class="xl:col-span-3">
                <Tabs default-value="overview">
                    <TabsList class="mb-4">
                        <TabsTrigger value="overview">
                            Overview
                        </TabsTrigger>
                        <TabsTrigger value="comments">
                            Comments
                            <span v-if="ticket.comments.length" class="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs">
                                {{ ticket.comments.length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger value="attachments">
                            Attachments
                            <span v-if="ticket.attachments.length" class="ml-1.5 rounded-full bg-muted px-1.5 py-0.5 text-xs">
                                {{ ticket.attachments.length }}
                            </span>
                        </TabsTrigger>
                        <TabsTrigger value="costs">
                            Costs
                        </TabsTrigger>
                        <TabsTrigger value="history">
                            History
                        </TabsTrigger>
                    </TabsList>

                    <!-- Overview Tab -->
                    <TabsContent value="overview" class="space-y-4">
                        <Card>
                            <CardHeader>
                                <CardTitle class="text-sm">Issue Description</CardTitle>
                            </CardHeader>
                            <CardContent>
                                <p class="whitespace-pre-wrap text-sm text-foreground">{{ ticket.description }}</p>
                            </CardContent>
                        </Card>

                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <Card>
                                <CardHeader><CardTitle class="text-sm">Details</CardTitle></CardHeader>
                                <CardContent class="space-y-3 text-sm">
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Category</span>
                                        <span class="font-medium">{{ ticket.category_label }}</span>
                                    </div>
                                    <Separator />
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Date Submitted</span>
                                        <span>{{ formatDateTime(ticket.date_submitted) }}</span>
                                    </div>
                                    <Separator />
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Preferred Schedule</span>
                                        <span>{{ formatDateTime(ticket.preferred_schedule) }}</span>
                                    </div>
                                    <Separator />
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Resolved At</span>
                                        <span>{{ formatDateTime(ticket.resolved_at) }}</span>
                                    </div>
                                    <Separator />
                                    <div class="flex justify-between">
                                        <span class="text-muted-foreground">Completed At</span>
                                        <span>{{ formatDateTime(ticket.completed_at) }}</span>
                                    </div>
                                </CardContent>
                            </Card>

                            <Card v-if="ticket.notes">
                                <CardHeader><CardTitle class="text-sm">Notes</CardTitle></CardHeader>
                                <CardContent>
                                    <p class="whitespace-pre-wrap text-sm text-muted-foreground">{{ ticket.notes }}</p>
                                </CardContent>
                            </Card>
                        </div>

                        <!-- Rating -->
                        <Card v-if="ticket.rating">
                            <CardHeader><CardTitle class="text-sm">Tenant Rating</CardTitle></CardHeader>
                            <CardContent class="space-y-2">
                                <div class="flex items-center gap-1">
                                    <span
                                        v-for="i in 5"
                                        :key="i"
                                        class="text-2xl"
                                        :class="i <= ticket.rating.rating ? 'text-amber-400' : 'text-gray-300'"
                                    >★</span>
                                    <span class="ml-2 text-sm font-semibold">{{ ticket.rating.rating }}/5</span>
                                </div>
                                <p v-if="ticket.rating.feedback" class="text-sm text-muted-foreground">
                                    "{{ ticket.rating.feedback }}"
                                </p>
                                <p v-if="ticket.rating.would_recommend !== null" class="text-xs text-muted-foreground">
                                    Would recommend: <strong>{{ ticket.rating.would_recommend ? 'Yes' : 'No' }}</strong>
                                </p>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Comments Tab -->
                    <TabsContent value="comments" class="space-y-4">
                        <div v-if="ticket.comments.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                            No comments yet.
                        </div>

                        <div
                            v-for="comment in ticket.comments"
                            :key="comment.id"
                            class="rounded-lg p-4 text-sm"
                            :class="getCommentTypeClass(comment.comment_type)"
                        >
                            <div class="mb-2 flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <Avatar class="h-7 w-7">
                                        <AvatarFallback class="text-xs">
                                            {{ comment.user ? getInitials(comment.user.name) : '?' }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div>
                                        <span class="font-medium">{{ comment.user?.name ?? 'Unknown' }}</span>
                                        <span class="ml-2 text-xs text-muted-foreground">{{ comment.comment_type_label }}</span>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-muted-foreground">{{ formatDateTime(comment.created_at) }}</span>
                                    <Button
                                        v-if="can('maintenance.edit')"
                                        variant="ghost"
                                        size="sm"
                                        class="h-6 px-1 text-destructive"
                                        @click="deleteComment(comment.id)"
                                    >
                                        ×
                                    </Button>
                                </div>
                            </div>
                            <p class="whitespace-pre-wrap">{{ comment.body }}</p>

                            <!-- Comment attachments -->
                            <div v-if="comment.attachments.length" class="mt-3 flex flex-wrap gap-2">
                                <a
                                    v-for="att in comment.attachments"
                                    :key="att.id"
                                    :href="att.url"
                                    target="_blank"
                                    class="flex items-center gap-1 rounded bg-background px-2 py-1 text-xs text-primary hover:underline"
                                >
                                    📎 {{ att.name }}
                                </a>
                            </div>
                        </div>

                        <!-- Add Comment -->
                        <Card>
                            <CardHeader><CardTitle class="text-sm">Add Comment</CardTitle></CardHeader>
                            <CardContent>
                                <form class="space-y-3" @submit.prevent="submitComment">
                                    <div>
                                        <Label class="mb-1 block text-xs text-muted-foreground">Comment Type</Label>
                                        <Select v-model="commentForm.comment_type">
                                            <SelectTrigger class="w-[180px]">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="staff">Staff Comment</SelectItem>
                                                <SelectItem value="internal">Internal Note</SelectItem>
                                                <SelectItem value="tenant">Tenant Comment</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <Textarea
                                        v-model="commentForm.body"
                                        rows="3"
                                        placeholder="Write a comment..."
                                    />
                                    <Button type="submit" size="sm" :disabled="commentForm.processing || !commentForm.body">
                                        {{ commentForm.processing ? 'Posting…' : 'Post Comment' }}
                                    </Button>
                                </form>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Attachments Tab -->
                    <TabsContent value="attachments" class="space-y-4">
                        <Card>
                            <CardHeader class="flex flex-row items-center justify-between">
                                <CardTitle class="text-sm">Files</CardTitle>
                                <form class="flex items-center gap-2" @submit.prevent="uploadAttachments">
                                    <input
                                        type="file"
                                        multiple
                                        accept=".jpg,.jpeg,.png,.webp,.pdf,.mp4,.mov"
                                        class="text-xs"
                                        @change="onFilesChange"
                                    />
                                    <Button type="submit" size="sm" :disabled="uploadForm.processing || !uploadForm.files.length">
                                        {{ uploadForm.processing ? 'Uploading…' : 'Upload' }}
                                    </Button>
                                </form>
                            </CardHeader>
                            <CardContent>
                                <div v-if="ticket.attachments.length === 0" class="py-8 text-center text-sm text-muted-foreground">
                                    No attachments yet.
                                </div>

                                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                    <div
                                        v-for="att in ticket.attachments"
                                        :key="att.id"
                                        class="group relative rounded-lg border bg-muted/30 p-2"
                                    >
                                        <!-- Image Preview -->
                                        <a v-if="att.is_image" :href="att.url" target="_blank">
                                            <img :src="att.url" :alt="att.name" class="mb-2 h-24 w-full rounded object-cover" />
                                        </a>
                                        <!-- Video -->
                                        <div v-else-if="att.is_video" class="mb-2 flex h-24 items-center justify-center rounded bg-black/5">
                                            <span class="text-3xl">🎥</span>
                                        </div>
                                        <!-- PDF/Other -->
                                        <div v-else class="mb-2 flex h-24 items-center justify-center rounded bg-red-50">
                                            <span class="text-3xl">📄</span>
                                        </div>

                                        <p class="truncate text-xs font-medium">{{ att.name }}</p>
                                        <p class="text-[10px] text-muted-foreground">{{ att.file_size_formatted }}</p>

                                        <div class="mt-1 flex gap-1">
                                            <a :href="att.url" target="_blank" download class="text-xs text-primary hover:underline">
                                                Download
                                            </a>
                                            <button
                                                v-if="can('maintenance.edit')"
                                                class="text-xs text-destructive hover:underline"
                                                @click="deleteAttachment(att.id)"
                                            >
                                                Delete
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    </TabsContent>

                    <!-- Costs Tab -->
                    <TabsContent value="costs" class="space-y-4">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-muted-foreground">
                                Total: <strong class="text-foreground">{{ formatCurrency(ticket.total_cost) }}</strong>
                            </p>
                            <Button v-if="can('maintenance.manage')" size="sm" @click="costOpen = true">
                                + Add Cost
                            </Button>
                        </div>

                        <Card>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Added By</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead v-if="can('maintenance.manage')" />
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow v-if="ticket.costs.length === 0">
                                        <TableCell colspan="7" class="py-8 text-center text-muted-foreground">
                                            No cost entries yet.
                                        </TableCell>
                                    </TableRow>
                                    <TableRow v-for="cost in ticket.costs" :key="cost.id">
                                        <TableCell class="text-sm font-medium">{{ cost.cost_type_label }}</TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ cost.description }}</TableCell>
                                        <TableCell class="text-sm font-semibold">{{ formatCurrency(cost.amount) }}</TableCell>
                                        <TableCell>
                                            <span class="rounded-full px-2 py-0.5 text-xs font-medium" :class="getCostStatusClass(cost.status)">
                                                {{ cost.status_label }}
                                            </span>
                                        </TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ cost.added_by }}</TableCell>
                                        <TableCell class="text-sm text-muted-foreground">{{ formatDate(cost.created_at) }}</TableCell>
                                        <TableCell v-if="can('maintenance.manage')">
                                            <Button variant="ghost" size="sm" class="h-7 text-destructive" @click="deleteCost(cost.id)">
                                                Delete
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </Card>
                    </TabsContent>

                    <!-- History Tab -->
                    <TabsContent value="history">
                        <ActivityTimeline :histories="ticket.histories" />
                    </TabsContent>
                </Tabs>
            </div>

            <!-- Sidebar -->
            <div class="space-y-4">
                <!-- Status Control -->
                <Card v-if="can('maintenance.manage')">
                    <CardHeader><CardTitle class="text-sm">Update Status</CardTitle></CardHeader>
                    <CardContent class="space-y-2">
                        <Select :model-value="ticket.status" @update:model-value="updateStatus">
                            <SelectTrigger>
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem v-for="s in statuses" :key="s.value" :value="s.value">
                                    {{ s.label }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </CardContent>
                </Card>

                <!-- Tenant Info -->
                <Card v-if="ticket.rental_tenant">
                    <CardHeader><CardTitle class="text-sm">Tenant</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p class="font-semibold">{{ ticket.rental_tenant.name }}</p>
                        <p v-if="ticket.rental_tenant.email" class="text-muted-foreground">{{ ticket.rental_tenant.email }}</p>
                        <p v-if="ticket.rental_tenant.phone" class="text-muted-foreground">{{ ticket.rental_tenant.phone }}</p>
                    </CardContent>
                </Card>

                <!-- Location -->
                <Card>
                    <CardHeader><CardTitle class="text-sm">Location</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <p><span class="text-muted-foreground">Property:</span> {{ ticket.property?.name ?? '—' }}</p>
                        <p v-if="ticket.building"><span class="text-muted-foreground">Building:</span> {{ ticket.building.name }}</p>
                        <p><span class="text-muted-foreground">Unit:</span> {{ ticket.unit?.unit_number ?? '—' }}</p>
                    </CardContent>
                </Card>

                <!-- Assignments -->
                <Card>
                    <CardHeader class="flex flex-row items-center justify-between">
                        <CardTitle class="text-sm">Assigned Staff</CardTitle>
                        <Button v-if="can('maintenance.manage')" variant="ghost" size="sm" @click="assignOpen = true">
                            + Assign
                        </Button>
                    </CardHeader>
                    <CardContent>
                        <div v-if="ticket.assignments.length === 0" class="text-sm text-muted-foreground">
                            No staff assigned.
                        </div>
                        <div v-else class="space-y-3">
                            <div
                                v-for="a in ticket.assignments"
                                :key="a.id"
                                class="flex items-start gap-2"
                                :class="{ 'opacity-60': !a.is_primary }"
                            >
                                <Avatar class="h-8 w-8 shrink-0">
                                    <AvatarFallback class="text-xs">
                                        {{ a.user ? getInitials(a.user.name) : 'C' }}
                                    </AvatarFallback>
                                </Avatar>
                                <div class="text-sm">
                                    <p class="font-medium">{{ a.user?.name ?? a.contractor_name ?? '—' }}</p>
                                    <p class="text-xs text-muted-foreground">{{ a.assignee_type_label }}</p>
                                    <p v-if="a.estimated_completion" class="text-xs text-muted-foreground">
                                        Due: {{ formatDate(a.estimated_completion) }}
                                    </p>
                                    <span v-if="a.is_primary" class="text-[10px] font-semibold text-primary">Primary</span>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Cost Summary -->
                <Card>
                    <CardHeader><CardTitle class="text-sm">Cost Summary</CardTitle></CardHeader>
                    <CardContent class="space-y-2 text-sm">
                        <div
                            v-for="cost in ticket.costs"
                            :key="cost.id"
                            class="flex justify-between text-muted-foreground"
                        >
                            <span>{{ cost.cost_type_label }}</span>
                            <span>{{ formatCurrency(cost.amount) }}</span>
                        </div>
                        <Separator v-if="ticket.costs.length" />
                        <div class="flex justify-between font-semibold">
                            <span>Total</span>
                            <span>{{ formatCurrency(ticket.total_cost) }}</span>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </div>

    <AssignStaffModal
        :open="assignOpen"
        :ticket="ticket"
        :staff="staff"
        @update:open="assignOpen = $event"
    />

    <AddCostModal
        :open="costOpen"
        :ticket="ticket"
        @update:open="costOpen = $event"
    />

    <SatisfactionRatingModal
        :open="ratingOpen"
        :ticket="ticket"
        @update:open="ratingOpen = $event"
    />
</template>
