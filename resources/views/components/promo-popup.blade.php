{{-- Promo pop-up: configured entirely from Dashboard → Modules → Promo pop-up. --}}
@php
    $promo = fn (string $field, mixed $fallback = '') => module_value('promo_popup', $field, $fallback);

    $heading = trim((string) $promo('heading'));
    $text = trim((string) $promo('text'));
    $eyebrow = trim((string) $promo('eyebrow'));
    $image = cms_image((string) $promo('image'));
    $buttonText = trim((string) $promo('button_text'));
    $buttonUrl = trim((string) $promo('button_url'));
    $frequency = (string) $promo('frequency', 'once_per_day');
    $delay = max(0, (int) $promo('delay_seconds', 2));

    // Only plain site links, http(s), tel: and mailto: are allowed on the button.
    $safeUrl = preg_match('#^(/(?!/)|\#|https?://|tel:|mailto:)#i', $buttonUrl) === 1;
    $showButton = $buttonText !== '' && $buttonUrl !== '' && $safeUrl;

    // Scheduling: dates are optional; the end date is inclusive.
    $today = now()->toDateString();
    $start = (string) $promo('start_date');
    $end = (string) $promo('end_date');
    $inWindow = ($start === '' || $today >= $start) && ($end === '' || $today <= $end);

    $onThisPage = $promo('show_on', 'home') === 'all' || request()->routeIs('home');
    $render = $inWindow && $onThisPage && ($heading !== '' || $text !== '' || $image);

    // Changing anything about the message makes visitors who dismissed the old one see it again.
    $version = substr(md5($heading.'|'.$text.'|'.$eyebrow.'|'.$image.'|'.$buttonText.'|'.$buttonUrl), 0, 10);
@endphp

@if($render)
    <div
        x-data="{
            open: false,
            version: @js($version),
            frequency: @js($frequency),
            storeKey: 'ek_promo',
            init() {
                if (this.alreadySeen()) return;
                const show = () => {
                    // Never stack on top of another pop-up (e.g. the newsletter one).
                    if (window.__popupOpen) { setTimeout(show, 2000); return; }
                    this.open = true;
                    window.__popupOpen = true;
                    this.markSeen();
                };
                setTimeout(show, {{ $delay * 1000 }});
            },
            alreadySeen() {
                try {
                    if (this.frequency === 'once_per_session') return sessionStorage.getItem(this.storeKey) === this.version;
                    const [v, t] = (localStorage.getItem(this.storeKey) || '').split('|');
                    if (this.frequency === 'once') return v === this.version;
                    if (this.frequency === 'once_per_day') return v === this.version && (Date.now() - Number(t)) < 86400000;
                } catch (e) {}
                return false;
            },
            markSeen() {
                try {
                    if (this.frequency === 'once_per_session') sessionStorage.setItem(this.storeKey, this.version);
                    else if (this.frequency !== 'every_visit') localStorage.setItem(this.storeKey, this.version + '|' + Date.now());
                } catch (e) {}
            },
            close() {
                this.open = false;
                window.__popupOpen = false;
            },
        }"
        @keydown.escape.window="if (open) close()"
    >
        <div x-show="open" x-cloak x-transition.opacity class="fixed inset-0 z-[90] bg-charcoal-900/50 backdrop-blur-sm" @click="close()"></div>
        <div x-show="open" x-cloak
             x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 translate-y-6 scale-95" x-transition:enter-end="opacity-100 translate-y-0 scale-100"
             x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0 scale-95"
             class="fixed z-[95] inset-x-4 top-1/2 -translate-y-1/2 sm:inset-x-auto sm:left-1/2 sm:-translate-x-1/2 sm:w-full sm:max-w-md max-h-[90vh] overflow-y-auto bg-white rounded-3xl shadow-2xl text-center"
             role="dialog" aria-modal="true" aria-label="{{ $heading !== '' ? $heading : 'Announcement' }}">
            <button type="button" @click="close()" class="absolute top-3 right-3 z-10 w-9 h-9 rounded-xl bg-white/90 text-slate-500 hover:text-slate-700 hover:bg-white shadow-sm flex items-center justify-center text-xl leading-none" aria-label="Close">&times;</button>

            @if($image)
                <img src="{{ $image }}" alt="{{ $heading }}" class="w-full aspect-[4/3] object-cover rounded-t-3xl">
            @endif

            <div class="p-8 {{ $image ? 'pt-6' : '' }}">
                @if($eyebrow !== '')
                    <p class="text-xs font-bold uppercase tracking-widest text-terracotta-500 mb-2">{{ $eyebrow }}</p>
                @endif
                @if($heading !== '')
                    <h2 class="text-2xl font-bold text-charcoal-700">{{ $heading }}</h2>
                @endif
                @if($text !== '')
                    <p class="text-slate-500 mt-2 whitespace-pre-line">{{ $text }}</p>
                @endif

                @if($showButton)
                    <a href="{{ $buttonUrl }}" @click="close()" class="btn-primary w-full mt-6">{{ $buttonText }}</a>
                    <button type="button" @click="close()" class="mt-4 text-sm text-slate-400 hover:text-slate-600">Maybe later</button>
                @else
                    <button type="button" @click="close()" class="btn-primary w-full mt-6">Got it</button>
                @endif
            </div>
        </div>
    </div>
@endif
