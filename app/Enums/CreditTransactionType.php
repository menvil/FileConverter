<?php

declare(strict_types=1);

namespace App\Enums;

enum CreditTransactionType: string
{
    case Grant = 'grant';
    case Purchase = 'purchase';
    case Spend = 'spend';
    case Refund = 'refund';
    case Adjustment = 'adjustment';
    case Expiration = 'expiration';
}
