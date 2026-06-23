<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    priority: string | null | undefined;
    label: string | null | undefined;
    icon?: string | null;
}>();

const classes = computed(() => {
    switch (props.priority) {
        case 'low':       return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'medium':    return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'high':      return 'bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-400';
        case 'urgent':    return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'emergency': return 'bg-rose-100 text-rose-900 dark:bg-rose-900/40 dark:text-rose-300 font-semibold';
        default:          return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
    }
});
</script>

<template>
    <span
        class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-xs font-medium"
        :class="classes"
    >
        <span v-if="icon">{{ icon }}</span>
        {{ label ?? priority }}
    </span>
</template>
