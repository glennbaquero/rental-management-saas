<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{
    startDate: string | null | undefined;
    endDate: string | null | undefined;
}>();

const label = computed(() => {
    if (!props.startDate || !props.endDate) return null;
    const start  = new Date(props.startDate);
    const end    = new Date(props.endDate);
    if (isNaN(start.getTime()) || isNaN(end.getTime())) return null;
    const months = Math.round((end.getTime() - start.getTime()) / (1000 * 60 * 60 * 24 * 30.44));
    if (months <= 0) return null;
    return `${months} Month${months !== 1 ? 's' : ''}`;
});
</script>

<template>
    <span
        v-if="label"
        class="inline-flex items-center rounded-full bg-secondary px-2.5 py-0.5 text-xs font-medium text-secondary-foreground"
    >
        {{ label }}
    </span>
</template>
