<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Exceptions\Conversions\ConversionResultExpiredException;
use App\Models\ConversionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class DownloadConversionResultController extends Controller
{
    public function __invoke(Request $request, ConversionJob $conversion)
    {
        abort_unless((int) $conversion->user_id === (int) $request->user()->id, 403);
        abort_unless($conversion->status === ConversionStatus::Completed, 404);
        abort_unless($conversion->resultFile !== null, 404);

        $file = $conversion->resultFile;

        if ($file->isExpired()) {
            throw ConversionResultExpiredException::forConversion((string) $conversion->id);
        }

        abort_unless(Storage::disk('local')->exists($file->stored_path), 404);

        return Storage::disk('local')->download(
            $file->stored_path,
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }
}
