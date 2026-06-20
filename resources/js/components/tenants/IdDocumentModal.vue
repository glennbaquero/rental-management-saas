<script setup lang="ts">
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { Upload } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { TenantIdDocument } from '@/types/tenant';

const props = defineProps<{
    open: boolean;
    tenantId: string;
    document?: TenantIdDocument | null;
}>();

const emit = defineEmits<{
    'update:open': [value: boolean];
}>();

const isEdit = () => !!props.document;

const documentTypes = [
    { value: 'national_id',      label: 'National ID' },
    { value: 'passport',         label: 'Passport' },
    { value: 'drivers_license',  label: "Driver's License" },
    { value: 'sss',              label: 'SSS ID' },
    { value: 'tin',              label: 'TIN ID' },
    { value: 'residence_permit', label: 'Residence Permit' },
    { value: 'other',            label: 'Other' },
];

const verificationStatuses = [
    { value: 'pending',  label: 'Pending' },
    { value: 'verified', label: 'Verified' },
    { value: 'rejected', label: 'Rejected' },
];

const frontPreview = ref<string | null>(null);
const backPreview  = ref<string | null>(null);
const frontInput   = ref<HTMLInputElement | null>(null);
const backInput    = ref<HTMLInputElement | null>(null);

const form = useForm({
    type:                '',
    document_number:     '',
    issued_by:           '',
    issued_date:         '',
    expiry_date:         '',
    verification_status: 'pending',
    front_image:         null as File | null,
    back_image:          null as File | null,
});

watch(() => props.open, (open) => {
    if (!open) return;
    const doc = props.document;
    form.reset();
    frontPreview.value = null;
    backPreview.value  = null;

    if (doc) {
        form.type                = doc.type ?? '';
        form.document_number     = doc.document_number ?? '';
        form.issued_by           = doc.issued_by ?? '';
        form.issued_date         = doc.issued_date ?? '';
        form.expiry_date         = doc.expiry_date ?? '';
        form.verification_status = doc.verification_status ?? 'pending';
        frontPreview.value       = doc.front_image_url;
        backPreview.value        = doc.back_image_url;
    }
});

function onFileChange(e: Event, side: 'front' | 'back') {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    if (side === 'front') {
        form.front_image   = file;
        frontPreview.value = URL.createObjectURL(file);
    } else {
        form.back_image   = file;
        backPreview.value = URL.createObjectURL(file);
    }
}

function submit() {
    const onSuccess = () => emit('update:open', false);

    if (isEdit()) {
        form.patch(`/tenants/${props.tenantId}/documents/${props.document!.id}`, {
            forceFormData: true,
            onSuccess,
        });
    } else {
        form.post(`/tenants/${props.tenantId}/documents`, {
            forceFormData: true,
            onSuccess,
        });
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent class="max-w-lg">
            <DialogHeader>
                <DialogTitle>{{ isEdit() ? 'Edit Document' : 'Add ID Document' }}</DialogTitle>
            </DialogHeader>

            <form @submit.prevent="submit" class="flex flex-col gap-4">
                <div class="flex flex-col gap-1.5">
                    <Label>Document Type <span class="text-destructive">*</span></Label>
                    <Select v-model="form.type">
                        <SelectTrigger><SelectValue placeholder="Select type…" /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="t in documentTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                    <p v-if="form.errors.type" class="text-xs text-destructive">{{ form.errors.type }}</p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-1.5">
                        <Label>Document Number</Label>
                        <Input v-model="form.document_number" placeholder="ID-1234567" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label>Issued By</Label>
                        <Input v-model="form.issued_by" placeholder="Republic of the Philippines" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label>Issue Date</Label>
                        <Input v-model="form.issued_date" type="date" />
                    </div>
                    <div class="flex flex-col gap-1.5">
                        <Label>Expiry Date</Label>
                        <Input v-model="form.expiry_date" type="date" />
                        <p v-if="form.errors.expiry_date" class="text-xs text-destructive">{{ form.errors.expiry_date }}</p>
                    </div>
                </div>

                <div v-if="isEdit()" class="flex flex-col gap-1.5">
                    <Label>Verification Status</Label>
                    <Select v-model="form.verification_status">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="s in verificationStatuses" :key="s.value" :value="s.value">{{ s.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <!-- Image uploads -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="flex flex-col gap-2">
                        <Label>Front Image</Label>
                        <button
                            type="button"
                            class="flex h-24 w-full items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-muted hover:bg-muted/80 transition-colors"
                            @click="frontInput?.click()"
                        >
                            <img v-if="frontPreview" :src="frontPreview" alt="Front" class="h-full w-full object-cover" />
                            <Upload v-else class="h-6 w-6 text-muted-foreground" />
                        </button>
                        <input ref="frontInput" type="file" accept="image/*" class="hidden" @change="onFileChange($event, 'front')" />
                    </div>
                    <div class="flex flex-col gap-2">
                        <Label>Back Image</Label>
                        <button
                            type="button"
                            class="flex h-24 w-full items-center justify-center overflow-hidden rounded-lg border-2 border-dashed bg-muted hover:bg-muted/80 transition-colors"
                            @click="backInput?.click()"
                        >
                            <img v-if="backPreview" :src="backPreview" alt="Back" class="h-full w-full object-cover" />
                            <Upload v-else class="h-6 w-6 text-muted-foreground" />
                        </button>
                        <input ref="backInput" type="file" accept="image/*" class="hidden" @change="onFileChange($event, 'back')" />
                    </div>
                </div>

                <DialogFooter>
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cancel</Button>
                    <Button type="submit" :disabled="form.processing || !form.type">
                        {{ form.processing ? 'Saving…' : (isEdit() ? 'Save Changes' : 'Add Document') }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
