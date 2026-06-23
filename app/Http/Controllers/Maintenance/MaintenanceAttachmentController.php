<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceAttachmentRequest;
use App\Models\MaintenanceAttachment;
use App\Models\MaintenanceTicket;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class MaintenanceAttachmentController extends Controller
{
    public function store(StoreMaintenanceAttachmentRequest $request, MaintenanceTicket $ticket): RedirectResponse
    {
        foreach ($request->file('files') as $file) {
            $path = $file->store('maintenance/attachments', 'public');
            $ticket->attachments()->create([
                'name'        => $file->getClientOriginalName(),
                'file_path'   => $path,
                'file_size'   => $file->getSize(),
                'mime_type'   => $file->getMimeType(),
                'uploaded_by' => $request->user()->id,
            ]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Files uploaded successfully.']);
    }

    public function destroy(MaintenanceTicket $ticket, MaintenanceAttachment $attachment): RedirectResponse
    {
        Storage::disk('public')->delete($attachment->file_path);
        $attachment->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Attachment deleted.']);
    }
}
