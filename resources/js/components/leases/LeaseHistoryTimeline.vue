<script setup lang="ts">
import type { LeaseHistory } from '@/types/lease';

defineProps<{
    histories: LeaseHistory[];
    getIcon: (eventType: string) => string;
}>();

function formatRelative(dateStr: string): string {
    const date = new Date(dateStr);
    const diff  = Date.now() - date.getTime();
    const mins  = Math.floor(diff / 60000);
    if (mins < 1)   return 'just now';
    if (mins < 60)  return `${mins}m ago`;
    const hrs = Math.floor(mins / 60);
    if (hrs < 24)   return `${hrs}h ago`;
    const days = Math.floor(hrs / 24);
    if (days < 30)  return `${days}d ago`;
    return date.toLocaleDateString('en-PH', { year: 'numeric', month: 'short', day: 'numeric' });
}

function formatFull(dateStr: string): string {
    return new Date(dateStr).toLocaleString('en-PH', {
        year: 'numeric', month: 'short', day: 'numeric',
        hour: '2-digit', minute: '2-digit',
    });
}
</script>

<template>
    <div v-if="histories.length" class="relative flex flex-col gap-0">
        <div
            v-for="(event, index) in histories"
            :key="event.id"
            class="relative flex gap-4 pb-6"
        >
            <!-- Connector line -->
            <div
                v-if="index < histories.length - 1"
                class="absolute left-5 top-10 h-full w-px bg-border"
            />

            <!-- Icon -->
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border bg-background text-base">
                {{ getIcon(event.event_type) }}
            </div>

            <!-- Content -->
            <div class="flex-1 pt-1">
                <p class="text-sm font-medium">{{ event.description }}</p>
                <p class="mt-0.5 text-xs text-muted-foreground" :title="formatFull(event.occurred_at)">
                    {{ formatRelative(event.occurred_at) }}
                </p>
            </div>
        </div>
    </div>

    <div v-else class="flex flex-col items-center justify-center rounded-lg border border-dashed py-12 text-center">
        <p class="text-sm font-medium">No history yet</p>
        <p class="text-xs text-muted-foreground">Events will appear here as they occur.</p>
    </div>
</template>
