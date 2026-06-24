<script setup lang="ts">
import { computed } from 'vue';
import { CreditCard, FileText, Wrench } from '@lucide/vue';
import type { ActivityItem } from '@/types/dashboard';

defineProps<{
    activities: ActivityItem[];
}>();

const iconMap = {
    lease:       { icon: FileText,   bg: 'bg-blue-100 dark:bg-blue-900/30',   color: 'text-blue-600 dark:text-blue-400' },
    payment:     { icon: CreditCard, bg: 'bg-emerald-100 dark:bg-emerald-900/30', color: 'text-emerald-600 dark:text-emerald-400' },
    maintenance: { icon: Wrench,     bg: 'bg-amber-100 dark:bg-amber-900/30', color: 'text-amber-600 dark:text-amber-400' },
};

function getIcon(type: string) {
    return iconMap[type as keyof typeof iconMap] ?? iconMap.maintenance;
}

function relativeTime(iso: string): string {
    const diff = Date.now() - new Date(iso).getTime();
    const mins  = Math.floor(diff / 60_000);
    const hours = Math.floor(mins / 60);
    const days  = Math.floor(hours / 24);

    if (days > 0)  return `${days}d ago`;
    if (hours > 0) return `${hours}h ago`;
    if (mins > 0)  return `${mins}m ago`;
    return 'just now';
}

function formatTime(iso: string): string {
    return new Date(iso).toLocaleTimeString('en-PH', { hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <div v-if="activities.length === 0" class="py-10 text-center text-sm text-muted-foreground">
        No recent activity
    </div>

    <ol v-else class="relative space-y-0">
        <li
            v-for="(activity, i) in activities"
            :key="activity.id"
            class="relative flex gap-4 pb-6 last:pb-0"
        >
            <!-- Vertical line -->
            <div
                v-if="i < activities.length - 1"
                class="absolute left-4 top-8 h-full w-px bg-border"
            />

            <!-- Icon -->
            <div
                class="relative z-10 flex h-8 w-8 shrink-0 items-center justify-center rounded-full"
                :class="getIcon(activity.type).bg"
            >
                <component :is="getIcon(activity.type).icon" class="h-3.5 w-3.5" :class="getIcon(activity.type).color" />
            </div>

            <!-- Content -->
            <div class="min-w-0 flex-1 pt-0.5">
                <div class="flex items-start justify-between gap-2">
                    <p class="text-sm font-medium leading-tight">{{ activity.title }}</p>
                    <time class="shrink-0 text-xs text-muted-foreground">
                        {{ relativeTime(activity.occurred_at) }}
                    </time>
                </div>
                <p class="mt-0.5 text-xs text-muted-foreground">{{ activity.description }}</p>

                <div v-if="Object.keys(activity.meta).length" class="mt-1.5 flex flex-wrap gap-x-3 gap-y-0.5">
                    <span
                        v-for="(val, key) in activity.meta"
                        :key="key"
                        class="text-[11px] text-muted-foreground"
                    >
                        <span class="font-medium capitalize">{{ key }}:</span> {{ val }}
                    </span>
                </div>
            </div>
        </li>
    </ol>
</template>
