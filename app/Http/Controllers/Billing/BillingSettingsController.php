<?php

declare(strict_types=1);

namespace App\Http\Controllers\Billing;

use App\Enums\LateFeeType;
use App\Enums\ReminderChannel;
use App\Http\Controllers\Controller;
use App\Models\BillingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BillingSettingsController extends Controller
{
    public function edit(): Response
    {
        $settings = BillingSettings::first() ?? new BillingSettings([
            'currency'                  => 'PHP',
            'timezone'                  => 'Asia/Manila',
            'invoice_prefix'            => 'INV',
            'invoice_number_format'     => '{PREFIX}-{YEAR}-{SEQ5}',
            'grace_period_days'         => 3,
            'auto_generate_invoices'    => true,
            'auto_send_reminders'       => true,
            'late_fee_enabled'          => false,
            'late_fee_type'             => LateFeeType::Fixed->value,
            'late_fee_amount'           => 0,
            'late_fee_percentage'       => 0,
            'apply_late_fee_after_days' => 1,
            'compound_monthly'          => false,
            'reminder_days_before'      => [7, 3, 1],
            'reminder_channels'         => ['email', 'in_app'],
        ]);

        return Inertia::render('billing/settings/Index', [
            'settings'         => $this->transformSettings($settings),
            'lateFeeTypes'     => collect(LateFeeType::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
            'reminderChannels' => collect(ReminderChannel::cases())->map(fn ($c) => ['value' => $c->value, 'label' => $c->label()]),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'currency'                  => 'required|string|size:3',
            'timezone'                  => 'required|string|max:50',
            'invoice_prefix'            => 'required|string|max:10',
            'invoice_number_format'     => 'required|string|max:50',
            'grace_period_days'         => 'required|integer|min:0|max:30',
            'auto_generate_invoices'    => 'boolean',
            'auto_send_reminders'       => 'boolean',
            'late_fee_enabled'          => 'boolean',
            'late_fee_type'             => 'required|in:fixed,percentage',
            'late_fee_amount'           => 'required|numeric|min:0',
            'late_fee_percentage'       => 'required|numeric|min:0|max:100',
            'apply_late_fee_after_days' => 'required|integer|min:1|max:90',
            'compound_monthly'          => 'boolean',
            'reminder_days_before'      => 'nullable|array',
            'reminder_days_before.*'    => 'integer',
            'reminder_channels'         => 'nullable|array',
            'reminder_channels.*'       => 'in:email,sms,in_app',
        ]);

        $settings = BillingSettings::first();

        if ($settings) {
            $settings->update($data);
        } else {
            BillingSettings::create($data);
        }

        return back()->with('toast', ['type' => 'success', 'message' => 'Billing settings saved.']);
    }

    private function transformSettings(BillingSettings $settings): array
    {
        return [
            'currency'                  => $settings->currency ?? 'PHP',
            'timezone'                  => $settings->timezone ?? 'Asia/Manila',
            'invoice_prefix'            => $settings->invoice_prefix ?? 'INV',
            'invoice_number_format'     => $settings->invoice_number_format ?? '{PREFIX}-{YEAR}-{SEQ5}',
            'grace_period_days'         => $settings->grace_period_days ?? 3,
            'auto_generate_invoices'    => (bool) ($settings->auto_generate_invoices ?? true),
            'auto_send_reminders'       => (bool) ($settings->auto_send_reminders ?? true),
            'late_fee_enabled'          => (bool) ($settings->late_fee_enabled ?? false),
            'late_fee_type'             => $settings->late_fee_type?->value ?? LateFeeType::Fixed->value,
            'late_fee_amount'           => (float) ($settings->late_fee_amount ?? 0),
            'late_fee_percentage'       => (float) ($settings->late_fee_percentage ?? 0),
            'apply_late_fee_after_days' => $settings->apply_late_fee_after_days ?? 1,
            'compound_monthly'          => (bool) ($settings->compound_monthly ?? false),
            'reminder_days_before'      => $settings->reminder_days_before ?? [7, 3, 1],
            'reminder_channels'         => $settings->reminder_channels ?? ['email', 'in_app'],
        ];
    }
}
