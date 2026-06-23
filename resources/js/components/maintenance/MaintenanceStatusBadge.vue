<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string | null | undefined;
    label: string | null | undefined;
}>();

const classes = computed(() => {
    switch (props.status) {
        case 'open':              return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'assigned':          return 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400';
        case 'in_progress':       return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'waiting_for_parts': return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
        case 'on_hold':           return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
        case 'resolved':          return 'bg-teal-100 text-teal-700 dark:bg-teal-900/30 dark:text-teal-400';
        case 'completed':         return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'cancelled':         return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default:                  return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    }
});
</script>

<template>
    <span
        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="classes"
    >
        {{ label ?? status }}
    </span>
</template>
