<?php

namespace App\Models;

use App\Enums\FileStatus;
use Database\Factories\FileRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'user_id',
    'original_name',
    'stored_path',
    'mime_type',
    'extension',
    'size_bytes',
    'checksum',
    'metadata_json',
    'status',
    'expires_at',
])]
class FileRecord extends Model
{
    /** @use HasFactory<FileRecordFactory> */
    use HasFactory;

    protected $table = 'files';

    protected function casts(): array
    {
        return [
            'metadata_json' => 'array',
            'status' => FileStatus::class,
            'expires_at' => 'datetime',
            'size_bytes' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
