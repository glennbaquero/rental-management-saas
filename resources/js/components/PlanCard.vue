<script setup lang="ts">
import { Check } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import type { SubscriptionPlan } from '@/types';

const props = defineProps<{
    plan: SubscriptionPlan;
    selected: boolean;
    popular?: boolean;
}>();

const emit = defineEmits<{
    select: [];
}>();

function formatLimit(value: number | null, unit: string): string {
    return value === null ? `Unlimited ${unit}` : `${value} ${unit}`;
}
</script>

<template>
    <button
        type="button"
        class="group relative w-full rounded-xl border p-4 text-left transition-all duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        :class="
            selected
                ? 'border-primary bg-primary/5 shadow-sm'
                : 'border-border bg-background hover:border-primary/40 hover:bg-muted/30'
        "
        @click="emit('select')"
    >
        <div
            v-if="popular"
            class="absolute -top-2.5 left-4"
        >
            <Badge class="text-[10px] px-2 py-0.5 h-auto">Most Popular</Badge>
        </div>

        <div class="flex items-start gap-3">
            <div
                class="mt-0.5 flex h-4 w-4 shrink-0 items-center justify-center rounded-full border-2 transition-colors"
                :class="
                    selected
                        ? 'border-primary bg-primary'
                        : 'border-muted-foreground/40 bg-background'
                "
            >
                <div v-if="selected" class="h-1.5 w-1.5 rounded-full bg-white" />
            </div>

            <div class="flex flex-1 items-start justify-between gap-4 min-w-0">
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="font-semibold text-sm text-foreground">{{ plan.name }}</span>
                    </div>
                    <p class="mt-0.5 text-xs text-muted-foreground leading-snug">
                        {{ formatLimit(plan.max_properties, 'properties') }}
                        · {{ formatLimit(plan.max_units, 'units') }}
                        · {{ formatLimit(plan.max_users, 'users') }}
                    </p>
                    <ul
                        v-if="selected && plan.features.length"
                        class="mt-3 space-y-1.5"
                    >
                        <li
                            v-for="feature in plan.features"
                            :key="feature"
                            class="flex items-center gap-2 text-xs text-foreground"
                        >
                            <Check class="h-3 w-3 shrink-0 text-primary" />
                            {{ feature }}
                        </li>
                    </ul>
                </div>

                <div class="shrink-0 text-right">
                    <div class="flex items-baseline gap-0.5">
                        <span class="text-lg font-bold text-foreground leading-none">
                            ${{ plan.price.toFixed(2) }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            /{{ plan.billing_cycle === 'monthly' ? 'mo' : 'yr' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </button>
</template>
