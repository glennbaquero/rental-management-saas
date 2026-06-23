<?php

declare(strict_types=1);

namespace App\Http\Controllers\Lease;

use App\Enums\LeaseDepositStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Lease\StoreLeaseDepositRequest;
use App\Models\Lease;
use App\Models\LeaseDeposit;
use App\Models\LeaseHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LeaseDepositController extends Controller
{
    public function store(StoreLeaseDepositRequest $request, Lease $lease): RedirectResponse
    {
        $data               = $request->validated();
        $data['lease_id']   = $lease->id;
        $data['created_by'] = $request->user()->id;

        $deposit = LeaseDeposit::create($data);

        LeaseHistory::record(
            $lease,
            'deposit_recorded',
            "Deposit recorded: {$deposit->type?->label()} — {$lease->currency} {$deposit->amount}.",
            ['deposit_id' => $deposit->id, 'type' => $deposit->type?->value, 'amount' => $deposit->amount],
            $request->user()->id
        );

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Deposit recorded successfully.']);
    }

    public function update(Request $request, Lease $lease, LeaseDeposit $deposit): RedirectResponse
    {
        $data = $request->validate([
            'status'           => ['required', Rule::enum(LeaseDepositStatus::class)],
            'payment_date'     => ['nullable', 'date'],
            'refund_amount'    => ['nullable', 'numeric', 'min:0'],
            'deduction_amount' => ['nullable', 'numeric', 'min:0'],
            'refund_date'      => ['nullable', 'date'],
            'notes'            => ['nullable', 'string', 'max:1000'],
        ]);

        $deposit->update($data);

        LeaseHistory::record(
            $lease,
            'deposit_updated',
            "Deposit status updated to: {$deposit->status?->label()}.",
            ['deposit_id' => $deposit->id, 'status' => $data['status']],
            $request->user()->id
        );

        return redirect()->route('leases.show', $lease)
            ->with('toast', ['type' => 'success', 'message' => 'Deposit updated.']);
    }
}
