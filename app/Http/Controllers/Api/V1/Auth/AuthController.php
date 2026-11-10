<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\CartService;
use App\Services\EmailVerificationService;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function register(RegisterRequest $request, EmailVerificationService $verification, CartService $cartService): JsonResponse
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => Hash::make($request->password),
        ]);

        $user->forceFill([
            'verification_method' => $request->input('verification_method', 'otp'),
        ])->save();

        $user->assignRole('customer');

        $cartService->mergeGuestCart($user->id, $request->header('X-Guest-Cart-Token'));

        $token = $user->createToken(
            $request->input('device_name', 'api'),
            $this->tokenAbilities($user)
        )->plainTextToken;

        try {
            if ($user->verification_method === 'otp') {
                $verification->sendOtp($user);
            } else {
                $verification->sendLink($user);
            }
        } catch (\Throwable) {
            //
        }

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'email_verification_required' => $user->requiresEmailVerification(),
        ], 201);
    }

    public function login(LoginRequest $request, CartService $cartService): JsonResponse
    {
        // The route-level "auth-api" limiter is keyed by IP only, so a single attacker IP
        // could otherwise brute-force one account indefinitely. This mirrors the web login's
        // per-account lockout (LoginRequest::ensureIsNotRateLimited), keyed by email + IP.
        $throttleKey = Str::transliterate(Str::lower($request->string('email')).'|'.$request->ip());

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            event(new Lockout($request));

            Log::channel('security')->warning('api_login_locked_out', [
                'email' => Str::lower($request->string('email')),
                'ip' => $request->ip(),
            ]);

            $seconds = RateLimiter::availableIn($throttleKey);

            throw ValidationException::withMessages([
                'email' => ["Too many login attempts. Please try again in {$seconds} seconds."],
            ]);
        }

        $user = User::where('email', $request->email)->first();

        // Always run a hash check so response time doesn't reveal whether the email exists.
        $hash = $user?->password ?? '$2y$12$usesomesillystringfore7hnbRJHxXVLeakoG8K30oukPsA.ztMG';

        if (! Hash::check($request->password, $hash) || ! $user) {
            RateLimiter::hit($throttleKey);

            Log::channel('security')->warning('api_login_failed', [
                'email' => Str::lower($request->string('email')),
                'ip' => $request->ip(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        RateLimiter::clear($throttleKey);

        $cartService->mergeGuestCart($user->id, $request->header('X-Guest-Cart-Token'));

        $token = $user->createToken(
            $request->input('device_name', 'api'),
            $this->tokenAbilities($user)
        )->plainTextToken;

        return $this->success([
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => new UserResource($user),
            'email_verification_required' => $user->requiresEmailVerification(),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return $this->success(['message' => 'Logged out successfully.']);
    }

    public function user(Request $request): JsonResponse
    {
        return $this->success(new UserResource($request->user()));
    }

    /**
     * @return list<string>
     */
    protected function tokenAbilities(User $user): array
    {
        return $user->hasRole('admin')
            ? ['admin', 'customer']
            : ['customer'];
    }
}
