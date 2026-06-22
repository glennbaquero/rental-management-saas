<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    status: string;
    label: string;
}>();

const classes = computed(() => {
    switch (props.status) {
        case 'completed': return 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'pending':   return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400';
        case 'failed':    return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400';
        case 'refunded':  return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400';
        default:          return 'bg-slate-100 text-slate-600 border-slate-200';
    }
});
</script>

<template>
    <span :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', classes]">
        {{ label }}
    </span>
</template>
