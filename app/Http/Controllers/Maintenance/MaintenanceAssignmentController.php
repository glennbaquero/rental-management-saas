<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\AssignMaintenanceTicketRequest;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceTicket;
use App\Services\Maintenance\MaintenanceTicketService;
use Illuminate\Http\RedirectResponse;

class MaintenanceAssignmentController extends Controller
{
    public function __construct(
        private readonly MaintenanceTicketService $service,
    ) {}

    public function store(AssignMaintenanceTicketRequest $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $this->service->assignTicket($ticket, $request->validated(), $request->user()->id);

        return back()->with('toast', ['type' => 'success', 'message' => 'Staff assigned successfully.']);
    }

    public function update(AssignMaintenanceTicketRequest $request, MaintenanceTicket $ticket, MaintenanceAssignment $assignment): RedirectResponse
    {
        $assignment->update([
            ...$request->validated(),
        ]);

        return back()->with('toast', ['type' => 'success', 'message' => 'Assignment updated.']);
    }
}
