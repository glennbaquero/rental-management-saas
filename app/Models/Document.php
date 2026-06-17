<?php

namespace App\Models;

use App\Enums\DocumentType;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $documentable_type
 * @property string $documentable_id
 * @property string $name
 * @property DocumentType $type
 * @property string $file_path
 * @property int|null $file_size
 * @property string|null $mime_type
 * @property bool $is_signed
 * @property Carbon|null $signed_at
 * @property string|null $uploaded_by
 */
#[Fillable(['documentable_type', 'documentable_id', 'name', 'type', 'file_path', 'file_size', 'mime_type', 'is_signed', 'signed_at', 'uploaded_by'])]
class Document extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'type' => DocumentType::class,
            'is_signed' => 'boolean',
            'signed_at' => 'datetime',
        ];
    }

    public function documentable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }
}
