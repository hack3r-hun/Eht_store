@props([
    'action',
    'method' => 'DELETE',
    'title' => 'Confirm Delete',
    'message' => 'This action cannot be undone. Are you sure you want to continue?',
    'item' => null,
    'confirmLabel' => 'Delete',
    'formId' => null,
    'variant' => 'danger', // 'danger' | 'neutral' — neutral is for non-destructive actions
    'triggerIcon' => 'trash',
    'triggerClass' => null, // override the default small text-link trigger styling
    'triggerShowIcon' => true, // set false when the slot already conveys the action (e.g. a bare "×")
])

@php
    $isDanger = $variant !== 'neutral';
@endphp

{{--
    Native <dialog> stays closed until showModal(). No Alpine x-show, so tabs
    never show a stuck Archive/Delete overlay on load.
    Teleported to <body> so DELETE forms are never nested inside another form.
--}}
<div
    x-data="{
        open() {
            this.$refs.dlg.showModal();
        },
        close() {
            this.$refs.dlg.close();
        },
    }"
    {{ $attributes->class(['inline-flex']) }}
>
    <button
        type="button"
        @click.stop="open()"
        class="{{ $triggerClass ?? ('inline-flex items-center gap-1.5 font-semibold text-sm transition-colors ' . ($isDanger ? 'text-red-500 hover:text-red-700' : 'text-sage-700 hover:text-sage-800')) }}"
    >
        @if($triggerShowIcon)
            <x-icon :name="$triggerIcon" class="w-4 h-4" />
        @endif
        {{ $slot->isEmpty() ? 'Delete' : $slot }}
    </button>

    <template x-teleport="body">
        <dialog
            x-ref="dlg"
            class="confirm-dialog"
            aria-label="{{ $title }}"
            @click.self="close()"
        >
            <div class="confirm-dialog-panel" @click.stop>
                <button
                    type="button"
                    @click="close()"
                    class="confirm-modal-close"
                    aria-label="Close"
                >
                    <x-icon name="x" class="w-5 h-5" />
                </button>

                <div class="confirm-modal-icon {{ $isDanger ? 'confirm-modal-icon-danger' : 'confirm-modal-icon-neutral' }}">
                    <x-icon :name="$triggerIcon" class="w-7 h-7" />
                </div>

                <h3 class="confirm-modal-title">{{ $title }}</h3>

                <p class="confirm-modal-message">{{ $message }}</p>

                @if($item)
                    <p class="confirm-modal-item">{{ $item }}</p>
                @endif

                @if($formId)
                <div class="confirm-modal-actions">
                    <button type="button" @click="close()" class="btn-outline !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit" form="{{ $formId }}" @click="close()" class="{{ $isDanger ? 'btn-danger' : 'btn-primary' }} !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        <x-icon :name="$triggerIcon" class="w-4 h-4" />
                        {{ $confirmLabel }}
                    </button>
                </div>
                @else
                <form method="POST" action="{{ $action }}" class="confirm-modal-actions">
                    @csrf
                    @method($method)
                    <button type="button" @click="close()" class="btn-outline !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        Cancel
                    </button>
                    <button type="submit" class="{{ $isDanger ? 'btn-danger' : 'btn-primary' }} !py-2.5 !px-5 text-sm w-full sm:w-auto">
                        <x-icon :name="$triggerIcon" class="w-4 h-4" />
                        {{ $confirmLabel }}
                    </button>
                </form>
                @endif
            </div>
        </dialog>
    </template>
</div>
