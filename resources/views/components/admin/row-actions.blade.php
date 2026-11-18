@props([
    'deleteConfirmation',
    'deleteUrl',
    'editUrl',
    'name',
    'resetConfirmation',
    'resetUrl',
    'deleteDisabled' => false,
    'deleteDisabledTitle' => null,
])

<div class="flex flex-wrap gap-2">
    <a href="{{ $editUrl }}" class="inline-flex items-center rounded-lg bg-amber-500 px-3 py-1.5 text-xs font-semibold text-slate-900 transition hover:bg-amber-600">
        Edit
    </a>
    <form action="{{ $resetUrl }}" method="POST" class="inline" onsubmit="return confirm('{{ addslashes($resetConfirmation) }}');">
        @csrf
        <button type="submit" title="Reset password" aria-label="Reset password for {{ $name }}" class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100">
            <i class="fa-solid fa-key"></i>
        </button>
    </form>
    <form action="{{ $deleteUrl }}" method="POST" class="inline" onsubmit="return confirm('{{ $deleteConfirmation }}');">
        @csrf
        @method('DELETE')
        @if($deleteDisabled)
            <button type="button" disabled title="{{ $deleteDisabledTitle }}" class="inline-flex cursor-not-allowed items-center rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white opacity-40">
                Delete
            </button>
        @else
            <button type="submit" class="inline-flex items-center rounded-lg bg-rose-500 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-rose-600">
                Delete
            </button>
        @endif
    </form>
</div>
