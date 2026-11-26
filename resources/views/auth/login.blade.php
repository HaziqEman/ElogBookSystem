@extends('layouts.guest')

@section('body_class', 'p-0')

@section('title', 'Sign in')

{{-- Needs @stack('styles') inside <head> of layouts/guest.blade.php --}}
@push('styles')
    <link
        rel="preconnect"
        href="https://fonts.googleapis.com"
    >
    <link
        rel="preconnect"
        href="https://fonts.gstatic.com"
        crossorigin
    >
    <link
        href="https://fonts.googleapis.com/css2?family=Caveat:wght@600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap"
        rel="stylesheet"
    >
    <style>
        .login-root {
            font-family: 'Plus Jakarta Sans', ui-sans-serif, system-ui, sans-serif;
        }

        .font-hand {
            font-family: 'Caveat', 'Segoe Print', cursive;
        }

        @keyframes login-rise {
            from {
                opacity: 0;
                transform: translateY(18px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        @keyframes login-float {

            0%,
            100% {
                transform: translateY(0);
            }

            50% {
                transform: translateY(-7px);
            }
        }

        @keyframes login-drift {

            0%,
            100% {
                transform: translate(0, 0);
            }

            50% {
                transform: translate(14px, -10px);
            }
        }

        .login-rise {
            opacity: 0;
            animation: login-rise .7s cubic-bezier(.2, .8, .2, 1) forwards;
        }

        .login-float {
            animation: login-float 6s ease-in-out infinite;
        }

        .login-drift {
            animation: login-drift 14s ease-in-out infinite;
        }

        @media (prefers-reduced-motion: reduce) {
            .login-rise {
                opacity: 1;
                animation: none;
            }

            .login-float,
            .login-drift {
                animation: none;
            }
        }
    </style>
@endpush

@section('content')
    <div
        class="login-root grid min-h-screen w-full bg-white lg:h-dvh lg:grid-cols-[1.05fr_1fr] lg:grid-rows-[minmax(0,1fr)] lg:overflow-hidden">

        {{-- ============ Brand panel (desktop) ============ --}}
        <aside
            class="relative hidden min-h-0 overflow-hidden bg-gradient-to-br from-[#1e3a8a] via-[#3151d6] to-[#5b7cff] p-8 text-white lg:flex lg:flex-col lg:justify-between xl:p-10"
            aria-hidden="true"
        >
            {{-- Soft circles, echoing the dashboard welcome banner --}}
            <div
                class="login-drift absolute -right-24 top-20 h-72 w-72 rounded-full bg-white/10 xl:h-80 xl:w-80">
            </div>
            <div
                class="login-drift absolute -bottom-32 -left-20 h-80 w-80 rounded-full bg-indigo-300/15"
                style="animation-delay: -6s"
            ></div>

            <div class="relative">
                <img
                    src="{{ asset('images/logo/logo-horizontal-white-animated.svg') }}"
                    alt="Smart eLogBook"
                    class="h-10 w-auto xl:h-12"
                >
            </div>

            <div class="relative">
                <p class="font-hand text-5xl font-bold leading-[1.05] xl:text-6xl">
                    Better skills,<br>brighter future
                </p>
                <p
                    class="mt-3 max-w-xs text-sm leading-relaxed text-blue-100/90 [@media(max-height:640px)]:hidden">
                    Write your daily entries, clock in, and keep your supervisors in the loop, all in
                    one place.
                </p>

                {{-- Mini dashboard preview (decorative, hidden on short screens) --}}
                <div class="mt-6 w-72 space-y-2.5 xl:mt-8 [@media(max-height:760px)]:hidden">
                    <div
                        class="login-rise"
                        style="animation-delay: .25s"
                    >
                        <div
                            class="login-float flex items-center gap-3 rounded-xl bg-white p-3 text-slate-900 shadow-lg shadow-blue-950/30">
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-sm text-blue-600">
                                <i class="fa-solid fa-book"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-500">Logbook entries</p>
                                <p class="text-lg font-extrabold leading-none">50 <span
                                        class="text-xs font-medium text-slate-500"
                                    >written</span></p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="login-rise ml-8"
                        style="animation-delay: .4s"
                    >
                        <div
                            class="login-float flex items-center gap-3 rounded-xl bg-white p-3 text-slate-900 shadow-lg shadow-blue-950/30"
                            style="animation-delay: -2s"
                        >
                            <div
                                class="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-sm text-emerald-600">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <div>
                                <p class="text-[11px] text-slate-500">Attendance this month</p>
                                <p class="text-lg font-extrabold leading-none">2 <span
                                        class="text-xs font-medium text-slate-500"
                                    >days present</span></p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="login-rise ml-3"
                        style="animation-delay: .55s"
                    >
                        <div
                            class="login-float flex items-center justify-between gap-3 rounded-xl bg-white p-3 text-slate-900 shadow-lg shadow-blue-950/30"
                            style="animation-delay: -4s"
                        >
                            <div>
                                <p class="text-[11px] text-slate-500">Upcoming deadline</p>
                                <p class="text-sm font-bold">Week 7 logbook opens</p>
                            </div>
                            <span
                                class="rounded-full bg-blue-50 px-2.5 py-1 text-[11px] font-semibold text-blue-600"
                            >In 5 days</span>
                        </div>
                    </div>
                </div>
            </div>

            <p class="relative text-xs text-blue-100/70">Universiti Teknologi MARA</p>
        </aside>

        {{-- ============ Form panel ============ --}}
        <main
            class="flex min-h-0 items-center justify-center overflow-y-auto bg-slate-50 px-5 py-8 sm:px-10 lg:bg-white"
        >
            <div
                class="login-rise w-full max-w-sm"
                style="animation-delay: .1s"
            >

                {{-- Compact brand header (mobile / tablet) --}}
                <div
                    class="mb-6 overflow-hidden rounded-2xl bg-gradient-to-br from-[#1e3a8a] via-[#3151d6] to-[#5b7cff] p-4 text-white shadow-lg shadow-blue-900/20 lg:hidden">
                    <img
                        src="{{ asset('images/logo/logo-horizontal-white-animated.svg') }}"
                        alt="Smart eLogBook"
                        class="h-10 w-auto"
                    >
                    <p class="font-hand mt-2 text-2xl font-bold leading-none">Better skills, brighter
                        future</p>
                </div>

                <header>
                    <h1 class="text-2xl font-extrabold tracking-tight text-slate-900">Sign in to your
                        logbook</h1>
                    <p class="mt-1.5 text-sm text-slate-500">Students, lecturers and supervisors sign in
                        here.</p>
                </header>

                @if (session('status'))
                    <div
                        role="status"
                        class="mt-5 flex items-start gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-sm text-emerald-700"
                    >
                        <i
                            class="fa-solid fa-circle-check mt-0.5"
                            aria-hidden="true"
                        ></i>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                @if (session('error'))
                    <div
                        role="alert"
                        class="mt-5 flex items-start gap-2 rounded-lg border border-rose-200 bg-rose-50 px-3.5 py-2.5 text-sm text-rose-700"
                    >
                        <i
                            class="fa-solid fa-circle-exclamation mt-0.5"
                            aria-hidden="true"
                        ></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <form
                    id="login-form"
                    action="{{ route('login') }}"
                    method="POST"
                    class="mt-6 space-y-4"
                >
                    @csrf

                    {{-- Email --}}
                    <div>
                        <label
                            for="email"
                            class="mb-1 block text-[13px] font-semibold text-slate-700"
                        >Email address</label>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400"
                            >
                                <i
                                    class="fa-regular fa-envelope"
                                    aria-hidden="true"
                                ></i>
                            </span>
                            <input
                                id="email"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                placeholder="you@example.com"
                                autocomplete="username"
                                autofocus
                                required
                                @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                                class="{{ $errors->has('email') ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/15' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/15' }} w-full rounded-lg border bg-white py-2.5 pl-10 pr-4 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4"
                            >
                        </div>
                        @error('email')
                            <p
                                id="email-error"
                                class="mt-1 text-xs font-medium text-rose-600"
                            >{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div>
                        <div class="mb-1 flex items-center justify-between">
                            <label
                                for="password"
                                class="block text-[13px] font-semibold text-slate-700"
                            >Password</label>
                            @if (Route::has('password.request'))
                                <a
                                    href="{{ route('password.request') }}"
                                    class="text-xs font-semibold text-blue-600 hover:underline"
                                >Forgot password?</a>
                            @endif
                        </div>
                        <div class="relative">
                            <span
                                class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-sm text-slate-400"
                            >
                                <i
                                    class="fa-solid fa-lock"
                                    aria-hidden="true"
                                ></i>
                            </span>
                            <input
                                id="password"
                                type="password"
                                name="password"
                                placeholder="Enter your password"
                                autocomplete="current-password"
                                required
                                @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                                class="{{ $errors->has('password') ? 'border-rose-300 focus:border-rose-400 focus:ring-rose-500/15' : 'border-slate-200 focus:border-blue-500 focus:ring-blue-500/15' }} w-full rounded-lg border bg-white py-2.5 pl-10 pr-11 text-sm text-slate-900 shadow-sm transition placeholder:text-slate-400 focus:outline-none focus:ring-4"
                            >
                            <button
                                type="button"
                                id="toggle-password"
                                class="absolute inset-y-0 right-0 flex items-center rounded-r-lg px-3.5 text-sm text-slate-400 transition hover:text-blue-600 focus:outline-none focus-visible:text-blue-600"
                                aria-label="Show password"
                                aria-controls="password"
                                aria-pressed="false"
                            >
                                <i
                                    class="fa-regular fa-eye"
                                    id="toggle-password-icon"
                                    aria-hidden="true"
                                ></i>
                            </button>
                        </div>

                        <p
                            id="caps-hint"
                            class="mt-1 hidden items-center gap-1.5 text-xs font-medium text-amber-600"
                            role="status"
                        >
                            <i
                                class="fa-solid fa-triangle-exclamation"
                                aria-hidden="true"
                            ></i> Caps Lock is on
                        </p>
                        @error('password')
                            <p
                                id="password-error"
                                class="mt-1 text-xs font-medium text-rose-600"
                            >{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Remember me --}}
                    <label
                        for="remember"
                        class="flex w-fit cursor-pointer items-center gap-2 text-[13px] text-slate-600"
                    >
                        <input
                            id="remember"
                            type="checkbox"
                            name="remember"
                            value="1"
                            @checked(old('remember'))
                            class="h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-2 focus:ring-blue-500/30"
                        >
                        Keep me signed in
                    </label>

                    <button
                        id="login-submit"
                        type="submit"
                        class="flex w-full items-center justify-center gap-2 rounded-lg bg-blue-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-blue-600/25 transition hover:bg-blue-700 hover:shadow-blue-600/40 focus:outline-none focus-visible:ring-4 focus-visible:ring-blue-500/30 disabled:cursor-not-allowed disabled:opacity-70"
                    >
                        {{-- Wrapped in a span so Font Awesome's display rule can't override "hidden" --}}
                        <span
                            id="login-spinner"
                            class="hidden"
                            aria-hidden="true"
                        >
                            <i class="fa-solid fa-circle-notch fa-spin"></i>
                        </span>
                        <span id="login-label">Sign in</span>
                    </button>
                </form>

                @if (Route::has('register'))
                    <p class="mt-6 text-center text-[13px] text-slate-500">
                        New to the platform?
                        <a
                            href="{{ route('register') }}"
                            class="font-semibold text-blue-600 hover:underline"
                        >Create an account</a>
                    </p>
                @endif
            </div>
        </main>
    </div>

    <script>
        (() => {
            const form = document.getElementById('login-form');
            const password = document.getElementById('password');
            const toggle = document.getElementById('toggle-password');
            const toggleIcon = document.getElementById('toggle-password-icon');
            const capsHint = document.getElementById('caps-hint');
            const submit = document.getElementById('login-submit');
            const spinner = document.getElementById('login-spinner');
            const label = document.getElementById('login-label');
            if (!form || !password) return;

            // Show / hide password
            toggle?.addEventListener('click', () => {
                const show = password.type === 'password';
                password.type = show ? 'text' : 'password';
                toggle.setAttribute('aria-pressed', String(show));
                toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
                toggleIcon.classList.toggle('fa-eye', !show);
                toggleIcon.classList.toggle('fa-eye-slash', show);
            });

            // Caps Lock warning
            const checkCaps = (e) => {
                const on = e.getModifierState && e.getModifierState('CapsLock');
                capsHint.classList.toggle('hidden', !on);
                capsHint.classList.toggle('flex', on);
            };
            password.addEventListener('keydown', checkCaps);
            password.addEventListener('keyup', checkCaps);
            password.addEventListener('blur', () => {
                capsHint.classList.add('hidden');
                capsHint.classList.remove('flex');
            });

            // Prevent double submits
            form.addEventListener('submit', () => {
                submit.disabled = true;
                spinner.classList.remove('hidden');
                label.textContent = 'Signing in…';
            });
        })();
    </script>
@endsection
