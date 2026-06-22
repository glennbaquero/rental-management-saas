<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    status: string;
    label: string;
}>();

const variant = computed(() => {
    switch (props.status) {
        case 'paid':     return 'success';
        case 'partial':  return 'warning';
        case 'overdue':  return 'destructive';
        case 'sent':     return 'default';
        case 'draft':    return 'secondary';
        case 'void':     return 'outline';
        default:         return 'secondary';
    }
});

const classes = computed(() => {
    switch (props.status) {
        case 'paid':    return 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'partial': return 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-900/30 dark:text-amber-400';
        case 'overdue': return 'bg-red-100 text-red-800 border-red-200 dark:bg-red-900/30 dark:text-red-400';
        case 'sent':    return 'bg-blue-100 text-blue-800 border-blue-200 dark:bg-blue-900/30 dark:text-blue-400';
        case 'draft':   return 'bg-slate-100 text-slate-600 border-slate-200 dark:bg-slate-800 dark:text-slate-400';
        case 'void':    return 'bg-transparent text-muted-foreground border-border';
        default:        return '';
    }
});
</script>

<template>
    <span
        :class="['inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-medium', classes]"
    >
        {{ label }}
    </span>
</template>
