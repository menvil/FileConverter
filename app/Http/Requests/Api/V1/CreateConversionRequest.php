<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

final class CreateConversionRequest extends ConversionRequestBase
{
    public function rules(): array
    {
        return $this->conversionRules();
    }
}
