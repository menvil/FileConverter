<?php

declare(strict_types=1);

namespace App\Actions\Settings;

use App\Models\User;

final class UpdateProfileNameAction
{
    public function handle(User $user, string $name): void
    {
        $user->forceFill([
            'name' => trim($name),
        ])->save();
    }
}
