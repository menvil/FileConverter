<?php

declare(strict_types=1);

namespace App\Enums;

enum ConversionCreditChargeStatus: string
{
    case Estimated = 'estimated';
    case Captured = 'captured';
    case Refunded = 'refunded';
    case Failed = 'failed';
}
