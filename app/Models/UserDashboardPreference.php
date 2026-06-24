<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserDashboardPreference extends Model
{
    use HasUuids;

    protected $fillable = [
        'user_id',
        'widget_key',
        'is_visible',
        'order',
    ];

    protected function casts(): array
    {
        return [
            'is_visible' => 'boolean',
            'order'      => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
