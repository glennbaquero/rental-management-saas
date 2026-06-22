<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Http\Controllers\Controller;
use App\Models\LateFee;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LateFeeController extends Controller
{
    public function index(Request $request): Response
    {
        $lateFees = LateFee::query()
            ->with(['invoice', 'lease.rentalTenant', 'lease.unit.property'])
            ->when($request->search, function ($query, $value) {
                $query->whereHas('invoice', fn ($q) => $q->where('invoice_number', 'like', "%{$value}%"))
                    ->orWhereHas('lease.rentalTenant', fn ($q) =>
                        $q->where('first_name', 'like', "%{$value}%")
                          ->orWhere('last_name', 'like', "%{$value}%")
                    );
            })
            ->latest('applied_at')
            ->paginate(20)
            ->withQueryString()
            ->through(fn (LateFee $fee) => [
                'id'             => $fee->id,
                'invoice_number' => $fee->invoice?->invoice_number,
                'invoice_id'     => $fee->invoice_id,
                'tenant_name'    => $fee->lease?->rentalTenant?->full_name ?? '—',
                'property_name'  => $fee->lease?->unit?->property?->name ?? '—',
                'type'           => $fee->type->value,
                'type_label'     => $fee->type->label(),
                'rate'           => (float) $fee->rate,
                'amount'         => (float) $fee->amount,
                'days_overdue'   => $fee->days_overdue,
                'applied_at'     => $fee->applied_at->toISOString(),
            ]);

        $totalCollected = LateFee::sum('amount');

        return Inertia::render('billing/LateFees', [
            'lateFees'       => $lateFees,
            'filters'        => $request->only(['search']),
            'totalCollected' => (float) $totalCollected,
        ]);
    }
}
