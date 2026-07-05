<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\EmailVerificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EmailOtpVerificationController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route($request->user()->isAdmin() ? 'admin.dashboard' : 'home')
                ->with('success', 'Your email is already verified.');
        }

        return view('auth.verify-email-otp');
    }

    public function verify(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        $request->validate([
            'otp' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        if ($verification->verifyOtp($request->user(), $request->otp)) {
            return redirect()->route('home')->with('success', 'Email verified successfully! Welcome to '.shop_name().'.');
        }

        return back()->with('error', 'Invalid or expired code. Please try again or request a new one.');
    }

    public function resend(Request $request, EmailVerificationService $verification): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->route('home');
        }

        try {
            $verification->sendOtp($request->user());
        } catch (\Throwable) {
            if (config('app.debug') && session('dev_otp')) {
                return back()->with('success', 'Email failed but your dev OTP is shown above.');
            }

            return back()->with('error', 'Could not send OTP email. Please check mail settings or try the verification link option.');
        }

        return back()->with('success', config('app.debug')
            ? 'New code generated — see the yellow box above.'
            : 'A new verification code has been sent to your email.');
    }
}
