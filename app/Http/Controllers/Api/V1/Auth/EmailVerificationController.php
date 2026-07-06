<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Services\EmailVerificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EmailVerificationController extends ApiController
{
    public function verify(VerifyOtpRequest $request, EmailVerificationService $verification): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success([
                'message' => 'Email is already verified.',
                'user' => new UserResource($user),
            ]);
        }

        if (! $verification->verifyOtp($user, $request->otp)) {
            return $this->error('Invalid or expired verification code.', 422, [
                'otp' => ['Invalid or expired verification code.'],
            ]);
        }

        return $this->success([
            'message' => 'Email verified successfully.',
            'user' => new UserResource($user->fresh()),
        ]);
    }

    public function resend(Request $request, EmailVerificationService $verification): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return $this->success(['message' => 'Email is already verified.']);
        }

        try {
            $verification->sendOtp($user);
        } catch (\Throwable) {
            return $this->error('Could not send verification code. Please try again later.', 503);
        }

        return $this->success(['message' => 'A new verification code has been sent to your email.']);
    }
}
