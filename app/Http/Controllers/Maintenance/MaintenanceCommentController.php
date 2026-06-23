<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceCommentRequest;
use App\Models\MaintenanceComment;
use App\Models\MaintenanceTicket;
use App\Services\Maintenance\MaintenanceTicketService;
use Illuminate\Http\RedirectResponse;

class MaintenanceCommentController extends Controller
{
    public function __construct(
        private readonly MaintenanceTicketService $service,
    ) {}

    public function store(StoreMaintenanceCommentRequest $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $data    = $request->validated();
        $comment = $this->service->addComment(
            $ticket,
            collect($data)->except('attachments')->toArray(),
            $request->user()->id
        );

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('maintenance/attachments', 'public');
                $ticket->attachments()->create([
                    'comment_id'  => $comment->id,
                    'name'        => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'file_size'   => $file->getSize(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Comment added.']);
    }

    public function destroy(MaintenanceTicket $ticket, MaintenanceComment $comment): RedirectResponse
    {
        $comment->attachments->each(function ($attachment) {
            \Illuminate\Support\Facades\Storage::disk('public')->delete($attachment->file_path);
            $attachment->delete();
        });

        $comment->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Comment deleted.']);
    }
}
