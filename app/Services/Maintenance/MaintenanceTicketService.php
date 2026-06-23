<?php

declare(strict_types=1);

namespace App\Services\Maintenance;

use App\Enums\MaintenanceStatus;
use App\Jobs\Maintenance\CheckOverdueTicketsJob;
use App\Jobs\Maintenance\SendMaintenanceNotificationJob;
use App\Jobs\Maintenance\SendResolutionNotificationJob;
use App\Jobs\Maintenance\SendTicketAssignmentJob;
use App\Models\MaintenanceAssignment;
use App\Models\MaintenanceComment;
use App\Models\MaintenanceCost;
use App\Models\MaintenanceHistory;
use App\Models\MaintenanceTicket;
use Illuminate\Support\Facades\DB;

class MaintenanceTicketService
{
    public function createTicket(array $data, string $userId): MaintenanceTicket
    {
        return DB::transaction(function () use ($data, $userId) {
            $ticket = MaintenanceTicket::create([
                ...$data,
                'ticket_number' => MaintenanceTicket::generateTicketNumber(),
                'status'        => MaintenanceStatus::Open,
                'date_submitted' => now(),
                'created_by'    => $userId,
                'updated_by'    => $userId,
            ]);

            MaintenanceHistory::record($ticket, 'created', 'Ticket created.', [], $userId);

            SendMaintenanceNotificationJob::dispatch($ticket);

            return $ticket;
        });
    }

    public function assignTicket(MaintenanceTicket $ticket, array $data, string $userId): MaintenanceAssignment
    {
        return DB::transaction(function () use ($ticket, $data, $userId) {
            if ($data['is_primary'] ?? false) {
                $ticket->assignments()->where('is_primary', true)->update(['is_primary' => false]);
            }

            $assignment = $ticket->assignments()->create([
                ...$data,
                'created_by' => $userId,
            ]);

            $this->updateStatus($ticket, MaintenanceStatus::Assigned, $userId, 'Ticket assigned to staff.');

            $assigneeName = $assignment->user?->name ?? $assignment->contractor_name ?? 'staff';
            MaintenanceHistory::record(
                $ticket,
                'assigned',
                "Assigned to {$assigneeName}.",
                ['assignment_id' => $assignment->id, 'assignee' => $assigneeName],
                $userId
            );

            SendTicketAssignmentJob::dispatch($ticket, $assignment);

            return $assignment;
        });
    }

    public function updateStatus(
        MaintenanceTicket $ticket,
        MaintenanceStatus $newStatus,
        string $userId,
        string $note = ''
    ): void {
        $oldStatus = $ticket->status;

        if ($oldStatus === $newStatus) {
            return;
        }

        $ticket->update([
            'status'     => $newStatus,
            'updated_by' => $userId,
        ]);

        MaintenanceHistory::record(
            $ticket,
            'status_changed',
            $note ?: "Status changed from {$oldStatus->label()} to {$newStatus->label()}.",
            ['old_status' => $oldStatus->value, 'new_status' => $newStatus->value],
            $userId
        );
    }

    public function resolveTicket(MaintenanceTicket $ticket, string $userId): void
    {
        DB::transaction(function () use ($ticket, $userId) {
            $ticket->update([
                'resolved_at' => now(),
                'updated_by'  => $userId,
            ]);

            $this->updateStatus($ticket, MaintenanceStatus::Resolved, $userId, 'Ticket resolved.');
        });
    }

    public function completeTicket(MaintenanceTicket $ticket, string $userId): void
    {
        DB::transaction(function () use ($ticket, $userId) {
            $ticket->update([
                'completed_at' => now(),
                'updated_by'   => $userId,
            ]);

            $this->updateStatus($ticket, MaintenanceStatus::Completed, $userId, 'Ticket completed.');

            SendResolutionNotificationJob::dispatch($ticket);
        });
    }

    public function addComment(MaintenanceTicket $ticket, array $data, string $userId): MaintenanceComment
    {
        $comment = $ticket->comments()->create([
            ...$data,
            'user_id' => $userId,
        ]);

        MaintenanceHistory::record(
            $ticket,
            'comment_added',
            'A comment was added.',
            ['comment_id' => $comment->id, 'comment_type' => $comment->comment_type->value],
            $userId
        );

        return $comment;
    }

    public function addCost(MaintenanceTicket $ticket, array $data, string $userId): MaintenanceCost
    {
        return $ticket->costs()->create([
            ...$data,
            'added_by' => $userId,
        ]);
    }
}
