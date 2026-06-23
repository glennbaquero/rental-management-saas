<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lease;

use App\Http\Controllers\Controller;
use App\Http\Requests\Lease\TerminateLeaseRequest;
use App\Models\Lease;
use App\Services\Lease\LeaseService;
use Illuminate\Http\RedirectResponse;

class LeaseTerminationController extends Controller
{
    public function __construct(
        private readonly LeaseService $leaseService,
    ) {}

    public function store(TerminateLeaseRequest $request, Lease $lease): RedirectResponse
    {
        $this->leaseService->terminate($lease, $request->validated(), $request->user()->id);

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Lease terminated successfully.']);
    }
}
