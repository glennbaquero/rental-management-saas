<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lease;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lease\RenewLeaseRequest;
use App\Models\Lease;
use App\Models\LeaseRenewal;
use App\Services\Lease\LeaseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LeaseRenewalController extends Controller
{
    public function __construct(
        private readonly LeaseService $leaseService,
    ) {}

    public function store(RenewLeaseRequest $request, Lease $lease): RedirectResponse
    {
        $this->leaseService->renew($lease, $request->validated(), $request->user()->id);

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Lease renewed successfully.']);
    }

    public function update(Request $request, Lease $lease, LeaseRenewal $renewal): RedirectResponse
    {
        $data = $request->validate([
            'renewal_status' => ['required', \Illuminate\Validation\Rule::enum(\App\Enums\LeaseRenewalStatus::class)],
        ]);

        $renewal->update($data);

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Renewal status updated.']);
    }
}
