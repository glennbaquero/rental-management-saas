<script setup lang="ts">
import { computed } from 'vue';
import { Building2 } from '@lucide/vue';
import type { RentalHistoryEntry } from '@/types/tenant';

const props = defineProps<{
    history: RentalHistoryEntry[];
}>();

type GroupedHistory = Record<string, RentalHistoryEntry[]>;

const grouped = computed<GroupedHistory>(() => {
    return props.history.reduce((acc: GroupedHistory, entry) => {
        const year = entry.start_date ? new Date(entry.start_date).getFullYear().toString() : 'Unknown';
        if (!acc[year]) acc[year] = [];
        acc[year].push(entry);
        return acc;
    }, {});
});

const years = computed(() => Object.keys(grouped.value).sort((a, b) => Number(b) - Number(a)));

function statusColor(status: string | null): string {
    switch (status) {
        case 'active':      return 'bg-emerald-100 text-emerald-800';
        case 'terminated':  return 'bg-red-100 text-red-800';
        case 'expired':     return 'bg-gray-100 text-gray-600';
        default:            return 'bg-gray-100 text-gray-600';
    }
}

function formatDate(value: string | null): string {
    if (!value) return '—';
    return new Date(value).toLocaleDateString('en-PH', { month: 'short', day: 'numeric', year: 'numeric' });
}

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP', maximumFractionDigits: 0 }).format(value);
}
</script>

<template>
    <div class="relative space-y-8">
        <!-- Vertical line -->
        <div class="absolute left-3 top-4 bottom-4 w-px bg-border" />

        <div v-for="year in years" :key="year">
            <!-- Year marker -->
            <div class="relative mb-4 flex items-center gap-4">
                <div class="relative z-10 flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground shadow">
                    {{ year.slice(-2) }}
                </div>
                <p class="text-sm font-semibold text-muted-foreground">{{ year }}</p>
            </div>

            <!-- Entries for this year -->
            <div class="ml-10 space-y-3">
                <div
                    v-for="entry in grouped[year]"
                    :key="entry.id"
                    class="rounded-xl border bg-card p-4 shadow-sm"
                >
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div class="flex items-start gap-3">
                            <Building2 class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" />
                            <div>
                                <p class="font-semibold">{{ entry.property_name }}</p>
                                <p class="text-sm text-muted-foreground">
                                    Unit {{ entry.unit_number }}
                                    <template v-if="entry.building_name">
                                        · {{ entry.building_name }}
                                    </template>
                                </p>
                            </div>
                        </div>
                        <span :class="['shrink-0 inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium', statusColor(entry.status)]">
                            {{ entry.status_label ?? entry.status }}
                        </span>
                    </div>

                    <div class="mt-3 grid grid-cols-2 gap-2 text-sm sm:grid-cols-4">
                        <div>
                            <p class="text-xs text-muted-foreground">Move In</p>
                            <p>{{ formatDate(entry.move_in_date) }}</p>
                        </div>
                        <div v-if="entry.move_out_date">
                            <p class="text-xs text-muted-foreground">Move Out</p>
                            <p>{{ formatDate(entry.move_out_date) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Monthly Rent</p>
                            <p class="font-medium">{{ formatCurrency(entry.monthly_rent) }}</p>
                        </div>
                        <div>
                            <p class="text-xs text-muted-foreground">Security Deposit</p>
                            <p>{{ formatCurrency(entry.deposit_amount) }}</p>
                        </div>
                    </div>

                    <p v-if="entry.remarks" class="mt-2 text-xs text-muted-foreground italic">
                        "{{ entry.remarks }}"
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
