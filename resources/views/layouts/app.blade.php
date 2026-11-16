<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta
        name="viewport"
        content="width=device-width, initial-scale=1, viewport-fit=cover"
    >
    <meta
        name="theme-color"
        content="#1e3a8a"
    >
    <meta
        name="mobile-web-app-capable"
        content="yes"
    >
    <meta
        name="apple-mobile-web-app-capable"
        content="yes"
    >
    <meta
        name="apple-mobile-web-app-status-bar-style"
        content="black-translucent"
    >
    <meta
        name="apple-mobile-web-app-title"
        content="eLogBook"
    >
    <link
        rel="manifest"
        href="{{ asset('manifest.webmanifest') }}"
    >
    <link
        rel="icon"
        href="{{ asset('pwa/icon-192.svg') }}"
        type="image/svg+xml"
    >
    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >
    <title>E-Logbook System</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <link
        rel="stylesheet"
        href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css"
    >
</head>

<body class="bg-slate-50 font-sans text-slate-800 antialiased">

    @php
        $showDashboardShell =
            request()->is('student*') ||
            request()->is('lecturer*') ||
            request()->is('supervisor*') ||
            request()->is('admin*');
    @endphp

    @if ($showDashboardShell)
        <div class="fixed inset-0 flex flex-col overflow-hidden">
            {{-- Top simulator bar --}}
            <div
                class="flex shrink-0 flex-wrap items-center justify-between gap-2 bg-slate-900 px-3 py-2 text-xs text-white shadow-inner md:px-4"
                style="padding-top: max(0.5rem, env(safe-area-inset-top)); padding-left: max(0.75rem, env(safe-area-inset-left)); padding-right: max(0.75rem, env(safe-area-inset-right));"
            >
                <div class="flex items-center gap-2">
                    <a
                        href="{{ route('login.home') }}"
                        title="Return to the login page to access another system module."
                        class="cursor-pointer rounded-sm bg-amber-500 px-2 py-0.5 text-[11px] font-bold text-slate-950 transition hover:bg-amber-400 md:text-xs"
                    >PROPOSAL SIMULATOR</a>
                    <p class="hidden sm:block">Click roles to switch portal views:</p>
                </div>
                <div class="flex flex-wrap gap-1.5 md:gap-2">
                    <a
                        href="{{ route('module.switch', ['role' => 'student']) }}"
                        class="{{ request()->is('student*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} rounded px-2 py-1 text-[11px] font-semibold transition md:px-3 md:text-xs"
                    >Student<span class="hidden sm:inline"> Module</span></a>
                    <a
                        href="{{ route('module.switch', ['role' => 'lecturer']) }}"
                        class="{{ request()->is('lecturer*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} rounded px-2 py-1 text-[11px] font-semibold transition md:px-3 md:text-xs"
                    >Lecturer<span class="hidden sm:inline"> Module</span></a>
                    <a
                        href="{{ route('module.switch', ['role' => 'supervisor']) }}"
                        class="{{ request()->is('supervisor*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} rounded px-2 py-1 text-[11px] font-semibold transition md:px-3 md:text-xs"
                    >Supervisor<span class="hidden sm:inline"> Module</span></a>
                    <a
                        href="{{ route('module.switch', ['role' => 'admin']) }}"
                        class="{{ request()->is('admin*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} rounded px-2 py-1 text-[11px] font-semibold transition md:px-3 md:text-xs"
                    >Admin<span class="hidden sm:inline"> Module</span></a>
                </div>
            </div>

            <div class="relative flex min-h-0 flex-1 overflow-hidden">
                {{-- Dark overlay (phones only, shown when the drawer is open) --}}
                <div
                    id="sidebar-overlay"
                    class="fixed inset-0 z-30 hidden bg-slate-900/60 md:hidden"
                    aria-hidden="true"
                ></div>

                @php
                    $currentStudent = request()->is('student*')
                        ? Auth::guard('student')->user()
                        : null;
                    $currentLecturer = request()->is('lecturer*')
                        ? Auth::guard('lecturer')->user()
                        : null;
                    $currentSupervisor = request()->is('supervisor*')
                        ? Auth::guard('supervisor')->user()
                        : null;
                    $currentAdmin = request()->is('admin*') ? Auth::guard('admin')->user() : null;

                    $supervisorName = null;
                    $industryName = null;

                    if (request()->is('student*') && $currentStudent) {
                        $displayName = $currentStudent->name;
                        $roleLabel = 'Logged in as Student';
                        $roleShort = 'Student';
                        $supervisorName = optional($currentStudent->lecturer)->name;
                        $industryName = optional($currentStudent->supervisor)->name;
                    } elseif (request()->is('lecturer*') && $currentLecturer) {
                        $displayName = $currentLecturer->name;
                        $roleLabel = 'Faculty Evaluator';
                        $roleShort = 'Faculty Evaluator';
                    } elseif (request()->is('supervisor*') && $currentSupervisor) {
                        $displayName = $currentSupervisor->name;
                        $roleLabel = 'Industry Supervisor';
                        $roleShort = 'Industry Supervisor';
                    } elseif (request()->is('admin*') && $currentAdmin) {
                        $displayName = $currentAdmin->name ?? 'System Administrator';
                        $roleLabel = 'System Administrator';
                        $roleShort = 'System Administrator';
                    } else {
                        $displayName = 'System User';
                        $roleLabel = 'Guest';
                        $roleShort = 'Guest';
                    }

                    // Menu for the current role. "match" lists every page that should light up the link.
                    if (request()->is('student*')) {
                        $navItems = [
                            [
                                'href' => '/student/dashboard',
                                'icon' => 'fa-house',
                                'label' => 'Dashboard',
                                'match' => ['student/dashboard'],
                            ],
                            [
                                'href' => '/student/logbooks',
                                'icon' => 'fa-book',
                                'label' => 'Logbook',
                                'match' => ['student/logbooks', 'student/logbook/*'],
                            ],
                            [
                                'href' => '/student/calendar',
                                'icon' => 'fa-calendar-days',
                                'label' => 'Calendar',
                                'match' => ['student/calendar'],
                            ],
                            [
                                'href' => '/student/attendance',
                                'icon' => 'fa-user-clock',
                                'label' => 'Attendance',
                                'match' => ['student/attendance', 'student/attendance/*'],
                            ],
                            [
                                'href' => '/student/messages',
                                'icon' => 'fa-comments',
                                'label' => 'Messages',
                                'match' => ['student/messages', 'student/messages/*'],
                            ],
                        ];
                    } elseif (request()->is('lecturer*')) {
                        $navItems = [
                            [
                                'href' => '/lecturer/dashboard',
                                'icon' => 'fa-gauge-high',
                                'label' => 'Dashboard',
                                'match' => [
                                    'lecturer/dashboard',
                                    'lecturer/students/*',
                                    'lecturer/logbook',
                                    'lecturer/logbook/*',
                                ],
                            ],
                            [
                                'href' => '/lecturer/messages',
                                'icon' => 'fa-comments',
                                'label' => 'Messages',
                                'match' => ['lecturer/messages', 'lecturer/messages/*'],
                            ],
                            [
                                'href' => '/lecturer/evaluations',
                                'icon' => 'fa-clipboard-check',
                                'label' => 'Supervisor Marks',
                                'match' => ['lecturer/evaluations', 'lecturer/evaluations/*'],
                            ],
                        ];
                    } elseif (request()->is('supervisor*')) {
                        $navItems = [
                            [
                                'href' => '/supervisor/dashboard',
                                'icon' => 'fa-gauge-high',
                                'label' => 'Supervisor Dashboard',
                                'match' => [
                                    'supervisor/dashboard',
                                    'supervisor/logbook',
                                    'supervisor/logbook/*',
                                    'supervisor/students/*',
                                    'supervisor/weeks/*',
                                ],
                            ],
                            [
                                'href' => '/supervisor/tasks',
                                'icon' => 'fa-list-check',
                                'label' => 'Tasks',
                                'match' => ['supervisor/tasks', 'supervisor/tasks/*'],
                            ],
                            [
                                'href' => '/supervisor/messages',
                                'icon' => 'fa-comments',
                                'label' => 'Messages',
                                'match' => ['supervisor/messages', 'supervisor/messages/*'],
                            ],
                            [
                                'href' => '/supervisor/password',
                                'icon' => 'fa-key',
                                'label' => 'Change Password',
                                'match' => ['supervisor/password'],
                            ],
                        ];
                    } else {
                        $navItems = [
                            [
                                'href' => '/admin/dashboard',
                                'icon' => 'fa-sliders',
                                'label' => 'System Overview',
                                'match' => ['admin/dashboard'],
                            ],
                            [
                                'href' => '/admin/students',
                                'icon' => 'fa-file-invoice',
                                'label' => 'Manage Students',
                                'match' => ['admin/students', 'admin/students/*'],
                            ],
                            [
                                'href' => '/admin/lecturers',
                                'icon' => 'fa-user-tie',
                                'label' => 'Manage Lecturers',
                                'match' => ['admin/lecturers', 'admin/lecturers/*'],
                            ],
                            [
                                'href' => '/admin/supervisors',
                                'icon' => 'fa-building-user',
                                'label' => 'Manage Supervisors',
                                'match' => ['admin/supervisors', 'admin/supervisors/*'],
                            ],
                            [
                                'href' => '/admin/reports',
                                'icon' => 'fa-chart-line',
                                'label' => 'Reports',
                                'match' => ['admin/reports', 'admin/reports/*'],
                            ],
                            [
                                'href' => '/admin/universities',
                                'icon' => 'fa-building-columns',
                                'label' => 'Universities',
                                'match' => ['admin/universities', 'admin/universities/*'],
                            ],
                            [
                                'href' => '/admin/evaluations',
                                'icon' => 'fa-clipboard-check',
                                'label' => 'Supervisor Marks',
                                'match' => ['admin/evaluations', 'admin/evaluations/*'],
                            ],
                            [
                                'href' => '/admin/supervisor-rubric',
                                'icon' => 'fa-list-check',
                                'label' => 'Supervisor Rubric',
                                'match' => ['admin/supervisor-rubric', 'admin/supervisor-rubric/*'],
                            ],
                        ];
                    }

                    // Used by the top bar: current page name, portal name, today's date
                    $pageTitle = null;
                    foreach ($navItems as $navItem) {
                        if (request()->is(...\Illuminate\Support\Arr::flatten($navItem['match']))) {
                            $pageTitle = $navItem['label'];
                            break;
                        }
                    }
                    if (!$pageTitle && request()->is('*/notifications*')) {
                        $pageTitle = 'Notifications';
                    }
                    $pageTitle = $pageTitle ?: 'Dashboard';

                    if (request()->is('student*')) {
                        $portalName = 'Student Portal';
                    } elseif (request()->is('lecturer*')) {
                        $portalName = 'Lecturer Panel';
                    } elseif (request()->is('supervisor*')) {
                        $portalName = 'Supervisor Panel';
                    } else {
                        $portalName = 'Admin Console';
                    }

                    $todayLabel = now()
                        ->timezone(\App\Models\Attendance::TIMEZONE)
                        ->format('D, d M Y');
                    $dashboardHref = '/' . request()->segment(1) . '/dashboard';
                @endphp

                {{-- Sidebar: slide-out drawer on phones, fixed column on laptops --}}
                <aside
                    id="sidebar"
                    class="fixed inset-y-0 left-0 z-40 flex w-72 max-w-[85vw] shrink-0 -translate-x-full flex-col overflow-hidden bg-gradient-to-b from-[#1e3a8a] via-[#1d2f7a] to-[#0f1646] text-white shadow-2xl transition-transform duration-200 ease-out md:static md:z-auto md:max-w-none md:translate-x-0"
                    style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);"
                >

                    <div class="relative flex min-h-0 flex-1 flex-col">
                        {{-- Soft decorative glows --}}
                        <div
                            class="pointer-events-none absolute -left-16 -top-20 h-56 w-56 rounded-full bg-blue-400/15 blur-3xl"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="pointer-events-none absolute -bottom-24 -right-16 h-64 w-64 rounded-full bg-indigo-500/20 blur-3xl"
                            aria-hidden="true"
                        ></div>

                        {{-- Brand --}}
                        <div
                            class="relative flex flex-none items-center gap-3 border-b border-white/10 px-5 py-5">
                            <div
                                class="flex h-11 w-11 flex-none items-center justify-center rounded-2xl bg-gradient-to-br from-blue-400 to-indigo-500 text-white shadow-lg shadow-blue-950/40 ring-1 ring-white/20">
                                <i class="fa-solid fa-book-bookmark text-lg"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h1
                                    class="truncate text-[17px] font-extrabold leading-tight tracking-tight text-white">
                                    Smart eLogBook</h1>
                                <p
                                    class="mt-0.5 truncate text-[10px] font-semibold uppercase tracking-[0.16em] text-blue-200/70">
                                    Internship Management</p>
                            </div>
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
                                class="rounded-2xl border border-white/10 bg-white/[0.06] p-3.5 shadow-inner backdrop-blur-sm">
                                <div class="flex items-center gap-3">
                                    <div class="relative flex-none">
                                        <div
                                            class="flex h-11 w-11 items-center justify-center rounded-full bg-gradient-to-br from-sky-400 to-indigo-500 text-base font-bold text-white ring-2 ring-white/25">
                                            {{ strtoupper(substr($displayName ?? 'S', 0, 1)) }}
                                        </div>
                                        <span
                                            class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full border-2 border-[#1d2f7a] bg-emerald-400"
                                            title="Online"
                                        ></span>
                                    </div>
                                    <div class="min-w-0 flex-1">
                                        <h4
                                            class="truncate text-sm font-bold leading-tight text-white">
                                            {{ $displayName }}</h4>
                                        <p class="mt-0.5 truncate text-xs text-blue-200/80">
                                            {{ $roleShort }}</p>
                                    </div>
                                </div>

                                @if (request()->is('student*') && ($supervisorName || $industryName))
                                    <div class="mt-3 space-y-1.5 border-t border-white/10 pt-3">
                                        @if ($supervisorName)
                                            <div
                                                class="flex items-center gap-2.5 rounded-xl bg-white/5 px-2.5 py-2">
                                                <span
                                                    class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-indigo-400/20 text-xs text-indigo-200"
                                                ><i class="fa-solid fa-user-graduate"></i></span>
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-[10px] font-semibold uppercase tracking-[0.12em] text-blue-200/60">
                                                        Faculty Evaluator</p>
                                                    <p
                                                        class="truncate text-[13px] font-semibold text-white">
                                                        {{ $supervisorName }}</p>
                                                </div>
                                            </div>
                                        @endif
                                        @if ($industryName)
                                            <div
                                                class="flex items-center gap-2.5 rounded-xl bg-white/5 px-2.5 py-2">
                                                <span
                                                    class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-emerald-400/20 text-xs text-emerald-200"
                                                ><i class="fa-solid fa-building-user"></i></span>
                                                <div class="min-w-0">
                                                    <p
                                                        class="text-[10px] font-semibold uppercase tracking-[0.12em] text-blue-200/60">
                                                        Industry Supervisor</p>
                                                    <p
                                                        class="truncate text-[13px] font-semibold text-white">
                                                        {{ $industryName }}</p>
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif

                                @if (request()->is('supervisor*') && $currentSupervisor && $currentSupervisor->company_name)
                                    <div class="mt-3 border-t border-white/10 pt-3">
                                        <div
                                            class="flex items-center gap-2.5 rounded-xl bg-white/5 px-2.5 py-2">
                                            <span
                                                class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-emerald-400/20 text-xs text-emerald-200"
                                            ><i class="fa-solid fa-building"></i></span>
                                            <div class="min-w-0">
                                                <p
                                                    class="text-[10px] font-semibold uppercase tracking-[0.12em] text-blue-200/60">
                                                    Company</p>
                                                <p
                                                    class="truncate text-[13px] font-semibold text-white">
                                                    {{ $currentSupervisor->company_name }}</p>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            {{-- Menu --}}
                            <p
                                class="mb-2 mt-6 px-3 text-[10px] font-bold uppercase tracking-[0.18em] text-blue-200/50">
                                Menu</p>

                            <nav
                                id="sidebar-nav"
                                class="space-y-1"
                                aria-label="Main menu"
                            >
                                @foreach ($navItems as $item)
                                    @php $isActive = request()->is(...\Illuminate\Support\Arr::flatten($item['match'])); @endphp
                                    <a
                                        href="{{ $item['href'] }}"
                                        class="{{ $isActive ? 'bg-white/10 text-white ring-1 ring-white/10' : 'text-blue-100/80 hover:bg-white/5 hover:text-white' }} group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition"
                                        @if ($isActive) aria-current="page" @endif
                                    >
                                        @if ($isActive)
                                            <span
                                                class="absolute -left-3 top-1/2 h-6 w-1 -translate-y-1/2 rounded-r-full bg-sky-400"
                                                aria-hidden="true"
                                            ></span>
                                        @endif
                                        <span
                                            class="{{ $isActive ? 'bg-gradient-to-br from-sky-400 to-blue-600 text-white shadow-md shadow-blue-950/40' : 'bg-white/5 text-blue-200 group-hover:bg-white/10 group-hover:text-white' }} flex h-8 w-8 flex-none items-center justify-center rounded-lg text-[13px] transition"
                                        >
                                            <i class="fa-solid {{ $item['icon'] }}"></i>
                                        </span>
                                        <span class="truncate">{{ $item['label'] }}</span>
                                        @if ($isActive)
                                            <i
                                                class="fa-solid fa-chevron-right ml-auto text-[10px] text-blue-200/70"></i>
                                        @endif
                                    </a>
                                @endforeach
                            </nav>
                        </div>

                        {{-- Pinned footer: log out + version --}}
                        <div class="relative flex-none border-t border-white/10 px-3 pb-3 pt-3">
                            <a
                                href="/logout"
                                class="group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold text-rose-200 transition hover:bg-rose-500/15 hover:text-white"
                            >
                                <span
                                    class="flex h-8 w-8 flex-none items-center justify-center rounded-lg bg-rose-400/10 text-[13px] text-rose-200 transition group-hover:bg-rose-500/30 group-hover:text-white"
                                ><i class="fa-solid fa-arrow-right-from-bracket"></i></span>
                                Log out
                            </a>
                            <div
                                class="mt-2 flex items-center justify-between px-3 text-[11px] text-blue-200/50">
                                <span>v2.5 Full-Routing</span>
                                <span class="inline-flex items-center gap-1.5">
                                    <span
                                        class="h-1.5 w-1.5 animate-pulse rounded-full bg-emerald-400"
                                    ></span> System online
                                </span>
                            </div>
                        </div>
                    </div>
                </aside>

                <main class="flex min-w-0 flex-1 flex-col overflow-y-auto overflow-x-hidden">
                    <header
                        class="shadow-xs sticky top-0 z-20 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-slate-200 bg-white/95 px-4 backdrop-blur md:h-[4.5rem] md:px-8"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <button
                                type="button"
                                id="sidebar-toggle"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100 md:hidden"
                                aria-label="Open menu"
                                aria-controls="sidebar"
                                aria-expanded="false"
                            >
                                <i class="fa-solid fa-bars text-lg"></i>
                            </button>
                            <div class="min-w-0">
                                <p
                                    class="truncate text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400 md:text-[11px]">
                                    UiTM E-Logbook <span class="mx-1 text-slate-300">/</span>
                                    {{ $portalName }}
                                </p>
                                <h2
                                    id="page-title"
                                    class="truncate text-base font-bold leading-tight tracking-tight text-slate-900 md:text-xl"
                                >
                                    {{ $pageTitle }}</h2>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2 md:gap-3">
                            {{-- Today's date --}}
                            <span
                                class="hidden items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-xs font-semibold text-slate-600 lg:inline-flex"
                            >
                                <i class="fa-regular fa-calendar text-blue-600"></i>
                                {{ $todayLabel }}
                            </span>

                            @if (request()->is('student*') || request()->is('lecturer*') || request()->is('supervisor*'))
                                @php $notifRole = request()->segment(1); @endphp

                                {{-- Notification bell + dropdown panel --}}
                                <div
                                    class="relative"
                                    id="notif-wrap"
                                >
                                    <button
                                        type="button"
                                        id="notif-bell"
                                        data-count-url="/{{ $notifRole }}/notifications/unread-count"
                                        data-list-url="/{{ $notifRole }}/notifications"
                                        data-read-all-url="/{{ $notifRole }}/notifications/read-all"
                                        aria-label="Notifications"
                                        aria-haspopup="true"
                                        aria-expanded="false"
                                        aria-controls="notif-panel"
                                        class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-600 transition hover:bg-slate-50"
                                    >
                                        <i class="fa-solid fa-bell text-lg"></i>
                                        <span
                                            id="notif-badge"
                                            class="absolute -right-1 -top-1 hidden min-w-[18px] rounded-full bg-rose-600 px-1 text-center text-[10px] font-bold leading-[18px] text-white ring-2 ring-white"
                                        >0</span>
                                    </button>

                                    <div
                                        id="notif-panel"
                                        class="absolute right-0 top-full z-40 mt-2 hidden w-[calc(100vw-2rem)] max-w-sm overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl sm:w-96"
                                    >
                                        <div
                                            class="flex items-center justify-between gap-3 border-b border-slate-100 px-4 py-3">
                                            <div>
                                                <h3 class="text-sm font-bold text-slate-800">
                                                    Notifications</h3>
                                                <p
                                                    id="notif-panel-sub"
                                                    class="text-xs text-slate-500"
                                                ></p>
                                            </div>
                                            <button
                                                type="button"
                                                id="notif-panel-readall"
                                                class="hidden rounded-lg px-2.5 py-1.5 text-xs font-semibold text-blue-700 transition hover:bg-blue-50"
                                            >Mark all as read</button>
                                        </div>

                                        <div
                                            id="notif-panel-list"
                                            class="max-h-[60vh] overflow-y-auto"
                                        ></div>

                                        <a
                                            href="/{{ $notifRole }}/notifications"
                                            class="block border-t border-slate-100 bg-slate-50 px-4 py-3 text-center text-sm font-semibold text-blue-700 transition hover:bg-slate-100"
                                        >
                                            View all notifications
                                        </a>
                                    </div>
                                </div>

                                <script>
                                    (function() {
                                        var wrap = document.getElementById('notif-wrap');
                                        var bell = document.getElementById('notif-bell');
                                        var badge = document.getElementById('notif-badge');
                                        var panel = document.getElementById('notif-panel');
                                        var list = document.getElementById('notif-panel-list');
                                        var sub = document.getElementById('notif-panel-sub');
                                        var readAllBtn = document.getElementById('notif-panel-readall');
                                        if (!wrap || !bell || !badge || !panel) return;

                                        var unread = 0;
                                        var MAX_ITEMS = 8;

                                        function csrf() {
                                            var m = document.querySelector('meta[name="csrf-token"]');
                                            return m ? m.getAttribute('content') : '';
                                        }

                                        function el(tag, cls, text) {
                                            var node = document.createElement(tag);
                                            if (cls) node.className = cls;
                                            if (text !== undefined && text !== null) node.textContent = text;
                                            return node;
                                        }

                                        function setUnread(n) {
                                            unread = n;
                                            badge.textContent = n > 99 ? '99+' : n;
                                            badge.classList.toggle('hidden', n === 0);
                                            sub.textContent = n > 0 ? n + ' unread' : 'You are all caught up';
                                            readAllBtn.classList.toggle('hidden', n === 0);
                                        }

                                        function refreshCount() {
                                            return fetch(bell.getAttribute('data-count-url'), {
                                                    headers: {
                                                        'Accept': 'application/json',
                                                        'X-Requested-With': 'XMLHttpRequest'
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                .then(function(r) {
                                                    return r.ok ? r.json() : null;
                                                })
                                                .then(function(d) {
                                                    if (!d) return;
                                                    setUnread(parseInt(d.count, 10) || 0);
                                                })
                                                .catch(function() {});
                                        }

                                        function showMessage(iconClass, text) {
                                            list.innerHTML = '';
                                            var box = el('div', 'px-6 py-10 text-center text-sm text-slate-500');
                                            var icon = el('i', 'fa-solid ' + iconClass + ' mb-2 text-2xl text-slate-300');
                                            box.appendChild(icon);
                                            box.appendChild(el('p', '', text));
                                            list.appendChild(box);
                                        }

                                        // Hitting the /open route marks it as read on the server; the redirect is not followed.
                                        function markRead(data, row, btn, dot, title) {
                                            if (data.unread !== '1') return;
                                            data.unread = '0';
                                            btn.classList.remove('bg-blue-50/60');
                                            if (dot) dot.remove();
                                            title.classList.remove('font-bold', 'text-slate-900');
                                            title.classList.add('font-semibold', 'text-slate-700');

                                            fetch(data.openUrl, {
                                                method: 'GET',
                                                credentials: 'same-origin',
                                                redirect: 'manual',
                                                headers: {
                                                    'X-Requested-With': 'XMLHttpRequest'
                                                }
                                            }).catch(function() {}).then(refreshCount);
                                        }

                                        function buildRow(src) {
                                            var data = {
                                                openUrl: src.getAttribute('data-open-url'),
                                                type: src.getAttribute('data-type'),
                                                unread: src.getAttribute('data-unread'),
                                                title: src.getAttribute('data-title') || '',
                                                body: src.getAttribute('data-body') || '',
                                                time: src.getAttribute('data-time') || '',
                                                ago: src.getAttribute('data-ago') || '',
                                                count: parseInt(src.getAttribute('data-count'), 10) || 0,
                                                icon: src.getAttribute('data-icon') || 'fa-bell',
                                                iconClass: src.getAttribute('data-icon-class') ||
                                                    'bg-slate-100 text-slate-600'
                                            };

                                            var row = el('div', 'border-b border-slate-100 last:border-b-0');
                                            var btn = el('button',
                                                'flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50' +
                                                (data.unread === '1' ? ' bg-blue-50/60' : ''));
                                            btn.type = 'button';

                                            var iconBox = el('span',
                                                'flex h-10 w-10 flex-none items-center justify-center rounded-xl ' + data
                                                .iconClass);
                                            iconBox.appendChild(el('i', 'fa-solid ' + data.icon));
                                            btn.appendChild(iconBox);

                                            var textWrap = el('div', 'min-w-0 flex-1');
                                            var title = el('p', 'break-words text-sm ' + (data.unread === '1' ?
                                                    'font-bold text-slate-900' : 'font-semibold text-slate-700'),
                                                data.title + (data.count > 1 ? ' (' + data.count + ')' : ''));
                                            textWrap.appendChild(title);

                                            var body = null;
                                            if (data.body) {
                                                body = el('p', 'mt-0.5 truncate text-sm text-slate-500', data.body);
                                                textWrap.appendChild(body);
                                            }
                                            if (data.ago) {
                                                textWrap.appendChild(el('p', 'mt-1 text-xs text-slate-400', data.ago));
                                            }
                                            btn.appendChild(textWrap);

                                            var dot = null;
                                            if (data.unread === '1') {
                                                dot = el('span', 'mt-2 h-2.5 w-2.5 flex-none rounded-full bg-blue-600');
                                                btn.appendChild(dot);
                                            }
                                            row.appendChild(btn);

                                            // Expanded details (full message, exact time, optional link)
                                            var detail = el('div', 'hidden px-4 pb-3 pl-[4.25rem]');
                                            if (data.time) detail.appendChild(el('p', 'text-xs text-slate-400', data.time));
                                            if (data.type !== 'reminder' && data.openUrl) {
                                                var link = el('a',
                                                    'mt-2 inline-block rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50',
                                                    'Open page');
                                                link.href = data.openUrl;
                                                detail.appendChild(link);
                                            }
                                            row.appendChild(detail);

                                            btn.addEventListener('click', function() {
                                                var open = detail.classList.toggle('hidden') === false;
                                                if (body) {
                                                    body.classList.toggle('truncate', !open);
                                                    body.classList.toggle('whitespace-pre-line', open);
                                                    body.classList.toggle('break-words', open);
                                                }
                                                if (open) markRead(data, row, btn, dot, title);
                                            });

                                            return row;
                                        }

                                        function loadList() {
                                            showMessage('fa-spinner fa-spin', 'Loading...');

                                            fetch(bell.getAttribute('data-list-url'), {
                                                    headers: {
                                                        'Accept': 'text/html',
                                                        'X-Requested-With': 'XMLHttpRequest'
                                                    },
                                                    credentials: 'same-origin'
                                                })
                                                .then(function(r) {
                                                    return r.ok ? r.text() : Promise.reject();
                                                })
                                                .then(function(html) {
                                                    var doc = new DOMParser().parseFromString(html, 'text/html');
                                                    var items = doc.querySelectorAll('[data-notif-item]');

                                                    if (!items.length) {
                                                        showMessage('fa-bell-slash', 'No notifications yet.');
                                                        return;
                                                    }

                                                    list.innerHTML = '';
                                                    Array.prototype.slice.call(items, 0, MAX_ITEMS).forEach(function(src) {
                                                        list.appendChild(buildRow(src));
                                                    });
                                                })
                                                .catch(function() {
                                                    showMessage('fa-triangle-exclamation',
                                                        'Could not load notifications. Please try again.');
                                                });
                                        }

                                        function openPanel() {
                                            panel.classList.remove('hidden');
                                            bell.setAttribute('aria-expanded', 'true');
                                            loadList();
                                            refreshCount();
                                        }

                                        function closePanel() {
                                            panel.classList.add('hidden');
                                            bell.setAttribute('aria-expanded', 'false');
                                        }

                                        bell.addEventListener('click', function(e) {
                                            e.stopPropagation();
                                            if (panel.classList.contains('hidden')) openPanel();
                                            else closePanel();
                                        });

                                        readAllBtn.addEventListener('click', function() {
                                            readAllBtn.disabled = true;
                                            fetch(bell.getAttribute('data-read-all-url'), {
                                                method: 'POST',
                                                credentials: 'same-origin',
                                                redirect: 'manual',
                                                headers: {
                                                    'X-CSRF-TOKEN': csrf(),
                                                    'X-Requested-With': 'XMLHttpRequest',
                                                    'Accept': 'application/json'
                                                }
                                            }).catch(function() {}).then(function() {
                                                readAllBtn.disabled = false;
                                                refreshCount();
                                                if (!panel.classList.contains('hidden')) loadList();
                                            });
                                        });

                                        document.addEventListener('click', function(e) {
                                            if (!panel.classList.contains('hidden') && !wrap.contains(e.target))
                                                closePanel();
                                        });

                                        document.addEventListener('keydown', function(e) {
                                            if (e.key === 'Escape' && !panel.classList.contains('hidden')) {
                                                closePanel();
                                                bell.focus();
                                            }
                                        });

                                        refreshCount();
                                        setInterval(refreshCount, 60000);
                                        document.addEventListener('visibilitychange', function() {
                                            if (!document.hidden) refreshCount();
                                        });
                                    })();
                                </script>
                            @endif

                            {{-- Profile menu --}}
                            <div
                                class="relative"
                                id="profile-wrap"
                            >
                                <button
                                    type="button"
                                    id="profile-btn"
                                    aria-haspopup="true"
                                    aria-expanded="false"
                                    aria-controls="profile-menu"
                                    class="flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white py-1.5 pl-1.5 pr-2.5 transition hover:bg-slate-50 md:pr-3"
                                >
                                    <span
                                        class="flex h-8 w-8 items-center justify-center rounded-lg bg-gradient-to-br from-blue-600 to-indigo-600 text-sm font-bold text-white"
                                    >
                                        {{ strtoupper(substr($displayName ?? 'S', 0, 1)) }}
                                    </span>
                                    <span class="hidden text-left leading-tight md:block">
                                        <span
                                            class="block max-w-[9rem] truncate text-sm font-semibold text-slate-800"
                                        >{{ $displayName }}</span>
                                        <span
                                            class="block text-[11px] text-slate-500">{{ $portalName }}</span>
                                    </span>
                                    <i
                                        class="fa-solid fa-chevron-down hidden text-[10px] text-slate-400 md:block"></i>
                                </button>

                                <div
                                    id="profile-menu"
                                    class="absolute right-0 top-full z-40 mt-2 hidden w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl"
                                >
                                    <div class="border-b border-slate-100 bg-slate-50 px-4 py-3">
                                        <p class="truncate text-sm font-bold text-slate-800">
                                            {{ $displayName }}</p>
                                        <p class="text-xs text-slate-500">{{ $roleLabel }}</p>
                                    </div>
                                    <div class="p-1.5">
                                        <a
                                            href="{{ $dashboardHref }}"
                                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                                        >
                                            <i class="fa-solid fa-house w-4 text-slate-400"></i>
                                            Dashboard
                                        </a>
                                        <a
                                            href="/logout"
                                            class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-rose-600 transition hover:bg-rose-50"
                                        >
                                            <i
                                                class="fa-solid fa-arrow-right-from-bracket w-4"></i>
                                            Log out
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <script>
                                (function() {
                                    var wrap = document.getElementById('profile-wrap');
                                    var btn = document.getElementById('profile-btn');
                                    var menu = document.getElementById('profile-menu');
                                    if (!wrap || !btn || !menu) return;

                                    function close() {
                                        menu.classList.add('hidden');
                                        btn.setAttribute('aria-expanded', 'false');
                                    }

                                    btn.addEventListener('click', function(e) {
                                        e.stopPropagation();
                                        var open = menu.classList.toggle('hidden') === false;
                                        btn.setAttribute('aria-expanded', open ? 'true' : 'false');
                                    });

                                    document.addEventListener('click', function(e) {
                                        if (!wrap.contains(e.target)) close();
                                    });

                                    document.addEventListener('keydown', function(e) {
                                        if (e.key === 'Escape') close();
                                    });
                                })();
                            </script>
                        </div>
                    </header>

                    <div
                        id="page-content"
                        class="mx-auto w-full max-w-7xl flex-1 p-4 md:p-8"
                        style="padding-bottom: max(1rem, env(safe-area-inset-bottom));"
                    >

                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <script>
            (function() {
                var sidebar = document.getElementById('sidebar');
                var overlay = document.getElementById('sidebar-overlay');
                var openBtn = document.getElementById('sidebar-toggle');
                var closeBtn = document.getElementById('sidebar-close');
                if (!sidebar || !overlay || !openBtn) return;

                function openMenu() {
                    sidebar.classList.remove('-translate-x-full');
                    overlay.classList.remove('hidden');
                    openBtn.setAttribute('aria-expanded', 'true');
                }

                function closeMenu() {
                    sidebar.classList.add('-translate-x-full');
                    overlay.classList.add('hidden');
                    openBtn.setAttribute('aria-expanded', 'false');
                }

                openBtn.addEventListener('click', openMenu);
                overlay.addEventListener('click', closeMenu);
                if (closeBtn) closeBtn.addEventListener('click', closeMenu);

                sidebar.querySelectorAll('a').forEach(function(link) {
                    link.addEventListener('click', closeMenu);
                });

                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') closeMenu();
                });

                window.addEventListener('resize', function() {
                    if (window.innerWidth >= 768) closeMenu();
                });
            })();
        </script>

        <div
            id="nav-progress"
            class="pointer-events-none fixed left-0 top-0 z-[100] h-0.5 w-0 bg-sky-400 opacity-0 transition-all duration-300"
        ></div>

        <script>
            (function() {
                var content = document.getElementById('page-content');
                var main = document.querySelector('main');
                var bar = document.getElementById('nav-progress');
                if (!content || !main) return;

                var controller = null;
                var seg = function(p) {
                    return p.split('/')[1];
                };

                function progress(state) {
                    if (!bar) return;
                    if (state === 'start') {
                        bar.style.transition = 'none';
                        bar.style.width = '0';
                        bar.style.opacity = '1';
                        void bar.offsetWidth;
                        bar.style.transition = 'width 8s cubic-bezier(.1,.7,.2,1)';
                        bar.style.width = '80%';
                    } else {
                        bar.style.transition = 'width .2s';
                        bar.style.width = '100%';
                        setTimeout(function() {
                            bar.style.opacity = '0';
                        }, 220);
                    }
                }

                function handles(a, e) {
                    if (a.hasAttribute('data-live-nav')) return false;
                    if (e.defaultPrevented || e.button !== 0 || e.metaKey || e.ctrlKey || e.shiftKey ||
                        e.altKey) return false;
                    if ((a.target && a.target !== '_self') || a.hasAttribute('download') || a
                        .hasAttribute('data-no-ajax')) return false;
                    var u = new URL(a.href, location.href);
                    if (u.origin !== location.origin) return false;
                    if (u.pathname === location.pathname && u.search === location.search && u.hash)
                        return false; // #anchor
                    if (seg(u.pathname) !== seg(location.pathname))
                        return false; // other portal / logout
                    if (/(logout|download|export|print)|\.[a-z0-9]{2,5}$/i.test(u.pathname))
                        return false;
                    return true;
                }

                function runScripts(root) {
                    root.querySelectorAll('script').forEach(function(old) {
                        var s = document.createElement('script');
                        Array.prototype.forEach.call(old.attributes, function(at) {
                            s.setAttribute(at.name, at.value);
                        });
                        s.text = old.textContent;
                        old.parentNode.replaceChild(s, old);
                    });
                }

                // spinner keyframes (added once)
                var spinStyle = document.createElement('style');
                spinStyle.textContent = '@keyframes navspin{to{transform:rotate(360deg)}}';
                document.head.appendChild(spinStyle);

                function hideSpinner() {
                    var old = document.getElementById('nav-spinner');
                    if (old) old.remove();
                }

                function showSpinner() {
                    hideSpinner();
                    content.style.position = 'relative';

                    var overlay = document.createElement('div');
                    overlay.id = 'nav-spinner';
                    overlay.setAttribute('aria-busy', 'true');
                    overlay.style.cssText =
                        'position:absolute;inset:0;z-index:30;border-radius:24px;' +
                        'background:rgba(255,255,255,.55);backdrop-filter:blur(1px);';

                    // sticky = always centered in the part of the page you can currently see
                    var holder = document.createElement('div');
                    holder.style.cssText =
                        'position:sticky;top:40%;display:flex;justify-content:center;pointer-events:none;';

                    var disc = document.createElement('div');
                    disc.style.cssText =
                        'width:60px;height:60px;border-radius:50%;background:#fff;' +
                        'display:flex;align-items:center;justify-content:center;' +
                        'box-shadow:0 12px 28px -8px rgba(37,56,140,.35);';

                    var ring = document.createElement('div');
                    ring.style.cssText =
                        'width:30px;height:30px;border-radius:50%;' +
                        'border:3px solid rgba(66,99,240,.2);border-top-color:#4263f0;' +
                        'animation:navspin .7s linear infinite;';

                    disc.appendChild(ring);
                    holder.appendChild(disc);
                    overlay.appendChild(holder);
                    content.appendChild(overlay);
                }

                function load(url, push) {
                    if (controller) controller.abort();
                    controller = new AbortController();

                    // Same page, different query (e.g. ?month=...) -> keep the scroll position
                    var samePath = new URL(url, location.href).pathname === location.pathname;
                    var savedTop = main.scrollTop;

                    progress('start');
                    showSpinner();

                    fetch(url, {
                            credentials: 'same-origin', signal: controller.signal,
                            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'text/html' }
                        })
                        .then(function(res) {
                            if (!res.ok) throw new Error('hard: HTTP ' + res.status);
                            if ((res.headers.get('content-type') || '').indexOf('text/html') === -1) throw new Error('hard: not html');
                            return res.text().then(function(html) { return { html: html, url: res.url }; });
                        })
                        .then(function(r) {
                            var doc = new DOMParser().parseFromString(r.html, 'text/html');
                            var next = doc.getElementById('page-content');
                            if (!next) throw new Error('hard: no #page-content');
                            if (doc.getElementById('cm-root')) throw new Error('hard: chat page');
                            if (next.querySelector('[data-hard-load]')) throw new Error('hard: data-hard-load');
                            if (seg(new URL(r.url).pathname) !== seg(location.pathname)) throw new Error('hard: different portal');

                            if (push) history.pushState({}, '', r.url);
                            document.title = doc.title;

                            var t = doc.getElementById('page-title'), ct = document.getElementById('page-title');
                            if (t && ct) ct.textContent = t.textContent;
                            var n = doc.getElementById('sidebar-nav'), cn = document.getElementById('sidebar-nav');
                            if (n && cn) cn.innerHTML = n.innerHTML;

                            content.innerHTML = next.innerHTML;     // also removes the spinner
                            runScripts(content);

                            if (samePath) main.scrollTop = savedTop;  // stay where you were
                            else main.scrollTo(0, 0);                 // new page -> start at top

                            var ov = document.getElementById('sidebar-overlay');
                            if (ov && window.innerWidth < 768) ov.click();   // close mobile drawer
                            document.dispatchEvent(new CustomEvent('page:loaded', { detail: { url: r.url } }));
                            progress('done');
                        })
                        .catch(function(err) {
                            if (err && err.name === 'AbortError') return;
                            hideSpinner();
                            console.warn('ajax-nav fell back to full reload:', err);
                            if (push) location.href = url;
                            else location.reload();
                        });
                }

                document.addEventListener('click', function(e) {
                    var a = e.target.closest('#sidebar a[href], #page-content a[href]');
                    if (!a || !handles(a, e)) return;
                    if (document.getElementById('cm-root'))
                        return; // leaving the chat = normal load
                    e.preventDefault();
                    load(a.href, true);
                });

                window.addEventListener('popstate', function() {
                    if (document.getElementById('cm-root')) {
                        location.reload();
                        return;
                    }
                    load(location.href, false);
                });
            })();
        </script>
    @else
        <div class="min-h-screen">
            @yield('content')
        </div>
    @endif
</body>

</html>
