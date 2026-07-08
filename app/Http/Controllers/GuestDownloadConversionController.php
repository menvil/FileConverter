<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class GuestDownloadConversionController extends Controller
{
    public function __invoke(Request $request, int $conversionId)
    {
        $token = $request->session()->get('guest_converter_token');

        abort_if(empty($token), 403);

        $conversion = ConversionJob::query()
            ->where('id', $conversionId)
            ->where('guest_token', $token)
            ->first();

        abort_if($conversion === null, 404);
        abort_unless($conversion->status === ConversionStatus::Completed, 404);
        abort_unless($conversion->resultFile !== null, 404);

        $file = $conversion->resultFile;

        if ($file->isExpired()) {
            abort(410, 'This conversion result has expired.');
        }

        abort_unless(Storage::disk('local')->exists($file->stored_path), 404);

        return Storage::disk('local')->download(
            $file->stored_path,
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }
}
