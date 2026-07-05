<x-guest-layout>
    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center">
            <x-icon name="mail" class="w-7 h-7" />
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Verify your email</h2>
        <p class="text-sm text-slate-500 mt-2">We sent a verification link to <strong class="text-slate-700">{{ auth()->user()->email }}</strong>. Click the link to activate your account.</p>
    </div>

    <div class="space-y-4">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <x-primary-button class="w-full">Resend Verification Link</x-primary-button>
        </form>

        <a href="{{ route('verification.otp') }}" class="btn-outline w-full text-center block text-sm">
            Use OTP code instead
        </a>

        <form method="POST" action="{{ route('logout') }}" class="text-center pt-2">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-red-600 transition-colors">Log out</button>
        </form>
    </div>
</x-guest-layout>
