@extends('layouts.storefront')

@section('title', $page->metaValue('meta_title') ?: 'Custom Order')
@section('meta_description', 'Tell us your idea and we will crochet it — custom colours, sizes, names and gift sets.')

@section('content')
    <x-cms.sections :page="$page" part="top" />

    <section class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12 -mt-6">
        <form action="{{ route('custom-order.store') }}" method="POST" enctype="multipart/form-data" class="store-card p-6 md:p-10 space-y-6 relative z-10">
            @csrf
            @if($errors->any())
                <div class="p-4 rounded-xl bg-red-50 border border-red-200 text-red-700 text-sm">
                    <ul class="list-disc list-inside">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Your name *</label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()?->name) }}" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Email *</label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()?->email) }}" required class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Phone / WhatsApp</label>
                    <input type="text" name="phone" value="{{ old('phone', auth()->user()?->phone) }}" class="input-field">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">What would you like made? *</label>
                    <input type="text" name="item_type" value="{{ old('item_type') }}" required class="input-field" placeholder="e.g. Amigurumi bunny, name keychain, baby blanket">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Preferred colours</label>
                    <input type="text" name="colors" value="{{ old('colors') }}" class="input-field" placeholder="e.g. Sage green and cream">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Size</label>
                    <input type="text" name="size" value="{{ old('size') }}" class="input-field" placeholder="e.g. 15 cm tall, or S/M/L">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Budget</label>
                    <input type="text" name="budget" value="{{ old('budget') }}" class="input-field" placeholder="e.g. Rs. 2,000 – 3,000">
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">Needed by</label>
                    <input type="date" name="needed_by" value="{{ old('needed_by') }}" min="{{ now()->addDay()->toDateString() }}" class="input-field">
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">Tell us about your idea *</label>
                <textarea name="description" rows="5" required minlength="10" class="input-field" placeholder="Details, names, special requests…">{{ old('description') }}</textarea>
            </div>

            <div x-data="{ preview: null }">
                <label class="block text-sm font-medium text-slate-700 mb-2">Reference photo (optional)</label>
                <input type="file" name="reference_image" accept="image/png,image/jpeg,image/webp"
                       @change="const f = $event.target.files[0]; preview = f ? URL.createObjectURL(f) : null"
                       class="block w-full text-sm text-slate-600 file:mr-4 file:rounded-xl file:border-0 file:bg-oat-100 file:px-4 file:py-2.5 file:font-semibold file:text-sage-700 hover:file:bg-sage-50">
                <p class="text-xs text-slate-400 mt-1">JPG, PNG or WebP, up to 4 MB.</p>
                <img x-show="preview" x-cloak :src="preview" alt="Preview" class="mt-3 h-32 w-32 rounded-xl object-cover border border-oat-200">
            </div>

            <div class="flex flex-wrap items-center gap-4">
                <button type="submit" class="btn-primary text-base px-8 py-4">Send Request</button>
                @if(module_enabled('whatsapp'))<a href="{{ whatsapp_url('Hi! I would like a custom order.') }}" target="_blank" rel="noopener" class="btn-outline">Or chat on WhatsApp</a>@endif
            </div>
        </form>
    </section>
    <x-cms.sections :page="$page" part="bottom" />
@endsection
