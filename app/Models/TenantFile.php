<?php

namespace App\Models;

use App\Enums\TenantFileType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property string $id
 * @property string $rental_tenant_id
 * @property string $name
 * @property TenantFileType $type
 * @property string $file_path
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property string|null $uploaded_by
 */
#[Fillable(['rental_tenant_id', 'name', 'type', 'file_path', 'file_size', 'mime_type', 'uploaded_by'])]
class TenantFile extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => TenantFileType::class,
        ];
    }

    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->file_path);
    }

    public function getFileSizeFormattedAttribute(): string
    {
        $bytes = $this->file_size ?? 0;
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2) . ' MB';
        }
        return round($bytes / 1024, 2) . ' KB';
    }

    public function rentalTenant(): BelongsTo
    {
        return $this->belongsTo(RentalTenant::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
