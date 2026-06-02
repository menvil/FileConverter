<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversionStatus;
use Database\Factories\ConversionJobFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property int $source_file_id
 * @property int|null $result_file_id
 * @property string $source_format
 * @property string $target_format
 * @property string $converter_key
 * @property array<string, mixed> $options_json
 * @property ConversionStatus $status
 * @property bool $is_starred
 * @property int $progress
 * @property string|null $error_code
 * @property string|null $error_message
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
#[Fillable([
    'user_id',
    'source_file_id',
    'result_file_id',
    'source_format',
    'target_format',
    'converter_key',
    'options_json',
    'status',
    'is_starred',
    'progress',
    'error_code',
    'error_message',
    'started_at',
    'completed_at',
    'expires_at',
])]
class ConversionJob extends Model
{
    /** @use HasFactory<ConversionJobFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => ConversionStatus::class,
            'is_starred' => 'boolean',
            'options_json' => 'array',
            'progress' => 'integer',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function sourceFile(): BelongsTo
    {
        return $this->belongsTo(FileRecord::class, 'source_file_id');
    }

    public function resultFile(): BelongsTo
    {
        return $this->belongsTo(FileRecord::class, 'result_file_id');
    }

    public function creditCharge(): HasOne
    {
        return $this->hasOne(ConversionCreditCharge::class);
    }

    public function isCompleted(): bool
    {
        return $this->status === ConversionStatus::Completed;
    }
}
