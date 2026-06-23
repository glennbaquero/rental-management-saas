<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { Dialog, DialogContent, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import InputError from '@/components/InputError.vue';

const props = defineProps<{
    open: boolean;
    leaseId: string;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const documentTypes = [
    { value: 'lease_agreement',  label: 'Lease Agreement' },
    { value: 'signed_contract',  label: 'Signed Contract' },
    { value: 'id_document',      label: 'Government ID' },
    { value: 'proof_of_income',  label: 'Proof of Income' },
    { value: 'other',            label: 'Other' },
];

const fileInput = ref<HTMLInputElement | null>(null);
const fileName  = ref('');

const form = useForm<{
    name: string;
    type: string;
    file: File | null;
}>({
    name: '',
    type: 'lease_agreement',
    file: null,
});

watch(() => props.open, (open) => {
    if (!open) return;
    form.reset();
    form.type = 'lease_agreement';
    fileName.value = '';
});

function onFileChange(event: Event) {
    const input = event.target as HTMLInputElement;
    const file  = input.files?.[0];
    if (!file) return;
    form.file  = file;
    fileName.value = file.name;
    if (!form.name) {
        form.name = file.name.replace(/\.[^/.]+$/, '');
    }
}

function submit() {
    form.post(`/leases/${props.leaseId}/documents`, {
        forceFormData: true,
        onSuccess:     () => emit('update:open', false),
    });
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-md">
            <DialogHeader>
                <DialogTitle>Upload Document</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label>Document Type <span class="text-destructive">*</span></Label>
                    <Select v-model="form.type">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="t in documentTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="form.errors.type" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>Document Name <span class="text-destructive">*</span></Label>
                    <Input v-model="form.name" placeholder="e.g. Signed Lease Agreement" />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex flex-col gap-1.5">
                    <Label>File <span class="text-destructive">*</span></Label>
                    <div
                        class="cursor-pointer rounded-lg border-2 border-dashed border-muted-foreground/30 p-6 text-center transition hover:border-primary/50 hover:bg-muted/30"
                        @click="fileInput?.click()"
                    >
                        <Upload class="mx-auto mb-2 h-8 w-8 text-muted-foreground/50" />
                        <p v-if="fileName" class="text-sm font-medium">{{ fileName }}</p>
                        <p v-else class="text-sm text-muted-foreground">Click to select a file</p>
                        <p class="mt-1 text-xs text-muted-foreground">PDF, JPG, PNG, DOCX — max 10 MB</p>
                    </div>
                    <input
                        ref="fileInput"
                        type="file"
                        accept=".pdf,.jpg,.jpeg,.png,.docx"
                        class="hidden"
                        @change="onFileChange"
                    />
                    <InputError :message="form.errors.file" />
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing || !form.file">
                        {{ form.processing ? 'Uploading…' : 'Upload' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
