@props(['action', 'gridClass', 'hasFilters', 'perPage', 'sortOptions'])

<form method="GET" action="{{ $action }}" class="rounded-xl border border-slate-200 bg-white p-4 shadow-xs">
    <div class="{{ $gridClass }}">
        {{ $slot }}
    </div>

    <div class="mt-3 flex flex-wrap items-center justify-between gap-3">
        <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
            <label for="sort">Sort</label>
            <select id="sort" name="sort" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700">
                @foreach($sortOptions as $value => $label)
                    <option value="{{ $value }}" {{ request('sort', 'name') === $value ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
            <label for="per_page" class="ml-2">Show</label>
            <select id="per_page" name="per_page" onchange="this.form.submit()" class="rounded-lg border border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-700">
                @foreach([10, 25, 50, 100] as $size)
                    <option value="{{ $size }}" {{ $perPage === $size ? 'selected' : '' }}>{{ $size }} per page</option>
                @endforeach
            </select>
        </div>
        <div class="flex items-center gap-2">
            @if($hasFilters)
                <a href="{{ $action }}" class="rounded-lg px-3 py-2 text-xs font-semibold text-slate-500 hover:text-slate-800">Clear filters</a>
            @endif
            <button type="submit" class="rounded-lg bg-slate-800 px-4 py-2 text-xs font-semibold text-white transition hover:bg-slate-900">Search</button>
        </div>
    </div>
</form>
