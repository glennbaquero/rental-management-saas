<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import InputError from '@/components/InputError.vue';
import type { MaintenanceTicketDetail } from '@/types/maintenance';

const props = defineProps<{
    open: boolean;
    ticket: MaintenanceTicketDetail;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const hovered = ref(0);

const form = useForm({
    rating: 0,
    feedback: '',
    would_recommend: null as boolean | null,
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    hovered.value = 0;
});

function setRating(val: number) {
    form.rating = val;
}

function submit() {
    form.post(`/maintenance/${props.ticket.id}/rate`, {
        onSuccess: () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="$emit('update:open', $event)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Rate Your Experience</DialogTitle>
            </DialogHeader>

            <form class="space-y-5" @submit.prevent="submit">
                <div class="space-y-2">
                    <Label>Rating <span class="text-destructive">*</span></Label>
                    <div class="flex gap-1">
                        <button
                            v-for="star in 5"
                            :key="star"
                            type="button"
                            class="text-3xl transition-transform hover:scale-110 focus:outline-none"
                            :class="star <= (hovered || form.rating) ? 'text-amber-400' : 'text-gray-300 dark:text-gray-600'"
                            @mouseenter="hovered = star"
                            @mouseleave="hovered = 0"
                            @click="setRating(star)"
                        >
                            ★
                        </button>
                    </div>
                    <InputError :message="form.errors.rating" />
                </div>

                <div class="space-y-1.5">
                    <Label>Feedback</Label>
                    <Textarea v-model="form.feedback" rows="3" placeholder="Share your experience..." />
                    <InputError :message="form.errors.feedback" />
                </div>

                <div class="space-y-2">
                    <Label>Would you recommend our maintenance service?</Label>
                    <div class="flex gap-3">
                        <Button
                            type="button"
                            :variant="form.would_recommend === true ? 'default' : 'outline'"
                            size="sm"
                            @click="form.would_recommend = true"
                        >
                            Yes
                        </Button>
                        <Button
                            type="button"
                            :variant="form.would_recommend === false ? 'destructive' : 'outline'"
                            size="sm"
                            @click="form.would_recommend = false"
                        >
                            No
                        </Button>
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="$emit('update:open', false)">Skip</Button>
                    <Button type="submit" :disabled="form.processing || form.rating === 0">
                        {{ form.processing ? 'Submitting…' : 'Submit Rating' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
