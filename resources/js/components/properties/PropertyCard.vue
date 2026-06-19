<script setup lang="ts">
import { Building2, DoorOpen, MapPin, Pencil, Trash2, Eye } from '@lucide/vue';
import { Link } from '@inertiajs/vue3';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import PropertyStatusBadge from '@/components/properties/PropertyStatusBadge.vue';
import type { Property } from '@/types/property';

const props = defineProps<{
    property: Property;
    canEdit?: boolean;
    canDelete?: boolean;
}>();

const emit = defineEmits<{
    delete: [property: Property];
}>();
</script>

<template>
    <Card class="group overflow-hidden border-border/50 hover:border-border hover:shadow-md transition-all duration-200">
        <div class="relative aspect-video overflow-hidden bg-muted">
            <img
                v-if="property.featured_image_url"
                :src="property.featured_image_url"
                :alt="property.name"
                class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-300"
            />
            <div v-else class="flex h-full items-center justify-center">
                <Building2 class="h-12 w-12 text-muted-foreground/30" />
            </div>

            <div class="absolute top-3 right-3">
                <PropertyStatusBadge :status="property.status" :label="property.status_label" />
            </div>
        </div>

        <CardContent class="p-4">
            <span class="text-xs font-medium uppercase tracking-wide text-muted-foreground">
                {{ property.type_label ?? property.type }}
            </span>

            <h3 class="mt-1 truncate text-base font-semibold leading-tight">
                {{ property.name }}
            </h3>

            <div class="mt-1 flex items-center gap-1 text-sm text-muted-foreground">
                <MapPin class="h-3.5 w-3.5 shrink-0" />
                <span class="truncate">{{ property.city }}{{ property.province ? `, ${property.province}` : '' }}</span>
            </div>

            <div class="mt-3 flex items-center gap-4 text-sm text-muted-foreground">
                <div class="flex items-center gap-1.5">
                    <Building2 class="h-4 w-4" />
                    <span>{{ property.total_buildings }} {{ property.total_buildings === 1 ? 'Building' : 'Buildings' }}</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <DoorOpen class="h-4 w-4" />
                    <span>{{ property.total_units }} Units</span>
                </div>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <Button size="sm" class="flex-1" as-child>
                    <Link :href="`/properties/${property.id}`">
                        <Eye class="mr-1.5 h-3.5 w-3.5" />
                        View
                    </Link>
                </Button>

                <Button v-if="canEdit" size="sm" variant="outline" as-child>
                    <Link :href="`/properties/${property.id}/edit`">
                        <Pencil class="h-3.5 w-3.5" />
                    </Link>
                </Button>

                <Button
                    v-if="canDelete"
                    size="sm"
                    variant="outline"
                    class="text-destructive hover:text-destructive"
                    @click="emit('delete', property)"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </div>
        </CardContent>
    </Card>
</template>
