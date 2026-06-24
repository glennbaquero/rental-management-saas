<script setup lang="ts">
import { AlertTriangle, Calendar, Clock, FileText } from '@lucide/vue';
import type { UpcomingEvent } from '@/types/dashboard';

defineProps<{
    events: UpcomingEvent[];
}>();

const typeConfig = {
    lease_expiry: {
        icon:  FileText,
        bg:    'border-l-amber-400',
        badge: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
        label: 'Lease Expiry',
    },
    invoice_overdue: {
        icon:  Clock,
        bg:    'border-l-red-400',
        badge: 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
        label: 'Overdue Invoice',
    },
    maintenance_emergency: {
        icon:  AlertTriangle,
        bg:    'border-l-rose-500',
        badge: 'bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400',
        label: 'Emergency',
    },
};

function getConfig(type: string) {
    return typeConfig[type as keyof typeof typeConfig] ?? {
        icon:  Calendar,
        bg:    'border-l-blue-400',
        badge: 'bg-blue-100 text-blue-700',
        label: 'Event',
    };
}

function formatDate(dateStr: string): string {
    const d = new Date(dateStr);
    return d.toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function daysUntil(dateStr: string): string {
    const diff = Math.ceil((new Date(dateStr).getTime() - Date.now()) / 86_400_000);
    if (diff < 0)  return `${Math.abs(diff)}d overdue`;
    if (diff === 0) return 'Today';
    if (diff === 1) return 'Tomorrow';
    return `In ${diff} days`;
}
</script>

<template>
    <div v-if="events.length === 0" class="py-10 text-center text-sm text-muted-foreground">
        <Calendar class="mx-auto mb-2 h-8 w-8 opacity-30" />
        No upcoming events
    </div>

    <ul v-else class="space-y-2.5">
        <li
            v-for="(event, i) in events"
            :key="i"
            class="flex items-start gap-3 rounded-lg border-l-4 bg-muted/40 p-3 text-sm"
            :class="getConfig(event.type).bg"
        >
            <div class="mt-0.5 shrink-0">
                <component :is="getConfig(event.type).icon" class="h-4 w-4 text-muted-foreground" />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-start gap-1.5">
                    <span
                        class="inline-flex items-center rounded-full px-1.5 py-0.5 text-[10px] font-medium"
                        :class="getConfig(event.type).badge"
                    >
                        {{ getConfig(event.type).label }}
                    </span>
                </div>
                <p class="mt-0.5 font-medium leading-snug">{{ event.title }}</p>
                <p class="text-xs text-muted-foreground">{{ event.description }}</p>
            </div>

            <div class="shrink-0 text-right">
                <div class="text-[11px] font-semibold" :class="event.priority === 'emergency' ? 'text-rose-600' : 'text-muted-foreground'">
                    {{ daysUntil(event.due_at) }}
                </div>
                <div class="text-[10px] text-muted-foreground">{{ formatDate(event.due_at) }}</div>
            </div>
        </li>
    </ul>
</template>
