<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantFileRequest;
use App\Models\RentalTenant;
use App\Models\TenantFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class TenantFileController extends Controller
{
    public function store(StoreTenantFileRequest $request, RentalTenant $tenant): RedirectResponse
    {
        $file = $request->file('file');

        $path = $file->store('tenants/files', 'public');

        $tenant->tenantFiles()->create([
            'name'        => $request->validated('name') ?: $file->getClientOriginalName(),
            'type'        => $request->validated('type'),
            'file_path'   => $path,
            'file_size'   => $file->getSize(),
            'mime_type'   => $file->getMimeType(),
            'uploaded_by' => $request->user()->id,
        ]);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'File uploaded successfully.']);
    }

    public function destroy(RentalTenant $tenant, TenantFile $file): RedirectResponse
    {
        Storage::disk('public')->delete($file->file_path);
        $file->delete();

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'File deleted.']);
    }
}
