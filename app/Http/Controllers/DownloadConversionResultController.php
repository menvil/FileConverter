<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ConversionStatus;
use App\Models\ConversionJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

final class DownloadConversionResultController extends Controller
{
    public function __invoke(Request $request, ConversionJob $conversion)
    {
        abort_unless($conversion->user_id === $request->user()->id, 403);
        abort_unless($conversion->status === ConversionStatus::Completed, 404);
        abort_unless($conversion->resultFile !== null, 404);

        $file = $conversion->resultFile;

        if ($file->expires_at !== null && $file->expires_at->isPast()) {
            abort(410);
        }

        abort_unless(Storage::disk('local')->exists($file->stored_path), 404);

        return Storage::disk('local')->download(
            $file->stored_path,
            $file->original_name,
            ['Content-Type' => $file->mime_type],
        );
    }
}
