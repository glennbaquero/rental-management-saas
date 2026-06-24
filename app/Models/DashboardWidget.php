<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class DashboardWidget extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'key',
        'description',
        'roles',
        'is_active',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'roles'     => 'array',
            'is_active' => 'boolean',
            'order'     => 'integer',
        ];
    }

    public function scopeForRole(self $query, string $role): self
    {
        return $query->whereJsonContains('roles', $role)->where('is_active', true);
    }
}
