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

                <x-layouts.app-sidebar
                    :display-name="$displayName"
                    :role-short="$roleShort"
                    :supervisor-name="$supervisorName"
                    :industry-name="$industryName"
                    :current-supervisor="$currentSupervisor"
                    :nav-items="$navItems"
                />

                {{-- TOP BAR + MAIN CONTENT --}}
                <main class="flex min-w-0 flex-1 flex-col overflow-y-auto overflow-x-hidden">
                    <x-layouts.app-header
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
                            credentials: 'same-origin',
                            signal: controller.signal,
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'text/html'
                            }
                        })
                        .then(function(res) {
                            if (!res.ok) throw new Error('hard: HTTP ' + res.status);
                            if ((res.headers.get('content-type') || '').indexOf('text/html') === -1)
                                throw new Error('hard: not html');
                            return res.text().then(function(html) {
                                return {
                                    html: html,
                                    url: res.url
                                };
                            });
                        })
                        .then(function(r) {
                            var doc = new DOMParser().parseFromString(r.html, 'text/html');
                            var next = doc.getElementById('page-content');
                            if (!next) throw new Error('hard: no #page-content');
                            if (doc.getElementById('cm-root')) throw new Error('hard: chat page');
                            if (next.querySelector('[data-hard-load]')) throw new Error(
                                'hard: data-hard-load');
                            if (seg(new URL(r.url).pathname) !== seg(location.pathname))
                                throw new Error('hard: different portal');

                            if (push) history.pushState({}, '', r.url);
                            document.title = doc.title;

                            var t = doc.getElementById('page-title'),
                                ct = document.getElementById('page-title');
                            if (t && ct) ct.textContent = t.textContent;
                            var n = doc.getElementById('sidebar-nav'),
                                cn = document.getElementById('sidebar-nav');
                            if (n && cn) cn.innerHTML = n.innerHTML;

                            content.innerHTML = next.innerHTML; // also removes the spinner
                            runScripts(content);

                            if (samePath) main.scrollTop = savedTop; // stay where you were
                            else main.scrollTo(0, 0); // new page -> start at top

                            var ov = document.getElementById('sidebar-overlay');
                            if (ov && window.innerWidth < 768) ov.click(); // close mobile drawer
                            document.dispatchEvent(new CustomEvent('page:loaded', {
                                detail: {
                                    url: r.url
                                }
                            }));
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
