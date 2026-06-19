<script setup lang="ts">
defineProps<{ active: boolean }>();
</script>

<template>
    <span
        :class="[
            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-medium',
            active
                ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                : 'bg-muted text-muted-foreground',
        ]"
    >
        <span :class="['h-1.5 w-1.5 rounded-full', active ? 'bg-green-500' : 'bg-muted-foreground']" />
        {{ active ? 'Active' : 'Inactive' }}
    </span>
</template>
