<?php

namespace App\Http\Controllers;

use App\Actions\User\DeleteUserAccount;
use App\Actions\User\UpdateUserProfile;
use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
        ]);
    }

    public function update(ProfileUpdateRequest $request, UpdateUserProfile $action): RedirectResponse
    {
        $action->execute($request->user(), $request);

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request, DeleteUserAccount $action): RedirectResponse
    {
        $action->execute($request);

        return Redirect::to('/');
    }
}
