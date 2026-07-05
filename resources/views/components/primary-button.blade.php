<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn-primary w-full justify-center']) }}>
    {{ $slot }}
</button>
