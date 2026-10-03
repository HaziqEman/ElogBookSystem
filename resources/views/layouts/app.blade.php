<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#1e3a8a">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="eLogBook">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('pwa/icon-192.svg') }}" type="image/svg+xml">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>E-Logbook System</title>

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @else
        <script src="https://cdn.tailwindcss.com"></script>
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased">
    @php
        $showDashboardShell = request()->is('student*') || request()->is('lecturer*') || request()->is('admin*');
    @endphp

    @if($showDashboardShell)
        <div class="flex flex-col h-[100dvh] overflow-hidden">
            {{-- Top simulator bar --}}
            <div class="bg-slate-900 text-white px-3 md:px-4 py-2 flex flex-wrap justify-between items-center text-xs gap-2 shadow-inner shrink-0"
                 style="padding-top: max(0.5rem, env(safe-area-inset-top)); padding-left: max(0.75rem, env(safe-area-inset-left)); padding-right: max(0.75rem, env(safe-area-inset-right));">
                <div class="flex items-center gap-2">
                    <a href="{{ route('login.home') }}" title="Return to the login page to access another system module." class="bg-amber-500 text-slate-950 font-bold px-2 py-0.5 rounded-sm hover:bg-amber-400 transition cursor-pointer text-[11px] md:text-xs">PROPOSAL SIMULATOR</a>
                    <p class="hidden sm:block">Click roles to switch portal views:</p>
                </div>
                <div class="flex gap-1.5 md:gap-2">
                    <a href="{{ route('module.switch', ['role' => 'student']) }}" class="px-2 md:px-3 py-1 rounded font-semibold text-[11px] md:text-xs {{ request()->is('student*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} transition">Student<span class="hidden sm:inline"> Module</span></a>
                    <a href="{{ route('module.switch', ['role' => 'lecturer']) }}" class="px-2 md:px-3 py-1 rounded font-semibold text-[11px] md:text-xs {{ request()->is('lecturer*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} transition">Lecturer<span class="hidden sm:inline"> Module</span></a>
                    <a href="{{ route('module.switch', ['role' => 'admin']) }}" class="px-2 md:px-3 py-1 rounded font-semibold text-[11px] md:text-xs {{ request()->is('admin*') ? 'bg-blue-600 text-white' : 'bg-slate-700 text-slate-300 hover:bg-slate-600' }} transition">Admin<span class="hidden sm:inline"> Module</span></a>
                </div>
            </div>

            <div class="relative flex flex-1 min-h-0 overflow-hidden">
                {{-- Dark overlay (phones only, shown when the drawer is open) --}}
                <div id="sidebar-overlay" class="fixed inset-0 z-30 bg-slate-900/60 hidden md:hidden" aria-hidden="true"></div>

                {{-- Sidebar: slide-out drawer on phones, fixed column on laptops --}}
                <aside id="sidebar"
                       class="fixed inset-y-0 left-0 z-40 w-64 max-w-[85vw] -translate-x-full transition-transform duration-200 ease-out md:static md:z-auto md:max-w-none md:translate-x-0 bg-gradient-to-b from-blue-900 to-indigo-950 text-white flex flex-col justify-between shrink-0 shadow-xl overflow-y-auto"
                       style="padding-top: env(safe-area-inset-top); padding-bottom: env(safe-area-inset-bottom);">
                    <div>
                        <div class="p-6 border-b border-blue-800/50 flex items-center gap-3">
                            <div class="bg-white/10 p-2 rounded-lg text-blue-300">
                                <i class="fa-solid fa-book-bookmark text-xl"></i>
                            </div>
                            <div class="min-w-0 flex-1">
                                <h1 class="font-bold tracking-wide leading-tight text-base uppercase text-white">Smart eLogBook</h1>
                                <p class="text-[10px] text-blue-300 tracking-wider">INTERNSHIP MANAGEMENT SYSTEM</p>
                            </div>
                            <button type="button" id="sidebar-close" class="md:hidden shrink-0 w-9 h-9 flex items-center justify-center rounded-lg text-blue-200 hover:bg-white/10" aria-label="Close menu">
                                <i class="fa-solid fa-xmark text-lg"></i>
                            </button>
                        </div>

                        @php
                            $currentStudent = request()->is('student*') ? Auth::guard('student')->user() : null;
                            $currentLecturer = request()->is('lecturer*') ? Auth::guard('lecturer')->user() : null;
                            $currentAdmin = request()->is('admin*') ? Auth::guard('admin')->user() : null;

                            if (request()->is('student*') && $currentStudent) {
                                $displayName = $currentStudent->name;
                                $roleLabel = 'Logged in as Student';
                                $supervisorName = optional($currentStudent->lecturer)->name;
                            } elseif (request()->is('lecturer*') && $currentLecturer) {
                                $displayName = $currentLecturer->name;
                                $roleLabel = 'Faculty Evaluator';
                                $supervisorName = null;
                            } elseif (request()->is('admin*') && $currentAdmin) {
                                $displayName = $currentAdmin->name ?? 'System Administrator';
                                $roleLabel = 'System Administrator';
                                $supervisorName = null;
                            } else {
                                $displayName = 'System User';
                                $roleLabel = 'Guest';
                                $supervisorName = null;
                            }
                        @endphp

                        <div class="p-4 mx-3 my-4 bg-white/5 rounded-xl border border-white/5">
                            <div class="flex items-center gap-3 mb-3">
                                <div class="w-10 h-10 shrink-0 rounded-full bg-blue-500 flex items-center justify-center font-bold text-sm border-2 border-blue-400">
                                    {{ strtoupper(substr($displayName ?? 'S', 0, 1)) }}
                                </div>
                                <div class="min-w-0">
                                    <p class="text-xs text-blue-200">{{ $roleLabel }}</p>
                                    <h4 class="font-semibold text-sm truncate">{{ $displayName }}</h4>
                                </div>
                            </div>
                            @if(request()->is('student*') && $supervisorName)
                                <div class="rounded-xl bg-white/10 p-3 text-sm text-slate-200">
                                    <p class="text-xs uppercase tracking-[0.12em] text-slate-400">Faculty Evaluator</p>
                                    <p class="mt-1 font-semibold">{{ $supervisorName }}</p>
                                </div>
                            @endif
                        </div>

                        <nav class="px-3 space-y-1">
                            @if(request()->is('student*'))
                                <a href="/student/dashboard" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition bg-blue-800/60 text-white">
                                    <i class="fa-solid fa-chart-pie w-5"></i> Dashboard Overview
                                </a>
                                <a href="/student/messages" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                    <i class="fa-solid fa-comments w-5"></i> Messages
                                </a>
                                <a href="/student/feedback" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                    <i class="fa-solid fa-comments-dollar w-5"></i> Supervisor Feedback
                                </a>
                            @elseif(request()->is('lecturer*'))
                                <a href="/lecturer/dashboard" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition bg-blue-800/60 text-white">
                                    <i class="fa-solid fa-gauge-high w-5"></i> Lecturer Dashboard
                                </a>
                                <a href="/lecturer/messages" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                    <i class="fa-solid fa-comments w-5"></i> Messages
                                </a>
                            @else
                                <a href="/admin/dashboard" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition bg-blue-800/60 text-white">
                                    <i class="fa-solid fa-sliders w-5"></i> System Overview
                                </a>
                                <a href="/admin/students" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                    <i class="fa-solid fa-file-invoice w-5"></i> Manage Students
                                </a>
                                <a href="/admin/lecturers" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                    <i class="fa-solid fa-user-tie w-5"></i> Manage Lecturers
                                </a>
                                <a href="/admin/reports" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                    <i class="fa-solid fa-chart-line w-5"></i> Reports
                                </a>
                            @endif
                            <a href="/logout" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-lg font-medium text-sm text-left transition text-blue-200 hover:bg-white/5 hover:text-white">
                                <i class="fa-solid fa-arrow-right-from-bracket w-5"></i> Log Out
                            </a>
                        </nav>
                    </div>

                    <div class="p-4 border-t border-blue-900/60 text-xs text-blue-300/60 flex justify-between items-center">
                        <span>v2.5 Full-Routing</span>
                        <i class="fa-solid fa-circle text-emerald-400 text-[8px] animate-pulse"></i>
                    </div>
                </aside>

                <main class="flex-1 min-w-0 flex flex-col overflow-y-auto overflow-x-hidden">
                    <header class="bg-white border-b border-slate-200 h-14 md:h-16 flex items-center justify-between gap-3 px-4 md:px-8 shrink-0 shadow-xs">
                        <div class="flex items-center gap-3 min-w-0">
                            <button type="button" id="sidebar-toggle" class="md:hidden shrink-0 w-10 h-10 flex items-center justify-center rounded-lg text-slate-700 hover:bg-slate-100" aria-label="Open menu" aria-controls="sidebar" aria-expanded="false">
                                <i class="fa-solid fa-bars text-lg"></i>
                            </button>
                            <h2 class="text-sm md:text-lg font-bold text-slate-800 tracking-tight truncate">
                                {{ request()->is('student*') ? 'UITM E-LOGBOOK PORTAL > Student Portal' : (request()->is('lecturer*') ? 'UITM E-LOGBOOK PORTAL > Lecturer Panel' : 'UITM E-LOGBOOK PORTAL > Admin Console') }}
                            </h2>
                        </div>
                        <div class="hidden md:flex items-center gap-4 shrink-0">
                            <span class="text-xs bg-slate-100 text-slate-600 px-3 py-1.5 font-mono rounded-lg border border-slate-200">
                                {{ request()->is('student*') ? 'Student > Dashboard' : (request()->is('lecturer*') ? 'Lecturer > Review' : 'Admin > Overview') }}
                            </span>
                        </div>
                    </header>

                    <div class="p-4 md:p-8 max-w-7xl w-full mx-auto flex-1" style="padding-bottom: max(1rem, env(safe-area-inset-bottom));">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>

        <script>
            (function () {
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

                sidebar.querySelectorAll('a').forEach(function (link) {
                    link.addEventListener('click', closeMenu);
                });

                document.addEventListener('keydown', function (e) {
                    if (e.key === 'Escape') closeMenu();
                });

                window.addEventListener('resize', function () {
                    if (window.innerWidth >= 768) closeMenu();
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