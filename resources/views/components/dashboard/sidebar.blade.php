@props([
    'displayName',
    'roleShort',
    'supervisorName',
    'industryName',
    'currentSupervisor',
    'navItems',
])

{{-- Sidebar: slide-out drawer on phones, fixed column on laptops --}}
<aside
    id="sidebar"
    class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] shrink-0 -translate-x-full flex-col overflow-hidden bg-gradient-to-b from-[#4968c8] via-[#3d59b2] to-[#34458f] text-white shadow-xl shadow-indigo-950/10 transition-transform duration-200 ease-out md:static md:z-auto md:max-w-none md:translate-x-0"
    style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);"
>

    <div class="relative flex min-h-0 flex-1 flex-col">
        {{-- Soft decorative glows --}}
        <div
            class="pointer-events-none absolute -left-16 -top-20 h-56 w-56 rounded-full bg-sky-300/20 blur-3xl"
            aria-hidden="true"
        ></div>
        <div
            class="pointer-events-none absolute -bottom-24 -right-16 h-64 w-64 rounded-full bg-violet-300/15 blur-3xl"
            aria-hidden="true"
        ></div>

        {{-- Decorative bottom wave background --}}
        <div
            class="pointer-events-none absolute bottom-0 left-0 z-0 h-40 w-full overflow-hidden"
            aria-hidden="true"
        >
            <svg
                class="absolute bottom-0 h-full w-full"
                viewBox="0 0 288 160"
                preserveAspectRatio="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <defs>
                    <linearGradient
                        id="sidebarWave1"
                        x1="0"
                        y1="0"
                        x2="1"
                        y2="1"
                    >
                        <stop
                            offset="0%"
                            stop-color="#C7D2FE"
                            stop-opacity="0.30"
                        />
                        <stop
                            offset="100%"
                            stop-color="#A5B4FC"
                            stop-opacity="0.04"
                        />
                    </linearGradient>
                    <linearGradient
                        id="sidebarWave2"
                        x1="0"
                        y1="0"
                        x2="1"
                        y2="0"
                    >
                        <stop
                            offset="0%"
                            stop-color="#DDD6FE"
                            stop-opacity="0.28"
                        />
                        <stop
                            offset="100%"
                            stop-color="#BFDBFE"
                            stop-opacity="0.07"
                        />
                    </linearGradient>
                </defs>

                <path
                    d="M0 85 C65 65 95 115 145 105 C205 92 225 35 288 45 L288 160 L0 160 Z"
                    fill="url(#sidebarWave1)"
                />
                <path
                    d="M0 115 C55 80 95 95 140 125 C195 160 235 95 288 85 L288 160 L0 160 Z"
                    fill="url(#sidebarWave2)"
                />
                <path
                    d="M0 85 C65 65 95 115 145 105 C205 92 225 35 288 45"
                    fill="none"
                    stroke="white"
                    stroke-opacity="0.16"
                    stroke-width="1.5"
                />
            </svg>
        </div>

        {{-- Brand --}}
        <div class="relative flex flex-none items-center gap-3 border-b border-white/10 px-5 py-5">
            <img
                src="{{ asset('images/logo/logo-horizontal-white-animated.svg') }}"
                alt="Smart eLogBook"
                class="h-12 w-auto"
            >
            <button
                type="button"
                id="sidebar-close"
                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-blue-200 hover:bg-white/10 md:hidden"
                aria-label="Close menu"
            >
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        {{-- Scrollable middle: profile + menu --}}
        <div
            class="relative min-h-0 flex-1 overflow-y-auto px-3 pb-4 pt-5 [scrollbar-color:rgba(255,255,255,0.2)_transparent] [scrollbar-width:thin]">

            {{-- Profile card --}}
            <div
                class="relative overflow-hidden rounded-2xl border border-white/25 bg-white/15 p-3.5 shadow-lg shadow-indigo-950/10 backdrop-blur-md"
                style="background: linear-gradient(135deg, rgba(255,255,255,.20) 0%, rgba(190,205,255,.22) 48%, rgba(158,178,255,.30) 100%);"
            >
                {{-- class="relative overflow-hidden rounded-3xl px-6 py-6 text-white shadow-[0_14px_34px_rgba(47,91,255,0.20)] md:px-9 md:py-7" --}}

                <div class="flex items-center gap-3">
                    <div class="relative flex-none">
                        <div
                            class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-sky-300 to-indigo-400 text-base font-bold text-white shadow-md shadow-indigo-900/15 ring-2 ring-white/40">
                            {{ strtoupper(substr($displayName ?? 'S', 0, 1)) }}
                        </div>
                        <span
                            class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-[#5269b2] bg-emerald-400"
                            title="Online"
                        ></span>
                    </div>
                    <div class="min-w-0 flex-1">
                        <h4 class="truncate text-sm font-bold leading-tight text-white">
                            {{ $displayName }}</h4>
                        <p class="mt-0.5 truncate text-xs text-indigo-50/85">
                            {{ $roleShort }}</p>
                    </div>
                </div>

                @if (request()->is('student*') && ($supervisorName || $industryName))
                    <div class="mt-3 space-y-1.5 border-t border-white/20 pt-3">
                        @if ($supervisorName)
                            <div
                                class="flex items-center gap-2.5 rounded-xl bg-white/10 px-2.5 py-2">
                                <span
                                    class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-indigo-400/20 text-xs text-indigo-200"
                                ><i class="fa-solid fa-user-graduate"></i></span>
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-[0.12em] text-indigo-100/65">
                                        Faculty Evaluator</p>
                                    <p class="truncate text-[13px] font-semibold text-white">
                                        {{ $supervisorName }}</p>
                                </div>
                            </div>
                        @endif
                        @if ($industryName)
                            <div
                                class="flex items-center gap-2.5 rounded-xl bg-white/10 px-2.5 py-2">
                                <span
                                    class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-emerald-400/20 text-xs text-emerald-200"
                                ><i class="fa-solid fa-building-user"></i></span>
                                <div class="min-w-0">
                                    <p
                                        class="text-[10px] font-semibold uppercase tracking-[0.12em] text-indigo-100/65">
                                        Industry Supervisor</p>
                                    <p class="truncate text-[13px] font-semibold text-white">
                                        {{ $industryName }}</p>
                                </div>
                            </div>
                        @endif
                    </div>
                @endif

                @if (request()->is('supervisor*') && $currentSupervisor && $currentSupervisor->company_name)
                    <div class="mt-3 border-t border-white/20 pt-3">
                        <div
                            class="flex items-center gap-2.5 rounded-xl border border-white/15 bg-white/10 px-2.5 py-2 shadow-inner">
                            <span
                                class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-emerald-400/20 text-xs text-emerald-200"
                            ><i class="fa-solid fa-building"></i></span>
                            <div class="min-w-0">
                                <p
                                    class="text-[10px] font-semibold uppercase tracking-[0.12em] text-indigo-100/65">
                                    Company</p>
                                <p class="truncate text-[13px] font-semibold text-white">
                                    {{ $currentSupervisor->company_name }}</p>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Menu --}}
            <p
                class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-indigo-100/60">
                Menu</p>

            <x-dashboard.navigation :items="$navItems" />
        </div>

        {{-- Pinned footer: log out + version --}}
        <div class="relative flex-none border-t border-white/15 px-3 pb-3 pt-3">
            {{-- Logout must be POST (the route only accepts POST) --}}
            <form
                method="POST"
                action="{{ route('logout') }}"
                class="m-0"
            >
                @csrf
                <button
                    type="submit"
                    class="group flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-rose-100/90 transition hover:bg-rose-300/15 hover:text-white"
                >
                    <span
                        class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-rose-300/15 text-[13px] text-rose-100 transition group-hover:bg-rose-300/25 group-hover:text-white"
                    ><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                    Log out
                </button>
            </form>
            <div class="mt-2 flex items-center justify-between px-3 text-[11px] text-indigo-100/60">
                <span>v2.5 Full-Routing</span>
                <span class="inline-flex items-center gap-1.5">
                    <span class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"></span>
                    System online
                </span>
            </div>
        </div>
    </div>
</aside>
