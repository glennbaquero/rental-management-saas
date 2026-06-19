<script setup lang="ts">
import type { PropertyStatus } from '@/types/property';

const props = defineProps<{
    status: PropertyStatus | null;
    label: string | null;
}>();

const config: Record<string, string> = {
    active:            'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    under_maintenance: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    inactive:          'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};
</script>

<template>
    <span
        v-if="status"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config[status] ?? config['inactive']"
    >
        {{ label ?? status }}
    </span>
</template>
