<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CreditAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CreditAccount extends Model
{
    /** @use HasFactory<CreditAccountFactory> */
    use HasFactory;

    protected $fillable = ['user_id', 'balance'];

    protected function casts(): array
    {
        return [
            'balance' => 'integer',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
