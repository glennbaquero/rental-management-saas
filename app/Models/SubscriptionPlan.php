<?php

namespace App\Models;

use App\Enums\BillingCycle;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property string $id
 * @property string $name
 * @property string $slug
 * @property string|null $description
 * @property float $price
 * @property BillingCycle $billing_cycle
 * @property int|null $max_properties
 * @property int|null $max_units
 * @property int|null $max_users
 * @property array|null $features
 * @property bool $is_active
 */
#[Fillable(['name', 'slug', 'description', 'price', 'billing_cycle', 'max_properties', 'max_units', 'max_users', 'features', 'is_active', 'stripe_price_id'])]
class SubscriptionPlan extends Model
{
    use HasUuids;

    protected $connection = 'mysql';

    protected function casts(): array
    {
        return [
            'billing_cycle' => BillingCycle::class,
            'price' => 'decimal:2',
            'features' => 'array',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(TenantSubscription::class, 'plan_id');
    }
}
