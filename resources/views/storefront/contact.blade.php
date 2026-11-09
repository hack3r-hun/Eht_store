@extends('layouts.storefront')

@section('title', $page->metaValue('meta_title') ?: 'Contact Us')
@section('meta_description', $page->metaValue('meta_description') ?: 'Questions about a product, gift idea, custom colour or delivery? Get in touch.')

@section('content')
    <x-cms.sections :page="$page" part="top" />

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
                            <div class="icon-box bg-terracotta-50 text-terracotta-500 mb-4">
                                <x-icon :name="$info['icon']" class="w-7 h-7" />
                            </div>
                            <h3 class="font-bold text-charcoal-700">{{ $info['title'] }}</h3>
                            <p class="mt-1 text-sage-700 font-medium">{{ $info['value'] }}</p>
                            <p class="mt-1 text-sm text-slate-500">{{ $info['desc'] }}</p>
                        </x-store-card>
                    </x-reveal>
                @endforeach

                <x-reveal type="fade-right" delay="300">
                    <x-store-card class="bg-oat-100 border-oat-200 h-full">
                        <h3 class="font-bold text-lg mb-3 text-charcoal-700 flex items-center gap-2">
                            <x-icon name="clock" class="w-5 h-5 text-terracotta-500" /> Business Hours
                        </h3>
                        <ul class="space-y-2 text-sm text-slate-600">
                            @foreach(($page->metaValue('hours') ?: [['label' => 'Monday - Friday', 'value' => '9:00 AM - 7:00 PM'], ['label' => 'Saturday', 'value' => '10:00 AM - 5:00 PM'], ['label' => 'Sunday', 'value' => 'Closed']]) as $row)
                                <li class="flex justify-between gap-4"><span>{{ $row['label'] ?? '' }}</span><span class="font-semibold text-charcoal-700 text-right">{{ $row['value'] ?? '' }}</span></li>
                            @endforeach
                        </ul>
                    </x-store-card>
                </x-reveal>
            </div>

            <x-reveal type="fade-left" delay="150" class="lg:col-span-2 !pointer-events-auto">
                <form action="{{ route('contact.store') }}" method="POST" class="store-card p-8 md:p-10 space-y-6 h-full relative z-10">
                    <div>
                        <h2 class="text-2xl font-bold text-charcoal-700">{{ $page->metaValue('form_title') ?: 'Send us a message' }}</h2>
                        <p class="text-charcoal-500 mt-1">{{ $page->metaValue('form_subtitle') ?: "Fill out the form and we'll get back to you shortly." }}</p>
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

    <x-cms.sections :page="$page" part="bottom" />
@endsection
