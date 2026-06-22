<script setup lang="ts">
import { computed } from 'vue';
import { Banknote, Building2, CreditCard, Phone, Smartphone } from '@lucide/vue';

const props = defineProps<{
    method: string;
    label: string;
}>();

const icon = computed(() => {
    switch (props.method) {
        case 'cash':          return Banknote;
        case 'bank_transfer': return Building2;
        case 'gcash':         return Smartphone;
        case 'paymaya':       return Phone;
        case 'stripe':        return CreditCard;
        case 'check':         return Banknote;
        default:              return CreditCard;
    }
});

const classes = computed(() => {
    switch (props.method) {
        case 'gcash':         return 'bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400';
        case 'paymaya':       return 'bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400';
        case 'stripe':        return 'bg-violet-50 text-violet-700 dark:bg-violet-900/20 dark:text-violet-400';
        case 'bank_transfer': return 'bg-sky-50 text-sky-700 dark:bg-sky-900/20 dark:text-sky-400';
        case 'cash':          return 'bg-slate-50 text-slate-700 dark:bg-slate-800 dark:text-slate-400';
        default:              return 'bg-slate-50 text-slate-600 dark:bg-slate-800 dark:text-slate-400';
    }
});
</script>

<template>
    <span :class="['inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium', classes]">
        <component :is="icon" class="h-3 w-3" />
        {{ label }}
    </span>
</template>
