<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class EmailVerificationService
{
    public function sendOtp(User $user): void
    {
        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        Cache::forget('otp_attempts:'.$user->id);

        $user->forceFill([
            'verification_method' => 'otp',
            'email_otp' => Hash::make($otp),
            'email_otp_expires_at' => now()->addMinutes(15),
        ])->save();

        try {
            Mail::to($user->email)->send(new OtpVerificationMail($otp, $user));
        } catch (\Throwable $e) {
            Log::warning('OTP email failed: '.$e->getMessage(), ['user_id' => $user->id]);

            // Only local dev may surface the OTP on screen — never tie this to
            // APP_DEBUG, which could accidentally be enabled in production.
            if (app()->environment('local')) {
                session(['dev_otp' => $otp]);

                return;
            }

            throw $e;
        }

        if (app()->environment('local')) {
            session(['dev_otp' => $otp]);
        }
    }

    public function sendLink(User $user): void
    {
        $user->forceFill(['verification_method' => 'link'])->save();
        $user->sendEmailVerificationNotification();
    }

    public const MAX_OTP_ATTEMPTS = 5;

    public function verifyOtp(User $user, string $otp): bool
    {
        // A 6-digit code is only safe with an attempt cap: after 5 wrong guesses the code is
        // burned and a new one must be requested (which is itself rate limited).
        $attemptsKey = 'otp_attempts:'.$user->id;

        if (Cache::get($attemptsKey, 0) >= self::MAX_OTP_ATTEMPTS) {
            $user->forceFill(['email_otp' => null, 'email_otp_expires_at' => null])->save();
            Cache::forget($attemptsKey);

            return false;
        }

        if (! $user->email_otp || ! $user->email_otp_expires_at || $user->email_otp_expires_at->isPast()) {
            return false;
        }

        if (! Hash::check($otp, $user->email_otp)) {
            Cache::put($attemptsKey, Cache::get($attemptsKey, 0) + 1, now()->addMinutes(15));

            return false;
        }

        Cache::forget($attemptsKey);

        $user->forceFill([
            'email_verified_at' => now(),
            'email_otp' => null,
            'email_otp_expires_at' => null,
        ])->save();

        return true;
    }
}
