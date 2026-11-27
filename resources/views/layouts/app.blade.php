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

    @if (request()->is('student*'))
        @include('partials.google-fonts', [
            'includeCaveat' => request()->is('student/dashboard*') || request()->is('student/logbooks*'),
        ])
    @endif

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

<body
    class="bg-slate-50 font-sans text-slate-800 antialiased"
    @if (request()->is('student*')) style="font-family:'Plus Jakarta Sans',ui-sans-serif,system-ui,sans-serif" @endif
>

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

                    if (request()->is('student*') && $currentStudent) {
                        $displayName = $currentStudent->name;
                        $roleLabel = 'Logged in as Student';
                        $roleShort = 'Student';
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

                <x-dashboard.sidebar
                    :display-name="$displayName"
                    :role-short="$roleShort"
                    :supervisor-name="$supervisorName"
                    :industry-name="$industryName"
                    :current-supervisor="$currentSupervisor"
                    :nav-items="$navItems"
                />

                {{-- TOP BAR + MAIN CONTENT --}}
                <main class="flex min-w-0 flex-1 flex-col overflow-y-auto overflow-x-hidden">
                    <x-dashboard.header
                        :page-title="$pageTitle"
                        :portal-name="$portalName"
                        :today-label="$todayLabel"
                        :display-name="$displayName"
                        :role-label="$roleLabel"
                        :dashboard-href="$dashboardHref"
                    />

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

        @if ($currentStudent)
            @include('partials.reminder-popup', ['reminderStudentId' => $currentStudent->student_id])
        @endif

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

        @include('components.dashboard.scripts.async-navigation')
    @else
        <div class="min-h-screen">
            @yield('content')
        </div>
    @endif
</body>

</html>
