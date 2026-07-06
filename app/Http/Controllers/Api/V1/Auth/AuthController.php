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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
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

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

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
        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $cartService->mergeGuestCart($user->id, $request->header('X-Guest-Cart-Token'));

        $token = $user->createToken($request->input('device_name', 'api'))->plainTextToken;

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
}
