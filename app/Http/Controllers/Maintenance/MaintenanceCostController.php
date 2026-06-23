<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceCostRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceCostRequest;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceTicket;
use App\Services\Maintenance\MaintenanceTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

class MaintenanceCostController extends Controller
{
    public function __construct(
        private readonly MaintenanceTicketService $service,
    ) {}

    public function store(StoreMaintenanceCostRequest $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            $data['receipt_path'] = $request->file('receipt')->store('maintenance/receipts', 'public');
        }

        $this->service->addCost($ticket, collect($data)->except('receipt')->toArray(), $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Cost entry added.']);
    }

    public function update(UpdateMaintenanceCostRequest $request, MaintenanceTicket $ticket, MaintenanceCost $cost): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('receipt')) {
            if ($cost->receipt_path) {
                Storage::disk('public')->delete($cost->receipt_path);
            }
            $data['receipt_path'] = $request->file('receipt')->store('maintenance/receipts', 'public');
        }

        if (isset($data['status']) && $data['status'] === 'approved' && $cost->status->value !== 'approved') {
            $data['approved_by'] = $request->user()->id;
            $data['approved_at'] = now();
        }

        $cost->update(collect($data)->except('receipt')->toArray());

        return back()->with('toast', ['type' => 'success', 'message' => 'Cost entry updated.']);
    }

    public function destroy(MaintenanceTicket $ticket, MaintenanceCost $cost): RedirectResponse
    {
        if ($cost->receipt_path) {
            Storage::disk('public')->delete($cost->receipt_path);
        }

        $cost->delete();

        return back()->with('toast', ['type' => 'success', 'message' => 'Cost entry deleted.']);
    }
}
