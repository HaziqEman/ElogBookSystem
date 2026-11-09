{{-- Shared by create + edit. $university is null on create. --}}
@php
    $field = 'w-full rounded-lg border border-slate-200 p-3 text-sm focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20';
@endphp

<label class="block space-y-2">
    <span class="text-sm font-semibold text-slate-600">University name</span>
    <input type="text" name="name" value="{{ old('name', $university->name ?? '') }}" maxlength="255" required placeholder="Universiti Teknologi MARA (UiTM)" class="{{ $field }}">
</label>

<div class="space-y-2">
    <span class="text-sm font-semibold text-slate-600">Logo</span>
    <div class="flex items-center gap-4">
        <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-xl border border-slate-200 bg-slate-50">
            @if(! empty($university?->logo_url))
                <img src="{{ $university->logo_url }}" alt="Current logo" class="h-full w-full object-contain p-1">
            @else
                <i class="fa-solid fa-building-columns text-2xl text-slate-300"></i>
            @endif
        </div>
        <div class="flex-1">
            <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600 file:mr-3 file:rounded-lg file:border-0 file:bg-blue-50 file:px-4 file:py-2 file:text-sm file:font-semibold file:text-blue-700 hover:file:bg-blue-100">
            <p class="mt-1 text-xs text-slate-500">PNG, JPG or WebP, max 2 MB.{{ ! empty($university?->logo_url) ? ' Choosing a file replaces the current logo.' : '' }}</p>
        </div>
    </div>
</div>
