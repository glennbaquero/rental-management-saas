<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lease;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Lease;
use App\Models\LeaseHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class LeaseDocumentController extends Controller
{
    public function store(Request $request, Lease $lease): RedirectResponse
    {
        $request->validate([
            'file'  => ['required', 'file', 'max:10240', 'mimes:pdf,jpg,jpeg,png,docx'],
            'name'  => ['required', 'string', 'max:255'],
            'type'  => ['required', 'string', 'in:lease_agreement,signed_contract,id_document,proof_of_income,other'],
        ]);

        $file     = $request->file('file');
        $path     = $file->store("leases/{$lease->id}/documents", 'public');

        $lease->documents()->create([
            'name'        => $request->name,
            'type'        => $request->type,
            'file_path'   => $path,
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        LeaseHistory::record(
            $lease,
            'document_uploaded',
            "Document uploaded: {$request->name}.",
            ['name' => $request->name, 'type' => $request->type],
            $request->user()->id
        );

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Document uploaded successfully.']);
    }

    public function destroy(Lease $lease, Document $document): RedirectResponse
    {
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        LeaseHistory::record(
            $lease,
            'document_deleted',
            "Document deleted: {$document->name}.",
            ['name' => $document->name],
            request()->user()->id
        );

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Document deleted.']);
    }
}
