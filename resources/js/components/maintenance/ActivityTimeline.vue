<script setup lang="ts">
import type { MaintenanceHistory } from '@/types/maintenance';

defineProps<{
    histories: MaintenanceHistory[];
}>();

function formatDateTime(dt: string): string {
    return new Date(dt).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}

function getEventIcon(eventType: string): string {
    const icons: Record<string, string> = {
        created: '🎫',
        assigned: '👤',
        status_changed: '🔄',
        comment_added: '💬',
        resolved: '✅',
        completed: '🏁',
        rated: '⭐',
        auto_escalated: '⚠️',
        cost_added: '💰',
        attachment_added: '📎',
    };
    return icons[eventType] ?? '•';
}

function getEventColor(eventType: string): string {
    const colors: Record<string, string> = {
        created: 'bg-blue-500',
        assigned: 'bg-purple-500',
        status_changed: 'bg-yellow-500',
        comment_added: 'bg-gray-400',
        resolved: 'bg-teal-500',
        completed: 'bg-green-500',
        rated: 'bg-amber-500',
        auto_escalated: 'bg-red-500',
    };
    return colors[eventType] ?? 'bg-gray-400';
}
</script>

<template>
    <div v-if="histories.length === 0" class="py-8 text-center text-sm text-muted-foreground">
        No activity recorded yet.
    </div>

    <ol v-else class="relative border-l border-border ml-3">
        <li
            v-for="item in histories"
            :key="item.id"
            class="mb-6 ml-6"
        >
            <span
                class="absolute -left-3 flex h-6 w-6 items-center justify-center rounded-full ring-4 ring-background text-xs"
                :class="getEventColor(item.event_type)"
            >
                {{ getEventIcon(item.event_type) }}
            </span>

            <div class="rounded-lg border bg-card p-3 shadow-sm">
                <div class="mb-1 flex items-center justify-between gap-2">
                    <p class="text-sm font-medium text-foreground">{{ item.description }}</p>
                    <time class="shrink-0 text-xs text-muted-foreground">
                        {{ formatDateTime(item.occurred_at) }}
                    </time>
                </div>
                <p class="text-xs text-muted-foreground">by {{ item.created_by }}</p>
            </div>
        </li>
    </ol>
</template>
