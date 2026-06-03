<?php

namespace App\Models;

use App\Enums\FileStatus;
use Database\Factories\FileRecordFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function isExpired(): bool
    {
        return $this->status === FileStatus::Expired
            || ($this->expires_at !== null && $this->expires_at->isPast());
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeForUser(Builder $query, User $user): Builder
    {
        return $query->where('user_id', $user->id);
    }

    public function sourceConversionJobs(): HasMany
    {
        return $this->hasMany(ConversionJob::class, 'source_file_id');
    }

    public function resultConversionJobs(): HasMany
    {
        return $this->hasMany(ConversionJob::class, 'result_file_id');
    }
}
