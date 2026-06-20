<script setup lang="ts">
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import type { RentalTenantStatus } from '@/types/tenant';

const props = defineProps<{
    status: RentalTenantStatus | null | undefined;
    label: string | null | undefined;
}>();

const variant = computed(() => {
    switch (props.status) {
        case 'active':      return 'default' as const;
        case 'prospect':    return 'secondary' as const;
        case 'moved_out':   return 'outline' as const;
        case 'blacklisted': return 'destructive' as const;
        default:            return 'secondary' as const;
    }
});

const colorClass = computed(() => {
    switch (props.status) {
        case 'active':      return 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400';
        case 'prospect':    return 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400';
        case 'moved_out':   return 'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400';
        case 'blacklisted': return 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400';
        default:            return '';
    }
});
</script>

<template>
    <span :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', colorClass]">
        {{ label ?? status ?? '—' }}
    </span>
</template>
