<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string | null | undefined;
    label: string | null | undefined;
}>();

const classes = computed(() => {
    switch (props.status) {
        case 'draft':         return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
        case 'pending':       return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400';
        case 'active':        return 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400';
        case 'expiring_soon': return 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400';
        case 'expired':       return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        case 'renewed':       return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'terminated':    return 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
        case 'cancelled':     return 'bg-rose-100 text-rose-800 dark:bg-rose-900/30 dark:text-rose-400';
        default:              return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
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
