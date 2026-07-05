<x-guest-layout>
    <h2 class="text-2xl font-bold text-slate-900 mb-2 text-center">Create Account</h2>
    <p class="text-sm text-slate-500 text-center mb-6">Join {{ shop_name() }} to track orders and save addresses.</p>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <div>
            <x-input-label for="name" :value="__('Full Name')" />
            <x-text-input id="name" class="block mt-1 w-full input-field" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full input-field" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="phone" :value="__('Phone (optional)')" />
            <x-text-input id="phone" class="block mt-1 w-full input-field" type="text" name="phone" :value="old('phone')" autocomplete="tel" />
            <x-input-error :messages="$errors->get('phone')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('Password')" />
            <x-text-input id="password" class="block mt-1 w-full input-field" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
            <x-text-input id="password_confirmation" class="block mt-1 w-full input-field" type="password" name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <div>
            <x-input-label value="Verify your email via" class="mb-3" />
            <div class="store-grid-2 gap-3">
                <label class="relative cursor-pointer">
                    <input type="radio" name="verification_method" value="link" class="peer sr-only" @checked(old('verification_method', 'link') === 'link') required>
                    <div class="store-card p-4 border-2 border-transparent peer-checked:border-brand-500 peer-checked:bg-brand-50/50 transition-all">
                        <div class="flex items-center gap-3">
                            <x-icon name="mail" class="w-6 h-6 text-brand-600 shrink-0" />
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">Email Link</p>
                                <p class="text-xs text-slate-500">Click link in your inbox</p>
                            </div>
                        </div>
                    </div>
                </label>
                <label class="relative cursor-pointer">
                    <input type="radio" name="verification_method" value="otp" class="peer sr-only" @checked(old('verification_method') === 'otp')>
                    <div class="store-card p-4 border-2 border-transparent peer-checked:border-brand-500 peer-checked:bg-brand-50/50 transition-all">
                        <div class="flex items-center gap-3">
                            <x-icon name="check-circle" class="w-6 h-6 text-brand-600 shrink-0" />
                            <div>
                                <p class="font-semibold text-slate-900 text-sm">OTP Code</p>
                                <p class="text-xs text-slate-500">6-digit code via email</p>
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            <p class="text-xs text-slate-500 mt-2">Choose OTP — after signup you'll enter the code on the next page (shown on screen in dev mode).</p>
            <x-input-error :messages="$errors->get('verification_method')" class="mt-2" />
        </div>

        <x-primary-button class="w-full mt-2">{{ __('Create Account') }}</x-primary-button>

        <p class="text-center text-sm text-slate-500">
            Already have an account?
            <a href="{{ route('login') }}" class="text-brand-700 font-medium hover:text-brand-800">Sign in</a>
        </p>
    </form>
</x-guest-layout>
