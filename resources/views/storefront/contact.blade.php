@extends('layouts.storefront')

@section('title', 'Contact Us')

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-oat-100 via-white to-sage-50 py-20 border-b border-oat-200">
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <x-reveal type="fade-up">
                <span class="section-badge">Get In Touch</span>
                <h1 class="text-4xl md:text-5xl font-bold mb-4 text-charcoal-700">Contact Us</h1>
                <p class="text-lg text-charcoal-500 max-w-2xl mx-auto">Have a question about a product, gift idea, custom color, or delivery? We're here to help.</p>
            </x-reveal>
        </div>
    </section>

    <section class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 -mt-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <div class="space-y-5">
                @foreach([
                    ['title' => 'Phone', 'value' => shop_config('contact_phone'), 'icon' => 'phone', 'desc' => 'Mon-Sat, 9am-7pm'],
                    ['title' => 'Email', 'value' => shop_config('contact_email'), 'icon' => 'mail', 'desc' => 'We reply within 24 hours'],
                    ['title' => 'Location', 'value' => shop_config('contact_address'), 'icon' => 'map-pin', 'desc' => 'Handmade in Karachi'],
                ] as $i => $info)
                    <x-reveal type="fade-right" :delay="$i * 100">
                        <x-store-card class="h-full card-glow group">
                            <div class="icon-box bg-oat-100 text-terracotta-500 mb-4">
                                <x-icon :name="$info['icon']" class="w-7 h-7" />
                            </div>
                            <h3 class="font-bold text-charcoal-700">{{ $info['title'] }}</h3>
                            <p class="mt-1 text-sage-700 font-medium">{{ $info['value'] }}</p>
                            <p class="mt-1 text-sm text-charcoal-500">{{ $info['desc'] }}</p>
                        </x-store-card>
                    </x-reveal>
                @endforeach

                <x-reveal type="fade-right" delay="300">
                    <x-store-card class="bg-oat-100 border-oat-200 h-full">
                        <h3 class="font-bold text-lg mb-3 text-charcoal-700 flex items-center gap-2">
                            <x-icon name="clock" class="w-5 h-5 text-terracotta-500" /> Business Hours
                        </h3>
                        <ul class="space-y-2 text-sm text-slate-600">
                            <li class="flex justify-between"><span>Monday - Friday</span><span class="font-semibold text-charcoal-700">9:00 AM - 7:00 PM</span></li>
                            <li class="flex justify-between"><span>Saturday</span><span class="font-semibold text-charcoal-700">10:00 AM - 5:00 PM</span></li>
                            <li class="flex justify-between"><span>Sunday</span><span class="font-semibold text-slate-500">Closed</span></li>
                        </ul>
                    </x-store-card>
                </x-reveal>
            </div>

            <x-reveal type="fade-left" delay="150" class="lg:col-span-2 !pointer-events-auto">
                <form action="{{ route('contact.store') }}" method="POST" class="store-card p-8 md:p-10 space-y-6 h-full relative z-10">
                    <div>
                        <h2 class="text-2xl font-bold text-charcoal-700">Send us a message</h2>
                        <p class="text-charcoal-500 mt-1">Fill out the form and we'll get back to you shortly.</p>
                    </div>
                    @csrf
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Your Name</label>
                            <input type="text" name="name" value="{{ old('name') }}" required class="input-field">
                            @error('name')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Email</label>
                            <input type="email" name="email" value="{{ old('email') }}" required class="input-field">
                            @error('email')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Subject</label>
                        <input type="text" name="subject" value="{{ old('subject') }}" required class="input-field">
                        @error('subject')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Message</label>
                        <textarea name="message" rows="5" required class="input-field">{{ old('message') }}</textarea>
                        @error('message')<p class="text-red-500 text-sm mt-1">{{ $message }}</p>@enderror
                    </div>
                    <button type="submit" class="btn-primary w-full md:w-auto inline-flex items-center gap-2">
                        <x-icon name="mail" class="w-5 h-5" />
                        Send Message
                    </button>
                </form>
            </x-reveal>
        </div>
    </section>

    <section class="bg-oat-100/70 py-20">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
            <x-reveal type="fade-up" class="text-center mb-12">
                <span class="section-badge">FAQ</span>
                <h2 class="section-title">Frequently Asked Questions</h2>
            </x-reveal>

            <div class="space-y-4" x-data="{ open: null }">
                @foreach([
                    ['q' => 'What are your delivery options?', 'a' => 'We offer local delivery in Karachi and nationwide delivery.'.((float) shop_config('free_shipping_threshold', 0) > 0 ? ' Free delivery on orders above '.shop_money(shop_config('free_shipping_threshold')).'.' : '').' Custom orders may need extra making time.'],
                    ['q' => 'Do you accept custom orders?', 'a' => 'Yes. Share your idea, color preference, size, quantity, and deadline so we can confirm feasibility and timing.'],
                    ['q' => 'What payment methods do you accept?', 'a' => 'We accept online card payments via Stripe and Cash on Delivery (COD) for eligible areas.'],
                    ['q' => 'Can I return a product?', 'a' => 'Ready-made unused items can be discussed for return within 7 days. Custom handmade pieces are made to your selected details, so returns depend on the issue reported.'],
                ] as $i => $faq)
                    <x-reveal type="fade-up" :delay="$i * 80">
                        <div class="bg-white rounded-2xl border border-oat-200 shadow-card overflow-hidden card-hover">
                            <button type="button" @click="open = open === {{ $i }} ? null : {{ $i }}"
                                    class="w-full flex items-center justify-between px-6 py-5 text-left font-semibold text-charcoal-700 hover:text-sage-700 transition-colors">
                                {{ $faq['q'] }}
                                <svg class="w-5 h-5 shrink-0 transition-transform duration-300" :class="open === {{ $i }} && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                            <div x-show="open === {{ $i }}" x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="px-6 pb-5 text-slate-600 leading-relaxed">
                                {{ $faq['a'] }}
                            </div>
                        </div>
                    </x-reveal>
                @endforeach
            </div>
        </div>
    </section>
@endsection
