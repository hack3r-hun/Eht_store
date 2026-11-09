{{-- Welcome popup: shows once per browser after 12s, hands out the WELCOME10 coupon. --}}
<div
    x-data="{
        open: false,
        done: false,
        busy: false,
        email: '',
        coupon: null,
        couponLabel: '',
        error: '',
        copied: false,
        init() {
            let dismissed = false;
            try { dismissed = !!localStorage.getItem('ek_newsletter'); } catch (e) {}
            if (!dismissed) {
                const show = () => {
                    // Wait while another pop-up (e.g. the promo one) is on screen.
                    if (window.__popupOpen) { setTimeout(show, 2000); return; }
                    this.open = true;
                    window.__popupOpen = true;
                };
                setTimeout(show, {{ (int) module_value('newsletter_popup', 'delay_seconds', 12) * 1000 }});
            }
        },
        close() {
            this.open = false;
            window.__popupOpen = false;
            try { localStorage.setItem('ek_newsletter', '1'); } catch (e) {}
        },
        async submit() {
            if (this.busy) return;
            this.busy = true; this.error = '';
            try {
                const res = await fetch('{{ route('newsletter.store') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content },
                    body: JSON.stringify({ email: this.email }),
                });
                const data = await res.json();
                if (!res.ok) { this.error = data.errors?.email?.[0] || 'Please enter a valid email.'; return; }
                this.coupon = data.coupon; this.couponLabel = data.coupon_label; this.done = true;
                try { localStorage.setItem('ek_newsletter', '1'); } catch (e) {}
                if (window.launchConfetti) window.launchConfetti();
            } catch (e) { this.error = 'Something went wrong. Please try again.'; }
            finally { this.busy = false; }
        },
        copy() {
            navigator.clipboard?.writeText(this.coupon).then(() => { this.copied = true; setTimeout(() => this.copied = false, 1800); });
        },
    }"
    @keydown.escape.window="if (open) close()"
>
    <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[90] bg-charcoal-900/50 backdrop-blur-sm" @click="close()"></div>
    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
         class="fixed z-[95] inset-x-4 top-1/2 -translate-y-1/2 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-full sm:max-w-md bg-white rounded-3xl shadow-2xl p-8 text-center"
         role="dialog" aria-modal="true" aria-label="Join our newsletter">
        <button type="button" @click="close()" class="absolute top-4 right-4 w-9 h-9 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 flex items-center justify-center" aria-label="Close">&times;</button>
        <template x-if="!done">
            <div>
                <div class="w-14 h-14 mx-auto mb-4 rounded-2xl bg-terracotta-50 text-terracotta-500 flex items-center justify-center animate-float"><x-icon name="sparkles" class="w-7 h-7" /></div>
                <h2 class="text-2xl font-bold text-charcoal-700">{{ module_value('newsletter_popup', 'heading') }}</h2>
                <p class="text-slate-500 mt-2 mb-6">{{ module_value('newsletter_popup', 'text') }}</p>
                <form @submit.prevent="submit()" class="space-y-3" data-no-loading>
                    <input type="email" x-model="email" required placeholder="you@example.com" class="input-field text-center" aria-label="Email address">
                    <p x-show="error" x-cloak class="text-sm text-red-600" x-text="error"></p>
                    <button type="submit" class="btn-primary w-full" :disabled="busy"><span x-text="busy ? 'Subscribing…' : 'Subscribe'"></span></button>
                </form>
                <button type="button" @click="close()" class="mt-4 text-sm text-slate-400 hover:text-slate-600">No thanks</button>
            </div>
        </template>
        <template x-if="done">
            <div>
                <div class="w-14 h-14 mx-auto mb-4 rounded-full bg-green-100 text-green-600 flex items-center justify-center text-2xl animate-pop">✓</div>
                <h2 class="text-2xl font-bold text-charcoal-700">You're in!</h2>
                <template x-if="coupon">
                    <div>
                        <p class="text-slate-500 mt-2">Use this code at checkout for <span x-text="couponLabel"></span>:</p>
                        <button type="button" @click="copy()" class="mt-4 inline-flex items-center gap-3 rounded-xl border-2 border-dashed border-terracotta-300 bg-terracotta-50 px-6 py-3 font-mono text-xl font-bold tracking-widest text-terracotta-600 hover:bg-terracotta-100 transition-colors">
                            <span x-text="coupon"></span>
                            <span class="text-xs font-sans font-semibold tracking-normal text-slate-500" x-text="copied ? 'Copied!' : 'Tap to copy'"></span>
                        </button>
                    </div>
                </template>
                <button type="button" @click="close()" class="btn-outline mt-6">Start shopping</button>
            </div>
        </template>
    </div>
</div>
