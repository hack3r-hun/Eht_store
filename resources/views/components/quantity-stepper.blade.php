@props([
    'name' => 'quantity',
    'value' => 1,
    'min' => 1,
    'max' => 99,
    'model' => null,       // Alpine variable name already in scope to bind to; omit to use an isolated local 'qty'
    'maxExpr' => null,     // raw Alpine expression for a dynamic max (e.g. "selected?.stock || 1"); overrides the static $max prop
    'ajax' => false,       // submit via a background PATCH whenever the value changes; dispatches a window `cart-updated` event with the JSON response, falls back to a reload on failure
])

@php
    $qtyVar = $model ?? 'qty';
    $maxExpression = $maxExpr ?? (int) $max;
@endphp

<div
    x-data="{
        @if(!$model) qty: {{ (int) $value }}, @endif
        @if($ajax)
        saving: false,
        syncedQty: {{ (int) $value }},
        requestSeq: 0,
        async submitAjax(quantity) {
            this.saving = true;
            const myRequestId = ++this.requestSeq;
            const form = $el.closest('form');
            try {
                const { data } = await window.axios.patch(form.action, { quantity });
                // A newer request (from a subsequent click) may have started and
                // already resolved while this one was in flight — ignore this
                // now-stale response so it can't clobber the latest state.
                if (myRequestId !== this.requestSeq) { return; }
                window.dispatchEvent(new CustomEvent('cart-updated', { detail: data }));
                if (data.message) {
                    window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.message, type: data.clamped ? 'info' : 'success' } }));
                }
            } catch (e) {
                if (myRequestId === this.requestSeq) { window.location.reload(); }
            } finally {
                this.saving = false;
            }
        },
        @endif
    }"
    {{-- The ajax path allows quantity 0 through (below the UI's own $min floor for
         the +/- buttons) since the cart page relies on 0 to mean "remove this item";
         only genuinely invalid values (empty/NaN from a cleared input) are blocked. --}}
    @if($ajax)
        x-effect="if ({{ $qtyVar }} !== syncedQty && Number.isInteger({{ $qtyVar }}) && {{ $qtyVar }} >= 0) { syncedQty = {{ $qtyVar }}; $nextTick(() => submitAjax({{ $qtyVar }})); }"
    @endif
    class="qty-stepper"
    @if($ajax) :class="saving && 'opacity-60 pointer-events-none'" @endif
>
    <button
        type="button"
        @click="{{ $qtyVar }} = Math.max({{ (int) $min }}, {{ $qtyVar }} - 1)"
        :disabled="{{ $qtyVar }} <= {{ (int) $min }}"
        class="qty-stepper-btn"
        aria-label="Decrease quantity"
    >&minus;</button>
    <input
        type="number"
        name="{{ $name }}"
        x-model.number="{{ $qtyVar }}"
        @blur="if (!Number.isInteger({{ $qtyVar }})) { {{ $qtyVar }} = {{ (int) $min }} }"
        min="{{ (int) $min }}"
        :max="{{ $maxExpression }}"
        class="qty-stepper-input"
        {{ $attributes }}
    >
    <button
        type="button"
        @click="{{ $qtyVar }} = Math.min(({{ $maxExpression }}), {{ $qtyVar }} + 1)"
        :disabled="{{ $qtyVar }} >= ({{ $maxExpression }})"
        class="qty-stepper-btn"
        aria-label="Increase quantity"
    >+</button>
</div>
