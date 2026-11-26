@props(['items'])

<nav
    id="sidebar-nav"
    class="space-y-1"
    aria-label="Main menu"
>
    @foreach ($items as $item)
        @php $isActive = request()->is(...\Illuminate\Support\Arr::flatten($item['match'])); @endphp
        <a
            href="{{ $item['href'] }}"
            class="{{ $isActive ? 'bg-white/15 text-white ring-1 ring-white/20 shadow-sm shadow-indigo-950/10' : 'text-indigo-50/85 hover:bg-white/10 hover:text-white' }} group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
            @if ($isActive) aria-current="page" @endif
        >
            @if ($isActive)
                <span
                    class="absolute -left-3 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-sky-300"
                    aria-hidden="true"
                ></span>
            @endif
            <span
                class="{{ $isActive ? 'bg-gradient-to-br from-sky-300 to-blue-500 text-white shadow-md shadow-indigo-950/20' : 'bg-white/10 text-indigo-100 group-hover:bg-white/15 group-hover:text-white' }} flex h-8 w-8 flex-none items-center justify-center rounded-lg text-[13px] transition"
            >
                <i class="fa-solid {{ $item['icon'] }}"></i>
            </span>
            <span class="truncate">{{ $item['label'] }}</span>
            @if ($isActive)
                <i
                    class="fa-solid fa-chevron-right ml-auto text-[10px] text-indigo-100/75"></i>
            @endif
        </a>
    @endforeach
</nav>
