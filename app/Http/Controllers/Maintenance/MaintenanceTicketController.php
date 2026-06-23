<?php

declare(strict_types=1);

namespace App\Http\Controllers\Maintenance;

use App\Enums\MaintenanceCategory;
use App\Enums\MaintenancePriority;
use App\Enums\MaintenanceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Maintenance\StoreMaintenanceTicketRequest;
use App\Http\Requests\Maintenance\UpdateMaintenanceTicketRequest;
use App\Models\Building;
use App\Models\MaintenanceTicket;
use App\Models\Property;
use App\Models\RentalTenant;
use App\Models\Unit;
use App\Models\User;
use App\Services\Maintenance\MaintenanceTicketService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceTicketController extends Controller
{
    public function __construct(
        private readonly MaintenanceTicketService $service,
    ) {}

    public function index(Request $request): Response
    {
        $tickets = MaintenanceTicket::query()
            ->with([
                'rentalTenant:id,first_name,last_name',
                'property:id,name',
                'unit:id,unit_number',
                'primaryAssignment.user:id,name',
            ])
            ->when($request->search, fn ($q, $v) =>
                $q->where(fn ($inner) =>
                    $inner->where('ticket_number', 'like', "%{$v}%")
                          ->orWhere('title', 'like', "%{$v}%")
                          ->orWhereHas('rentalTenant', fn ($tq) =>
                              $tq->where('first_name', 'like', "%{$v}%")
                                 ->orWhere('last_name', 'like', "%{$v}%")
                          )
                          ->orWhereHas('unit', fn ($uq) => $uq->where('unit_number', 'like', "%{$v}%"))
                )
            )
            ->when($request->status, fn ($q, $v) => $q->where('status', $v))
            ->when($request->priority, fn ($q, $v) => $q->where('priority', $v))
            ->when($request->property_id, fn ($q, $v) => $q->where('property_id', $v))
            ->when($request->category, fn ($q, $v) => $q->where('category', $v))
            ->when($request->assigned_to, fn ($q, $v) =>
                $q->whereHas('assignments', fn ($aq) => $aq->where('user_id', $v))
            )
            ->latest()
            ->paginate(20)
            ->withQueryString()
            ->through(fn (MaintenanceTicket $t) => $this->transformTicketRow($t));

        return Inertia::render('maintenance/Index', [
            'tickets'    => $tickets,
            'filters'    => $request->only(['search', 'status', 'priority', 'property_id', 'category', 'assigned_to']),
            'statuses'   => collect(MaintenanceStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities' => collect(MaintenancePriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'icon' => $c->icon()]),
            'categories' => collect(MaintenanceCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'properties' => Property::orderBy('name')->get(['id', 'name'])->map(fn ($p) => ['value' => $p->id, 'label' => $p->name]),
            'staff'      => User::orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['value' => $u->id, 'label' => $u->name]),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('maintenance/Create', [
            'categories' => collect(MaintenanceCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities' => collect(MaintenancePriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'icon' => $c->icon()]),
            'properties' => Property::with(['buildings:id,property_id,name', 'units:id,property_id,unit_number'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'tenants'    => RentalTenant::orderBy('first_name')->get(['id', 'first_name', 'last_name', 'email']),
        ]);
    }

    public function store(StoreMaintenanceTicketRequest $request): RedirectResponse
    {
        $data   = $request->validated();
        $ticket = $this->service->createTicket(
            collect($data)->except('attachments')->toArray(),
            $request->user()->id
        );

        if ($request->hasFile('attachments')) {
            foreach ($request->file('attachments') as $file) {
                $path = $file->store('maintenance/attachments', 'public');
                $ticket->attachments()->create([
                    'name'        => $file->getClientOriginalName(),
                    'file_path'   => $path,
                    'file_size'   => $file->getSize(),
                    'mime_type'   => $file->getMimeType(),
                    'uploaded_by' => $request->user()->id,
                ]);
            }
        }

        return redirect()->route('maintenance.show', $ticket)
            ->with('toast', ['type' => 'success', 'message' => "Ticket {$ticket->ticket_number} created successfully."]);
    }

    public function show(MaintenanceTicket $ticket): Response
    {
        $ticket->load([
            'property:id,name',
            'building:id,name',
            'unit:id,unit_number',
            'rentalTenant:id,first_name,last_name,email,phone',
            'assignments.user:id,name,avatar',
            'assignments.createdBy:id,name',
            'comments.user:id,name,avatar',
            'comments.attachments',
            'attachments.uploadedBy:id,name',
            'costs.addedBy:id,name',
            'costs.approvedBy:id,name',
            'histories.createdBy:id,name',
            'rating.rentalTenant:id,first_name,last_name',
        ]);

        return Inertia::render('maintenance/Show', [
            'ticket'     => $this->transformTicketDetail($ticket),
            'statuses'   => collect(MaintenanceStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities' => collect(MaintenancePriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'icon' => $c->icon()]),
            'staff'      => User::orderBy('name')->get(['id', 'name'])->map(fn ($u) => ['value' => $u->id, 'label' => $u->name]),
        ]);
    }

    public function edit(MaintenanceTicket $ticket): Response
    {
        return Inertia::render('maintenance/Edit', [
            'ticket'     => $this->transformTicketDetail($ticket->load(['property', 'building', 'unit', 'rentalTenant'])),
            'categories' => collect(MaintenanceCategory::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'priorities' => collect(MaintenancePriority::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label(), 'icon' => $c->icon()]),
            'statuses'   => collect(MaintenanceStatus::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'properties' => Property::with(['buildings:id,property_id,name', 'units:id,property_id,unit_number'])
                ->orderBy('name')
                ->get(['id', 'name']),
            'tenants'    => RentalTenant::orderBy('first_name')->get(['id', 'first_name', 'last_name']),
        ]);
    }

    public function update(UpdateMaintenanceTicketRequest $request, MaintenanceTicket $ticket): RedirectResponse
    {
        $data = $request->validated();

        if (isset($data['status']) && $data['status'] !== $ticket->status->value) {
            $this->service->updateStatus(
                $ticket,
                MaintenanceStatus::from($data['status']),
                $request->user()->id
            );
        }

        $ticket->update([
            ...collect($data)->except(['status', 'attachments'])->toArray(),
            'updated_by' => $request->user()->id,
        ]);

        return redirect()->route('maintenance.show', $ticket)
            ->with('toast', ['type' => 'success', 'message' => 'Ticket updated successfully.']);
    }

    public function destroy(MaintenanceTicket $ticket): RedirectResponse
    {
        $ticket->delete();

        return redirect()->route('maintenance.index')
            ->with('toast', ['type' => 'success', 'message' => 'Ticket deleted.']);
    }

    private function transformTicketRow(MaintenanceTicket $ticket): array
    {
        $assignment = $ticket->primaryAssignment;

        return [
            'id'            => $ticket->id,
            'ticket_number' => $ticket->ticket_number,
            'title'         => $ticket->title,
            'category'      => $ticket->category->value,
            'category_label' => $ticket->category->label(),
            'priority'      => $ticket->priority->value,
            'priority_label' => $ticket->priority->label(),
            'priority_icon' => $ticket->priority->icon(),
            'priority_color' => $ticket->priority->color(),
            'status'        => $ticket->status->value,
            'status_label'  => $ticket->status->label(),
            'status_color'  => $ticket->status->color(),
            'tenant_name'   => $ticket->rentalTenant
                ? "{$ticket->rentalTenant->first_name} {$ticket->rentalTenant->last_name}"
                : '—',
            'property_name' => $ticket->property?->name ?? '—',
            'unit_number'   => $ticket->unit?->unit_number ?? '—',
            'assigned_to'   => $assignment?->user?->name ?? ($assignment?->contractor_name ?? '—'),
            'created_at'    => $ticket->created_at->toDateTimeString(),
            'date_submitted' => $ticket->date_submitted->toDateTimeString(),
        ];
    }

    private function transformTicketDetail(MaintenanceTicket $ticket): array
    {
        return [
            'id'                => $ticket->id,
            'ticket_number'     => $ticket->ticket_number,
            'title'             => $ticket->title,
            'description'       => $ticket->description,
            'notes'             => $ticket->notes,
            'category'          => $ticket->category->value,
            'category_label'    => $ticket->category->label(),
            'priority'          => $ticket->priority->value,
            'priority_label'    => $ticket->priority->label(),
            'priority_icon'     => $ticket->priority->icon(),
            'priority_color'    => $ticket->priority->color(),
            'status'            => $ticket->status->value,
            'status_label'      => $ticket->status->label(),
            'status_color'      => $ticket->status->color(),
            'preferred_schedule' => $ticket->preferred_schedule?->toDateTimeString(),
            'date_submitted'    => $ticket->date_submitted->toDateTimeString(),
            'resolved_at'       => $ticket->resolved_at?->toDateTimeString(),
            'completed_at'      => $ticket->completed_at?->toDateTimeString(),
            'is_overdue'        => $ticket->is_overdue,
            'total_cost'        => $ticket->total_cost,
            'property'          => $ticket->property ? ['id' => $ticket->property->id, 'name' => $ticket->property->name] : null,
            'building'          => $ticket->building ? ['id' => $ticket->building->id, 'name' => $ticket->building->name] : null,
            'unit'              => $ticket->unit ? ['id' => $ticket->unit->id, 'unit_number' => $ticket->unit->unit_number] : null,
            'rental_tenant'     => $ticket->rentalTenant ? [
                'id'    => $ticket->rentalTenant->id,
                'name'  => "{$ticket->rentalTenant->first_name} {$ticket->rentalTenant->last_name}",
                'email' => $ticket->rentalTenant->email,
                'phone' => $ticket->rentalTenant->phone,
            ] : null,
            'assignments' => $ticket->assignments->map(fn ($a) => [
                'id'                  => $a->id,
                'assignee_type'       => $a->assignee_type->value,
                'assignee_type_label' => $a->assignee_type->label(),
                'user'                => $a->user ? ['id' => $a->user->id, 'name' => $a->user->name, 'avatar' => $a->user->avatar] : null,
                'contractor_name'     => $a->contractor_name,
                'contractor_contact'  => $a->contractor_contact,
                'assigned_date'       => $a->assigned_date->toDateString(),
                'estimated_completion' => $a->estimated_completion?->toDateString(),
                'actual_completion'   => $a->actual_completion?->toDateString(),
                'remarks'             => $a->remarks,
                'is_primary'          => $a->is_primary,
            ]),
            'comments' => $ticket->comments->map(fn ($c) => [
                'id'           => $c->id,
                'comment_type' => $c->comment_type->value,
                'comment_type_label' => $c->comment_type->label(),
                'body'         => $c->body,
                'is_pinned'    => $c->is_pinned,
                'user'         => $c->user ? ['id' => $c->user->id, 'name' => $c->user->name, 'avatar' => $c->user->avatar] : null,
                'attachments'  => $c->attachments->map(fn ($a) => [
                    'id'        => $a->id,
                    'name'      => $a->name,
                    'url'       => $a->url,
                    'mime_type' => $a->mime_type,
                    'is_image'  => $a->is_image,
                    'is_video'  => $a->is_video,
                ]),
                'created_at'   => $c->created_at->toDateTimeString(),
            ]),
            'attachments' => $ticket->attachments->map(fn ($a) => [
                'id'                   => $a->id,
                'name'                 => $a->name,
                'url'                  => $a->url,
                'mime_type'            => $a->mime_type,
                'file_size_formatted'  => $a->file_size_formatted,
                'is_image'             => $a->is_image,
                'is_video'             => $a->is_video,
                'uploaded_by'          => $a->uploadedBy?->name ?? '—',
                'created_at'           => $a->created_at->toDateTimeString(),
            ]),
            'costs' => $ticket->costs->map(fn ($c) => [
                'id'            => $c->id,
                'cost_type'     => $c->cost_type->value,
                'cost_type_label' => $c->cost_type->label(),
                'description'   => $c->description,
                'amount'        => (float) $c->amount,
                'status'        => $c->status->value,
                'status_label'  => $c->status->label(),
                'status_color'  => $c->status->color(),
                'added_by'      => $c->addedBy?->name ?? '—',
                'approved_by'   => $c->approvedBy?->name,
                'approved_at'   => $c->approved_at?->toDateTimeString(),
                'created_at'    => $c->created_at->toDateTimeString(),
            ]),
            'histories' => $ticket->histories->map(fn ($h) => [
                'id'          => $h->id,
                'event_type'  => $h->event_type,
                'description' => $h->description,
                'metadata'    => $h->metadata,
                'occurred_at' => $h->occurred_at->toDateTimeString(),
                'created_by'  => $h->createdBy?->name ?? 'System',
            ]),
            'rating' => $ticket->rating ? [
                'id'               => $ticket->rating->id,
                'rating'           => $ticket->rating->rating,
                'feedback'         => $ticket->rating->feedback,
                'would_recommend'  => $ticket->rating->would_recommend,
                'rated_at'         => $ticket->rating->rated_at->toDateTimeString(),
            ] : null,
        ];
    }
}
