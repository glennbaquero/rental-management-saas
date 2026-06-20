<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantIdDocumentRequest;
use App\Http\Requests\Tenant\UpdateTenantIdDocumentRequest;
use App\Models\RentalTenant;
use App\Models\TenantIdDocument;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class TenantIdDocumentController extends Controller
{
    public function store(StoreTenantIdDocumentRequest $request, RentalTenant $tenant): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('front_image')) {
            $data['front_image'] = $request->file('front_image')
                ->store('tenants/documents', 'public');
        }

        if ($request->hasFile('back_image')) {
            $data['back_image'] = $request->file('back_image')
                ->store('tenants/documents', 'public');
        }

        $tenant->idDocuments()->create($data);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Document added successfully.']);
    }

    public function update(UpdateTenantIdDocumentRequest $request, RentalTenant $tenant, TenantIdDocument $document): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('front_image')) {
            if ($document->front_image) {
                Storage::disk('public')->delete($document->front_image);
            }
            $data['front_image'] = $request->file('front_image')
                ->store('tenants/documents', 'public');
        }

        if ($request->hasFile('back_image')) {
            if ($document->back_image) {
                Storage::disk('public')->delete($document->back_image);
            }
            $data['back_image'] = $request->file('back_image')
                ->store('tenants/documents', 'public');
        }

        $document->update($data);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Document updated successfully.']);
    }

    public function destroy(RentalTenant $tenant, TenantIdDocument $document): RedirectResponse
    {
        if ($document->front_image) {
            Storage::disk('public')->delete($document->front_image);
        }
        if ($document->back_image) {
            Storage::disk('public')->delete($document->back_image);
        }
        if ($document->file_path) {
            Storage::disk('public')->delete($document->file_path);
        }

        $document->delete();

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Document removed.']);
    }
}
