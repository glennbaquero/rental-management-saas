<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lease Expiration Reminder</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif; background: #f9fafb; margin: 0; padding: 0; }
        .container { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 8px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
        .header { background: #1e293b; color: #fff; padding: 32px 40px; }
        .header h1 { margin: 0; font-size: 22px; font-weight: 600; }
        .header p { margin: 4px 0 0; font-size: 14px; color: #94a3b8; }
        .body { padding: 32px 40px; }
        .badge { display: inline-block; background: #fef3c7; color: #92400e; padding: 4px 12px; border-radius: 999px; font-size: 13px; font-weight: 600; margin-bottom: 20px; }
        .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #f1f5f9; font-size: 14px; }
        .info-row:last-child { border-bottom: none; }
        .label { color: #64748b; }
        .value { font-weight: 500; color: #0f172a; }
        .cta { margin-top: 28px; text-align: center; }
        .footer { padding: 20px 40px; background: #f8fafc; border-top: 1px solid #e2e8f0; font-size: 12px; color: #94a3b8; text-align: center; }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <h1>Lease Expiration Reminder</h1>
        <p>Action required — your lease is expiring soon</p>
    </div>
    <div class="body">
        <div class="badge">⏰ {{ $daysRemaining }} day(s) remaining</div>

        <p style="font-size:15px;color:#334155;margin:0 0 20px;">
            Dear {{ $tenant?->full_name ?? 'Tenant' }},<br><br>
            This is a reminder that your lease agreement <strong>{{ $lease->lease_number }}</strong> is set to expire
            on <strong>{{ $lease->end_date?->format('F j, Y') }}</strong>.
        </p>

        <div style="background:#f8fafc;border-radius:6px;padding:20px;margin-bottom:20px;">
            <div class="info-row">
                <span class="label">Lease Number</span>
                <span class="value">{{ $lease->lease_number }}</span>
            </div>
            @if($property)
            <div class="info-row">
                <span class="label">Property</span>
                <span class="value">{{ $property->name }}</span>
            </div>
            @endif
            @if($unit)
            <div class="info-row">
                <span class="label">Unit</span>
                <span class="value">{{ $unit->name ?? $unit->unit_number }}</span>
            </div>
            @endif
            <div class="info-row">
                <span class="label">Lease End Date</span>
                <span class="value">{{ $lease->end_date?->format('F j, Y') }}</span>
            </div>
            <div class="info-row">
                <span class="label">Monthly Rent</span>
                <span class="value">{{ $lease->currency }} {{ number_format($lease->rent_amount, 2) }}</span>
            </div>
        </div>

        <p style="font-size:14px;color:#64748b;">
            Please contact your property manager to discuss renewal options or to initiate the move-out process.
        </p>
    </div>
    <div class="footer">
        This is an automated reminder from your Rental Management System.
    </div>
</div>
</body>
</html>
