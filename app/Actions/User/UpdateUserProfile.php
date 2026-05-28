<?php

namespace App\Actions\User;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;

class UpdateUserProfile
{
    public function execute(User $user, ProfileUpdateRequest $request): void
    {
        $user->fill($request->validated());

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();
    }
}
