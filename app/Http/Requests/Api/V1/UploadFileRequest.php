<?php

declare(strict_types=1);

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

final class UploadFileRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'file' => ['required', 'file'],
        ];
    }
}
