<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { Download, Eye, Pencil, Trash2 } from '@lucide/vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter } from '@/components/ui/card';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import type { TenantIdDocument } from '@/types/tenant';

const props = defineProps<{
    document: TenantIdDocument;
    tenantId: string;
    canEdit: boolean;
}>();

const emit = defineEmits<{
    edit: [document: TenantIdDocument];
}>();

const previewOpen = ref(false);
const previewUrl  = ref<string | null>(null);

const verificationColor = computed(() => {
    switch (props.document.verification_status) {
        case 'verified': return 'bg-emerald-100 text-emerald-800';
        case 'rejected': return 'bg-red-100 text-red-800';
        default:         return 'bg-yellow-100 text-yellow-800';
    }
});

const verificationLabel = computed(() => props.document.verification_status_label ?? 'Pending');

function openPreview(url: string) {
    previewUrl.value  = url;
    previewOpen.value = true;
}

function isPdf(url: string): boolean {
    return url.toLowerCase().includes('.pdf');
}

function download(url: string, name: string) {
    const a = document.createElement('a');
    a.href     = url;
    a.download = name;
    a.target   = '_blank';
    a.click();
}

function confirmDelete() {
    if (confirm(`Remove this ${props.document.type_label} document?`)) {
        router.delete(`/tenants/${props.tenantId}/documents/${props.document.id}`);
    }
}
</script>

<template>
    <Card class="flex flex-col">
        <CardContent class="flex-1 pt-4">
            <div class="flex items-start justify-between gap-2">
                <div>
                    <p class="font-medium text-sm">{{ document.type_label ?? 'Document' }}</p>
                    <p v-if="document.document_number" class="text-xs text-muted-foreground">#{{ document.document_number }}</p>
                </div>
                <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', verificationColor]">
                    {{ verificationLabel }}
                </span>
            </div>

            <div class="mt-3 space-y-1 text-xs text-muted-foreground">
                <div v-if="document.issued_by">Issued by: {{ document.issued_by }}</div>
                <div v-if="document.expiry_date" :class="document.is_expired ? 'text-destructive font-medium' : ''">
                    {{ document.is_expired ? 'Expired: ' : 'Expires: ' }}{{ document.expiry_date }}
                </div>
            </div>

            <!-- Thumbnails -->
            <div v-if="document.front_image_url || document.back_image_url" class="mt-3 flex gap-2">
                <button
                    v-if="document.front_image_url"
                    class="h-14 w-14 overflow-hidden rounded border bg-muted"
                    @click="openPreview(document.front_image_url!)"
                >
                    <img :src="document.front_image_url" alt="Front" class="h-full w-full object-cover" />
                </button>
                <button
                    v-if="document.back_image_url"
                    class="h-14 w-14 overflow-hidden rounded border bg-muted"
                    @click="openPreview(document.back_image_url!)"
                >
                    <img :src="document.back_image_url" alt="Back" class="h-full w-full object-cover" />
                </button>
            </div>
        </CardContent>

        <CardFooter class="gap-1 border-t pt-3">
            <Button
                v-if="document.front_image_url"
                size="sm"
                variant="ghost"
                class="h-7 px-2"
                @click="openPreview(document.front_image_url!)"
            >
                <Eye class="mr-1 h-3.5 w-3.5" /> Preview
            </Button>
            <Button
                v-if="document.front_image_url"
                size="sm"
                variant="ghost"
                class="h-7 px-2"
                @click="download(document.front_image_url!, `${document.type_label}-front`)"
            >
                <Download class="h-3.5 w-3.5" />
            </Button>
            <div class="ml-auto flex gap-1">
                <Button v-if="canEdit" size="sm" variant="ghost" class="h-7 px-2" @click="emit('edit', document)">
                    <Pencil class="h-3.5 w-3.5" />
                </Button>
                <Button
                    v-if="canEdit"
                    size="sm"
                    variant="ghost"
                    class="h-7 px-2 text-destructive hover:text-destructive"
                    @click="confirmDelete"
                >
                    <Trash2 class="h-3.5 w-3.5" />
                </Button>
            </div>
        </CardFooter>
    </Card>

    <!-- Preview Dialog -->
    <Dialog v-model:open="previewOpen">
        <DialogContent class="max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ document.type_label }}</DialogTitle>
            </DialogHeader>
            <div class="overflow-auto rounded">
                <iframe
                    v-if="previewUrl && isPdf(previewUrl)"
                    :src="previewUrl"
                    class="h-[60vh] w-full"
                />
                <img
                    v-else-if="previewUrl"
                    :src="previewUrl"
                    alt="Document preview"
                    class="max-h-[60vh] w-full object-contain"
                />
            </div>
        </DialogContent>
    </Dialog>
</template>
