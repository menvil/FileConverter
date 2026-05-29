<?php

namespace App\Support\Files;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\Date;

final class FileExpirationPolicy
{
    public function forUploadedFile(User $user): CarbonInterface
    {
        return Date::now()->addDay();
    }

    public function forResultFile(User $user): CarbonInterface
    {
        return Date::now()->addDay();
    }
}
