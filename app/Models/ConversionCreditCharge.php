<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConversionCreditChargeStatus;
use Database\Factories\ConversionCreditChargeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionCreditCharge extends Model
{
    /** @use HasFactory<ConversionCreditChargeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'conversion_job_id',
        'estimated_amount',
        'captured_amount',
        'refunded_amount',
        'status',
        'breakdown_json',
    ];

    protected $casts = [
        'status' => ConversionCreditChargeStatus::class,
        'breakdown_json' => 'array',
        'estimated_amount' => 'integer',
        'captured_amount' => 'integer',
        'refunded_amount' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function conversionJob(): BelongsTo
    {
        return $this->belongsTo(ConversionJob::class);
    }
}
