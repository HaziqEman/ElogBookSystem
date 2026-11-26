{{--
    resources/views/components/auth/input.blade.php

    Usage:
    <x-forms.text-input name="email" label="Email address" type="email" icon="fa-regular fa-envelope"
                  placeholder="you@example.com" autocomplete="email" />

    Extra attributes (placeholder, autocomplete, inputmode, maxlength...) go straight to the <input>.
    Passwords are never repopulated and get a show/hide button automatically.
--}}
@props(['name', 'label', 'type' => 'text', 'icon' => null, 'required' => true])

@php
    $id = $name;
    $invalid = $errors->has($name);
    $isPassword = $type === 'password';

    $inputClass =
        'w-full rounded-xl border bg-white py-3 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4 ' .
        ($icon ? 'pl-11 ' : 'pl-4 ') .
        ($isPassword ? 'pr-12 ' : 'pr-4 ') .
        ($invalid
            ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/15'
            : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/15');
@endphp

<div {{ $attributes->only('class') }}>
    <label
        for="{{ $id }}"
        class="mb-1.5 block text-sm font-semibold text-slate-700"
    >{{ $label }}</label>

    <div class="relative">
        @if ($icon)
            <span
                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-4 text-slate-400"
            >
                <i
                    class="{{ $icon }}"
                    aria-hidden="true"
                ></i>
            </span>
        @endif

        <input
            id="{{ $id }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ $isPassword ? '' : old($name) }}"
            @required($required)
            @if ($invalid) aria-invalid="true" aria-describedby="{{ $id }}-error" @endif
            {{ $attributes->except('class')->merge(['class' => $inputClass]) }}
        >

        @if ($isPassword)
            <button
                type="button"
                data-toggle-password="{{ $id }}"
                class="absolute inset-y-0 right-0 flex items-center rounded-r-xl px-4 text-slate-400 transition hover:text-blue-600 focus:outline-none focus-visible:text-blue-600"
                aria-label="Show password"
                aria-pressed="false"
            >
                <i
                    class="fa-regular fa-eye"
                    aria-hidden="true"
                ></i>
            </button>
        @endif
    </div>

    @error($name)
        <p
            id="{{ $id }}-error"
            class="mt-1.5 text-xs font-medium text-rose-600"
        >{{ $message }}</p>
    @enderror
</div>
