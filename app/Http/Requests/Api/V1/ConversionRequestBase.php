<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

abstract class ConversionRequestBase extends FormRequest
{
    protected function conversionRules(): array
    {
        return [
            'file_id' => ['required', 'integer'],
            'target_format' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
        ];
    }
}
