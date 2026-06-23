<?php

declare(strict_types=1);

namespace App\Services\Lease;

use App\Enums\LeaseRenewalStatus;
use App\Enums\LeaseStatus;
use App\Models\Lease;
use App\Models\LeaseHistory;
use App\Models\LeaseRenewal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class LeaseService
{
    public function activate(Lease $lease, ?string $userId = null): void
    {
        DB::transaction(function () use ($lease, $userId) {
            $lease->update(['status' => LeaseStatus::Active]);
            LeaseHistory::record($lease, 'activated', 'Lease activated.', [], $userId);
        });
    }

    public function markExpiringSoon(Lease $lease, int $daysRemaining, ?string $userId = null): void
    {
        DB::transaction(function () use ($lease, $daysRemaining, $userId) {
            $lease->update(['status' => LeaseStatus::ExpiringSoon]);
            LeaseHistory::record(
                $lease,
                'expiring_soon',
                "Lease marked as expiring soon ({$daysRemaining} days remaining).",
                ['days_remaining' => $daysRemaining],
                $userId
            );
        });
    }

    public function expire(Lease $lease, ?string $userId = null): void
    {
        DB::transaction(function () use ($lease, $userId) {
            $lease->update(['status' => LeaseStatus::Expired]);
            LeaseHistory::record($lease, 'expired', 'Lease automatically expired.', [], $userId);
        });
    }

    public function terminate(Lease $lease, array $data, ?string $userId = null): void
    {
        DB::transaction(function () use ($lease, $data, $userId) {
            $lease->update([
                'status'               => LeaseStatus::Terminated,
                'termination_date'     => $data['termination_date'],
                'termination_reason'   => $data['termination_reason'],
                'move_out_date'        => $data['move_out_date'] ?? $data['termination_date'],
                'notes'                => $data['notes'] ?? $lease->notes,
            ]);
            LeaseHistory::record(
                $lease,
                'terminated',
                "Lease terminated. Reason: {$data['termination_reason']}.",
                ['termination_date' => $data['termination_date'], 'reason' => $data['termination_reason']],
                $userId
            );
        });
    }

    public function renew(Lease $lease, array $data, ?string $userId = null): LeaseRenewal
    {
        return DB::transaction(function () use ($lease, $data, $userId) {
            $renewal = LeaseRenewal::create([
                'lease_id'            => $lease->id,
                'previous_start_date' => $lease->start_date,
                'previous_end_date'   => $lease->end_date,
                'new_end_date'        => $data['new_end_date'],
                'new_rent_amount'     => $data['new_rent_amount'],
                'renewal_date'        => Carbon::today(),
                'renewal_status'      => LeaseRenewalStatus::Completed,
                'reason'              => $data['reason'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_by'          => $userId,
                'renewed_by'          => $userId,
            ]);

            $lease->update([
                'end_date'    => $data['new_end_date'],
                'rent_amount' => $data['new_rent_amount'],
                'status'      => LeaseStatus::Renewed,
            ]);

            LeaseHistory::record(
                $lease,
                'renewed',
                "Lease renewed until {$data['new_end_date']}. New rent: {$data['new_rent_amount']}.",
                ['new_end_date' => $data['new_end_date'], 'new_rent_amount' => $data['new_rent_amount']],
                $userId
            );

            return $renewal;
        });
    }

    public function generateLeaseNumber(): string
    {
        $year = now()->year;
        $prefix = 'LSE';

        $lastNumber = Lease::withTrashed()
            ->where('lease_number', 'like', "{$prefix}-{$year}-%")
            ->lockForUpdate()
            ->count();

        $sequence = str_pad((string) ($lastNumber + 1), 5, '0', STR_PAD_LEFT);

        return "{$prefix}-{$year}-{$sequence}";
    }
}
