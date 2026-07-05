@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Confirm Delete',
    'message' => 'This action cannot be undone. Are you sure you want to continue?',
    'item' => null,
    'confirmLabel' => 'Delete',
    'formId' => null,
])

<div
    x-data="{ open: false }"
    x-effect="document.body.classList.toggle('overflow-hidden', open)"
    {{ $attributes->class(['inline-flex']) }}
>
    <button
        type="button"
        @click="open = true"
        class="inline-flex items-center gap-1.5 text-red-500 font-semibold text-sm hover:text-red-700 transition-colors"
    >
        <x-icon name="trash" class="w-4 h-4" />
        {{ $slot->isEmpty() ? 'Delete' : $slot }}
    </button>

    <template x-teleport="body">
        <div
            x-show="open"
            x-cloak
            class="confirm-modal"
            role="dialog"
            aria-modal="true"
            :aria-label="{{ json_encode($title) }}"
            @keydown.escape.window="open = false"
        >
            <div
                class="confirm-modal-backdrop"
                x-show="open"
                x-transition:enter="ease-out duration-200"
                x-transition:enter-start="opacity-0"
                x-transition:enter-end="opacity-100"
                x-transition:leave="ease-in duration-150"
                x-transition:leave-start="opacity-100"
                x-transition:leave-end="opacity-0"
                @click="open = false"
            ></div>

            <div
                class="confirm-modal-panel"
                x-show="open"
                x-transition:enter="ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-95 translate-y-2"
                @click.stop
            >
                <button
                    type="button"
                    @click="open = false"
                    class="confirm-modal-close"
                    aria-label="Close"
                >
                    <x-icon name="x" class="w-5 h-5" />
                </button>

                <div class="confirm-modal-icon">
                    <x-icon name="trash" class="w-7 h-7" />
                </div>

                <h3 class="confirm-modal-title">{{ $title }}</h3>

                <p class="confirm-modal-message">{{ $message }}</p>

                @if($item)
                    <p class="confirm-modal-item">{{ $item }}</p>
                @endif

                @if($formId)
                <div class="confirm-modal-actions">
                    <button type="button" @click="open = false" class="btn-outline !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit" form="{{ $formId }}" class="btn-danger !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        <x-icon name="trash" class="w-4 h-4" />
                        {{ $confirmLabel }}
                    </button>
                </div>
                @else
                <form method="POST" action="{{ $action }}" class="confirm-modal-actions">
                    @csrf
                    @method($method)
                    <button type="button" @click="open = false" class="btn-outline !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit" class="btn-danger !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        <x-icon name="trash" class="w-4 h-4" />
                        {{ $confirmLabel }}
                    </button>
                </form>
                @endif
            </div>
        </div>
    </template>
</div>
