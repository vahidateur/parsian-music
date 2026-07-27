<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    /**
     * Handles both the "info" section and "avatar" section via hidden _section field.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user    = $request->user();
        $section = $request->input('_section', 'info');

        if ($section === 'avatar') {
            if ($request->hasFile('avatar')) {
                // Remove old avatar
                if ($user->avatar_path) {
                    Storage::disk('public')->delete($user->avatar_path);
                }
                $path = $request->file('avatar')->store('avatars', 'public');
                $user->update(['avatar_path' => $path]);
            }

            return Redirect::route('profile.edit')->with('status', 'profile-updated');
        }

        // ── info section ──────────────────────────────────────────────────
        $user->fill($request->only('full_name', 'phone', 'email', 'locale', 'timezone'));

        // Note: email_verified_at intentionally not set — column not in use.

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password'         => ['required', 'confirmed', Password::min(8)],
        ]);

        $request->user()->update([
            'password'              => Hash::make($validated['password']),
            'force_password_change' => false,
        ]);

        return Redirect::route('profile.edit')->with('status', 'password-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        // Clean up avatar
        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
