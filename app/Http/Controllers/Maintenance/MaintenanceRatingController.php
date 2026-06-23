<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceRatingRequest;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceTicket;
use Illuminate\Http\RedirectResponse;

class MaintenanceRatingController extends Controller
{
    public function store(StoreMaintenanceRatingRequest $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $ticket->rating()->create([
            ...$request->validated(),
            'rental_tenant_id' => $ticket->rental_tenant_id,
            'rated_at'         => now(),
        ]);

        MaintenanceHistory::record(
            $ticket,
            'rated',
            "Tenant submitted a {$request->rating}-star rating.",
            ['rating' => $request->rating],
            $request->user()->id
        );

        return back()->with('toast', ['type' => 'success', 'message' => 'Thank you for your feedback!']);
    }
}
