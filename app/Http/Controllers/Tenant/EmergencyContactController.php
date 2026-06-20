<?php

declare(strict_types=1);

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmergencyContactRequest;
use App\Http\Requests\Tenant\UpdateEmergencyContactRequest;
use App\Models\EmergencyContact;
use App\Models\RentalTenant;
use Illuminate\Http\RedirectResponse;

class EmergencyContactController extends Controller
{
    public function store(StoreEmergencyContactRequest $request, RentalTenant $tenant): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['is_primary'])) {
            $tenant->emergencyContacts()->update(['is_primary' => false]);
        }

        $tenant->emergencyContacts()->create($data);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Emergency contact added.']);
    }

    public function update(UpdateEmergencyContactRequest $request, RentalTenant $tenant, EmergencyContact $contact): RedirectResponse
    {
        $data = $request->validated();

        if (! empty($data['is_primary'])) {
            $tenant->emergencyContacts()
                ->where('id', '!=', $contact->id)
                ->update(['is_primary' => false]);
        }

        $contact->update($data);

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Emergency contact updated.']);
    }

    public function destroy(RentalTenant $tenant, EmergencyContact $contact): RedirectResponse
    {
        $contact->delete();

        return redirect()->route('tenants.show', $tenant)
            ->with('toast', ['type' => 'success', 'message' => 'Emergency contact removed.']);
    }
}
