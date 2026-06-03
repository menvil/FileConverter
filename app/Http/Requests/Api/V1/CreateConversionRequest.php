<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class CreateConversionRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file_id' => ['required', 'integer'],
            'target_format' => ['required', 'string'],
            'options' => ['sometimes', 'array'],
        ];
    }
}
