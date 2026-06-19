<script setup lang="ts">
import type { UnitStatus } from '@/types/property';

const props = defineProps<{
    status: UnitStatus | null;
    label: string | null;
}>();

const config: Record<string, string> = {
    available:   'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    occupied:    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
    reserved:    'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    maintenance: 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400',
    unavailable: 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400',
};
</script>

<template>
    <span
        v-if="status"
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="config[status] ?? config['unavailable']"
    >
        {{ label ?? status }}
    </span>
</template>
