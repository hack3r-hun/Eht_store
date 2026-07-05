<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'verification_method' => ['required', 'in:link,otp'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->forceFill(['verification_method' => $request->verification_method])->save();

        $user->assignRole('customer');

        Auth::login($user);

        try {
            if ($request->verification_method === 'otp') {
                $verification->sendOtp($user);

                return redirect()->route('verification.otp')
                    ->with('success', 'Account created! Enter the 6-digit code below.');
            }

            $verification->sendLink($user);

            return redirect()->route('verification.notice')
                ->with('success', 'Account created! Click the verification link sent to your email.');
        } catch (\Throwable) {
            return redirect()->route('verification.notice')
                ->with('info', 'Account created! Email could not be sent — use resend below or contact support.');
        }
    }
}
