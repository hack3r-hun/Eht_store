<div
    x-data
    class="fixed top-4 right-4 z-[100] flex flex-col gap-3 w-full max-w-sm pointer-events-none px-4 sm:px-0"
    @toast.window="$store.toast.push($event.detail.message, $event.detail.type ?? 'success')"
>
    @if(session('success'))
        <div x-init="$store.toast.push(@js(session('success')), 'success')"></div>
    @endif
    @if(session('error'))
        <div x-init="$store.toast.push(@js(session('error')), 'error')"></div>
    @endif
    @if(session('info'))
        <div x-init="$store.toast.push(@js(session('info')), 'info')"></div>
    @endif
    @if(session('status'))
        @php
            $statusMessage = match(session('status')) {
                'profile-updated' => 'Profile updated successfully.',
                'password-updated' => 'Password updated successfully.',
                'verification-link-sent' => 'Verification link sent to your email.',
                default => is_string(session('status')) ? session('status') : 'Action completed.',
            };
        @endphp
        <div x-init="$store.toast.push(@js($statusMessage), 'success')"></div>
    @endif
    @if(isset($errors) && $errors->any())
        <div x-init="$store.toast.push(@js($errors->first()), 'error', 7000)"></div>
    @endif

    <template x-for="toast in $store.toast.items" :key="toast.id">
        <div
            x-show="true"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8 scale-95"
            x-transition:enter-end="opacity-100 translate-x-0 scale-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="pointer-events-auto toast-item"
            :class="{
                'toast-success': toast.type === 'success',
                'toast-error': toast.type === 'error',
                'toast-info': toast.type === 'info',
            }"
        >
            <div class="flex items-start gap-3">
                <div class="toast-icon shrink-0">
                    <span x-show="toast.type === 'success'"><x-icon name="check-circle" class="w-5 h-5" /></span>
                    <span x-show="toast.type === 'error'"><x-icon name="x-circle" class="w-5 h-5" /></span>
                    <span x-show="toast.type === 'info'"><x-icon name="info" class="w-5 h-5" /></span>
                </div>
                <p class="flex-1 text-sm font-medium leading-relaxed" x-text="toast.message"></p>
                <button
                    type="button"
                    @click="$store.toast.remove(toast.id)"
                    class="shrink-0 p-1 rounded-lg opacity-70 hover:opacity-100 transition-opacity"
                >
                    <x-icon name="x" class="w-4 h-4" />
                </button>
            </div>
        </div>
    </template>
</div>
