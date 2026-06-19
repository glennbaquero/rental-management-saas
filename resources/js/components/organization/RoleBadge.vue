<script setup lang="ts">
import type { RoleName } from '@/types/organization';
import { Badge } from '@/components/ui/badge';

const props = defineProps<{
    role?: RoleName | string | null;
    label?: string | null;
}>();

const variantMap: Record<string, string> = {
    owner:            'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    property_manager: 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    staff:            'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
    accountant:       'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
};

const displayClass = props.role ? (variantMap[props.role] ?? 'bg-muted text-muted-foreground') : 'bg-muted text-muted-foreground';
</script>

<template>
    <span
        v-if="role"
        :class="['inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', displayClass]"
    >
        {{ label ?? role }}
    </span>
    <span v-else class="text-xs text-muted-foreground">—</span>
</template>
