<x-guest-layout>
    <div class="text-center mb-6">
        <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-brand-100 text-brand-600 flex items-center justify-center">
            <x-icon name="check-circle" class="w-7 h-7" />
        </div>
        <h2 class="text-2xl font-bold text-slate-900">Enter verification code</h2>
        <p class="text-sm text-slate-500 mt-2">We sent a 6-digit code to <strong class="text-slate-700">{{ auth()->user()->email }}</strong></p>
    </div>

    @if(session('dev_otp'))
        <div class="mb-6 p-4 rounded-xl bg-brand-50 border border-brand-200 text-center">
            <p class="text-xs font-semibold text-brand-800 uppercase tracking-wide mb-1">Development — your OTP code</p>
            <p class="text-3xl font-bold text-brand-900 tracking-[0.3em]">{{ session('dev_otp') }}</p>
            <p class="text-xs text-brand-700 mt-2">Copy this code below. In production it will only be sent by email.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('verification.otp.verify') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="otp" value="Verification Code" />
            <x-text-input id="otp" class="block mt-1 w-full input-field text-center text-2xl tracking-[0.5em] font-bold" type="text" name="otp" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus placeholder="000000" autocomplete="one-time-code" />
            <x-input-error :messages="$errors->get('otp')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">Verify Email</x-primary-button>
    </form>

    <div class="mt-6 space-y-3">
        <form method="POST" action="{{ route('verification.otp.resend') }}">
            @csrf
            <button type="submit" class="w-full text-sm font-semibold text-brand-700 hover:text-brand-800">Resend Code</button>
        </form>

        <a href="{{ route('verification.notice') }}" class="btn-outline w-full text-center block text-sm">
            Use email link instead
        </a>

        <form method="POST" action="{{ route('logout') }}" class="text-center pt-2">
            @csrf
            <button type="submit" class="text-sm text-slate-500 hover:text-red-600 transition-colors">Log out</button>
        </form>
    </div>
</x-guest-layout>
