<script setup lang="ts">
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';
import { Download, Eye, FileText, Trash2, Upload } from '@lucide/vue';
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
import type { TenantFile } from '@/types/tenant';

const props = defineProps<{
    tenantId: string;
    files: TenantFile[];
    canUpload: boolean;
}>();

const fileTypes = [
    { value: 'lease_agreement',        label: 'Lease Agreement' },
    { value: 'proof_of_income',        label: 'Proof of Income' },
    { value: 'employment_certificate', label: 'Employment Certificate' },
    { value: 'utility_bill',           label: 'Utility Bill' },
    { value: 'police_clearance',       label: 'Police Clearance' },
    { value: 'other',                  label: 'Other' },
];

const isDragging    = ref(false);
const uploadOpen    = ref(false);
const previewOpen   = ref(false);
const previewFile   = ref<TenantFile | null>(null);
const selectedFile  = ref<File | null>(null);
const fileInput     = ref<HTMLInputElement | null>(null);

const form = useForm({
    file: null as File | null,
    type: 'other',
    name: '',
});

function onDrop(e: DragEvent) {
    e.preventDefault();
    isDragging.value = false;
    const file = e.dataTransfer?.files?.[0];
    if (file) openUploadWithFile(file);
}

function onFileSelect(e: Event) {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (file) openUploadWithFile(file);
}

function openUploadWithFile(file: File) {
    selectedFile.value = file;
    form.file = file;
    form.name = file.name.replace(/\.[^/.]+$/, '');
    uploadOpen.value = true;
}

function submitUpload() {
    form.post(`/tenants/${props.tenantId}/files`, {
        forceFormData: true,
        onSuccess: () => {
            uploadOpen.value = false;
            form.reset();
            selectedFile.value = null;
            if (fileInput.value) fileInput.value.value = '';
        },
    });
}

function openPreview(file: TenantFile) {
    previewFile.value = file;
    previewOpen.value = true;
}

function isPdf(mimeType: string | null): boolean {
    return mimeType === 'application/pdf';
}

function isImage(mimeType: string | null): boolean {
    return !!mimeType?.startsWith('image/');
}

function formatSize(bytes: number | null): string {
    if (!bytes) return '';
    if (bytes >= 1048576) return `${(bytes / 1048576).toFixed(1)} MB`;
    return `${(bytes / 1024).toFixed(0)} KB`;
}

function confirmDelete(file: TenantFile) {
    if (confirm(`Delete "${file.name}"?`)) {
        router.delete(`/tenants/${props.tenantId}/files/${file.id}`);
    }
}
</script>

<template>
    <!-- Drag & Drop Zone -->
    <div
        v-if="canUpload"
        :class="[
            'rounded-lg border-2 border-dashed p-8 text-center transition-colors cursor-pointer',
            isDragging ? 'border-primary bg-primary/5' : 'border-muted-foreground/20 hover:border-muted-foreground/40',
        ]"
        @click="fileInput?.click()"
        @dragover.prevent="isDragging = true"
        @dragleave.prevent="isDragging = false"
        @drop="onDrop"
    >
        <Upload class="mx-auto mb-2 h-8 w-8 text-muted-foreground" />
        <p class="text-sm font-medium">Drop files here or <span class="text-primary underline">browse</span></p>
        <p class="mt-1 text-xs text-muted-foreground">PDF, images, and documents · Max 10 MB</p>
        <input ref="fileInput" type="file" class="hidden" accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx" @change="onFileSelect" />
    </div>

    <!-- File List -->
    <div v-if="files.length > 0" class="mt-4 space-y-2">
        <div
            v-for="file in files"
            :key="file.id"
            class="flex items-center gap-3 rounded-lg border p-3"
        >
            <FileText class="h-5 w-5 shrink-0 text-muted-foreground" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-medium">{{ file.name }}</p>
                <p class="text-xs text-muted-foreground">
                    {{ file.type_label }} · {{ formatSize(file.file_size) }}
                </p>
            </div>
            <div class="flex shrink-0 gap-1">
                <Button
                    v-if="isPdf(file.mime_type) || isImage(file.mime_type)"
                    size="sm"
                    variant="ghost"
                    class="h-7 w-7 p-0"
                    @click="openPreview(file)"
                >
                    <Eye class="h-3.5 w-3.5" />
                </Button>
                <Button
                    size="sm"
                    variant="ghost"
                    class="h-7 w-7 p-0"
                    as="a"
                    :href="file.url"
                    download
                    target="_blank"
                >
                    <Download class="h-3.5 w-3.5" />
                </Button>
                <Button
                    v-if="canUpload"
                    size="sm"
                    variant="ghost"
                    class="h-7 w-7 p-0 text-destructive hover:text-destructive"
                    @click="confirmDelete(file)"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </div>
        </div>
    </div>

    <div v-else-if="!canUpload" class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground">
        No files uploaded yet.
    </div>

    <!-- Upload Config Dialog -->
    <Dialog v-model:open="uploadOpen">
        <DialogContent class="max-w-sm">
            <DialogHeader>
                <DialogTitle>Upload File</DialogTitle>
            </DialogHeader>
            <form @submit.prevent="submitUpload" class="flex flex-col gap-4">
                <div class="rounded-lg border bg-muted p-3 text-sm text-muted-foreground">
                    📎 {{ selectedFile?.name }}
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label>Document Name</Label>
                    <Input v-model="form.name" placeholder="e.g. Lease Agreement 2026" />
                </div>
                <div class="flex flex-col gap-1.5">
                    <Label>Document Type <span class="text-destructive">*</span></Label>
                    <Select v-model="form.type">
                        <SelectTrigger><SelectValue /></SelectTrigger>
                        <SelectContent>
                            <SelectItem v-for="t in fileTypes" :key="t.value" :value="t.value">{{ t.label }}</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <DialogFooter>
                    <Button type="button" variant="outline" @click="uploadOpen = false">Cancel</Button>
                    <Button type="submit" :disabled="form.processing">
                        {{ form.processing ? 'Uploading…' : 'Upload' }}
                    </Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <!-- Preview Dialog -->
    <Dialog v-model:open="previewOpen">
        <DialogContent class="max-w-3xl">
            <DialogHeader>
                <DialogTitle>{{ previewFile?.name }}</DialogTitle>
            </DialogHeader>
            <div class="overflow-auto rounded">
                <iframe
                    v-if="previewFile && isPdf(previewFile.mime_type)"
                    :src="previewFile.url"
                    class="h-[70vh] w-full"
                />
                <img
                    v-else-if="previewFile && isImage(previewFile.mime_type)"
                    :src="previewFile.url"
                    :alt="previewFile.name"
                    class="max-h-[70vh] w-full object-contain"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
