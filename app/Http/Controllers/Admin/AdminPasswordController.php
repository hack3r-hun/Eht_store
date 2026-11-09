<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminPasswordController extends Controller
{
    /** Lets the signed-in admin change their own password from Settings. */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validateWithBag('updatePassword', [
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', 'different:current_password', Password::defaults()],
        ], [
            'current_password.current_password' => 'The current password you entered is not correct.',
            'password.different' => 'Your new password must be different from the current one.',
        ]);

        $request->user()->update(['password' => Hash::make($validated['password'])]);

        return redirect()->route('admin.settings.index')->with('success', 'Your password was changed.');
    }

    /** Human-readable version of the active password rules, shown next to the form. */
    public static function requirementsHint(): string
    {
        return app()->environment('production')
            ? 'At least 10 characters with upper and lower case letters, a number and a symbol.'
            : 'At least 8 characters.';
    }
}
