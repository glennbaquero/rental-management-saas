<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property string $id
 * @property string $invoice_id
 * @property string $description
 * @property float $quantity
 * @property float $unit_price
 * @property float $total_price
 * @property string|null $type
 */
#[Fillable(['invoice_id', 'description', 'quantity', 'unit_price', 'total_price', 'type'])]
class InvoiceItem extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
