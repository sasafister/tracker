<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} · {{ __('app.meta.title') }}</title>

    <meta name="description" content="{{ __('app.meta.description') }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    {{-- Scroll reveals only hide content once we know the script will show it again. --}}
    <script>
        document.documentElement.classList.add('js');
    </script>

    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-zinc-900 antialiased">
    {{-- A thin, translucent bar, like a product page's local navigation. --}}
    <header class="sticky top-0 z-40 border-b border-black/5 bg-white/75 backdrop-blur-xl backdrop-saturate-150">
        <div class="mx-auto flex h-12 max-w-5xl items-center justify-between gap-4 px-4 sm:px-6">
            @include('partials.logo', ['class' => 'scale-90 origin-left'])

            <div class="flex items-center gap-1">
                <nav class="hidden items-center text-[13px] text-zinc-600 md:flex">
                    <a href="#features" class="px-3 py-1 transition hover:text-zinc-900">{{ __('app.nav.features') }}</a>
                    <a href="#without" class="px-3 py-1 transition hover:text-zinc-900">{{ __('app.nav.without') }}</a>
                    <a href="#how" class="px-3 py-1 transition hover:text-zinc-900">{{ __('app.nav.how') }}</a>
                    <a href="#signin" class="px-3 py-1 transition hover:text-zinc-900">{{ __('app.nav.login') }}</a>
                </nav>

                @include('partials.language-menu')
            </div>
        </div>
    </header>

    <main>
        {{-- Hero: one sentence, two ways in, and the product itself. --}}
        <section class="overflow-hidden pt-20 sm:pt-28">
            <div class="mx-auto max-w-5xl px-4 text-center sm:px-6">
                <p class="reveal text-lg font-semibold text-zinc-500">{{ config('app.name') }}</p>

                <h1 class="reveal mt-3 text-5xl font-semibold tracking-[-0.035em] text-balance sm:text-7xl lg:text-[5.5rem] lg:leading-[1.02]">
                    {{ __('app.landing.headline') }}
                </h1>

                <p class="reveal mx-auto mt-6 max-w-2xl text-lg text-pretty text-zinc-500 sm:text-xl">
                    {{ __('app.landing.subhead') }}
                </p>

                <div class="reveal mt-9 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
                    <a href="/register" class="rounded-full bg-zinc-900 px-6 py-3 text-[15px] font-medium text-white transition hover:bg-zinc-700">
                        {{ __('app.landing.cta_register') }}
                    </a>

                    <a href="#signin" class="group text-[15px] font-medium text-zinc-900">
                        {{ __('app.landing.cta_login') }}
                        <span class="inline-block transition group-hover:translate-x-0.5" aria-hidden="true">›</span>
                    </a>
                </div>
            </div>

            {{--
                The app, framed like a laptop. Every size is a share of the
                laptop's width, so the proportions hold on any screen: a 16:10
                display in a thin even bezel, on a wider base with a notch.
                The app is drawn at 1280 x 800 and scaled to fit the display.
            --}}
            <div class="reveal reveal-slow mx-auto mt-16 w-[88%] max-w-4xl sm:mt-20">
                <div class="rounded-t-[3.2%_5%] bg-zinc-900 p-[1.6%] pb-[2.2%] shadow-[0_50px_100px_-30px_rgba(0,0,0,0.5)] ring-1 ring-zinc-700/60">
                    <div class="relative aspect-[16/10] overflow-hidden rounded-t-[0.6%] bg-white" data-laptop-screen>
                        <div class="absolute top-0 left-0 h-[800px] w-[1280px] origin-top-left" data-laptop-content>
                            @include('partials.week-preview', [
                                'screen' => true,
                                'class' => 'h-full rounded-none! border-0! shadow-none!',
                            ])
                        </div>
                    </div>
                </div>

                <div class="relative -mx-[6%] h-0 rounded-b-[40%_100%] bg-gradient-to-b from-zinc-200 via-zinc-300 to-zinc-400 pb-[2.6%] shadow-[0_20px_30px_-18px_rgba(0,0,0,0.5)]">
                    <div class="absolute top-0 left-1/2 h-[45%] w-[15%] -translate-x-1/2 rounded-b-[40%_100%] bg-gradient-to-b from-zinc-400 to-zinc-300"></div>
                </div>
            </div>
        </section>

        {{-- Signing in, right under the fold. --}}
        <section id="signin" class="scroll-mt-12 bg-[#f5f5f7] py-24 sm:py-32">
            <div class="mx-auto max-w-sm px-4">
                <div class="reveal text-center">
                    <p class="text-sm font-semibold text-zinc-500">{{ __('app.landing.signin_eyebrow') }}</p>
                    <h2 class="mt-2 text-4xl font-semibold tracking-tight sm:text-5xl">{{ __('app.landing.signin_title') }}</h2>
                    <p class="mt-3 text-zinc-500">{{ __('app.landing.signin_text') }}</p>
                </div>

                @include('auth.status')

                <form method="POST" action="/login" class="reveal mt-10 flex flex-col gap-3">
                    @csrf

                    <label class="sr-only" for="signin-email">{{ __('app.auth.email') }}</label>
                    <input
                        id="signin-email"
                        name="email"
                        type="email"
                        value="{{ old('email') }}"
                        autocomplete="email"
                        placeholder="{{ __('app.auth.email') }}"
                        required
                        @class([
                            'h-14 rounded-2xl border bg-white px-5 text-[17px] outline-none transition placeholder:text-zinc-400 focus:ring-4',
                            'border-red-300 focus:border-red-400 focus:ring-red-100' => $errors->has('email'),
                            'border-zinc-300 focus:border-zinc-900 focus:ring-zinc-900/10' => ! $errors->has('email'),
                        ])
                    >

                    <label class="sr-only" for="signin-password">{{ __('app.auth.password') }}</label>
                    <input
                        id="signin-password"
                        name="password"
                        type="password"
                        autocomplete="current-password"
                        placeholder="{{ __('app.auth.password') }}"
                        required
                        class="h-14 rounded-2xl border border-zinc-300 bg-white px-5 text-[17px] outline-none transition placeholder:text-zinc-400 focus:border-zinc-900 focus:ring-4 focus:ring-zinc-900/10"
                    >

                    @error('email')
                        <p class="px-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror

                    <div class="flex items-center justify-between gap-2 px-1 pt-1 text-sm">
                        <label class="flex items-center gap-2 text-zinc-600">
                            <input name="remember" type="checkbox" value="1" class="size-4 rounded border-zinc-300 accent-zinc-900">
                            {{ __('app.auth.remember') }}
                        </label>

                        <a href="/forgot-password" class="text-zinc-600 transition hover:text-zinc-900">
                            {{ __('app.auth.forgot_link') }}
                        </a>
                    </div>

                    <button
                        type="submit"
                        class="mt-4 h-14 rounded-2xl bg-zinc-900 text-[17px] font-medium text-white transition hover:bg-zinc-700"
                    >
                        {{ __('app.auth.login_button') }}
                    </button>
                </form>

                <p class="reveal mt-8 text-center text-sm text-zinc-500">
                    {{ __('app.auth.no_account') }}
                    <a href="/register" class="font-medium text-zinc-900 hover:underline">{{ __('app.auth.register_link') }} ›</a>
                </p>
            </div>
        </section>

        {{-- The one thing the app does, as big as it gets. --}}
        <section class="bg-black py-28 text-center text-white sm:py-40">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <h2 class="reveal text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                    {{ __('app.landing.clock_line1') }}
                    <span class="block text-zinc-500">{{ __('app.landing.clock_line2') }}</span>
                </h2>

                <div class="reveal reveal-slow mt-16 flex items-center justify-center gap-4 sm:gap-6">
                    <span class="relative flex size-4 sm:size-5" aria-hidden="true">
                        <span class="absolute inset-0 animate-ping rounded-full bg-red-500/60"></span>
                        <span class="relative size-full rounded-full bg-red-500"></span>
                    </span>

                    <span
                        class="font-mono text-6xl font-medium tracking-tight tabular-nums sm:text-8xl lg:text-9xl"
                        data-landing-clock="6127"
                    >1:42:07</span>
                </div>

                <p class="reveal mx-auto mt-14 max-w-xl text-lg text-pretty text-zinc-400 sm:text-xl">
                    {{ __('app.landing.clock_text') }}
                </p>
            </div>
        </section>

        {{-- Features as tiles, each showing rather than telling. --}}
        <section id="features" class="scroll-mt-12 bg-[#f5f5f7] py-24 sm:py-32">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <div class="reveal text-center">
                    <h2 class="text-4xl font-semibold tracking-tight sm:text-6xl">{{ __('app.landing.bento_title') }}</h2>
                    <p class="mt-3 text-4xl font-semibold tracking-tight text-zinc-400 sm:text-6xl">{{ __('app.landing.bento_subtitle') }}</p>
                </div>

                @php
                    $tile = fn (string $key) => __('app.landing.tiles.'.$key);
                    $rate = \App\Support\Currency::format(50, 'EUR').'/h';
                    $amount = \App\Support\Currency::format(4650, 'EUR');
                @endphp

                <div class="mt-16 grid gap-4 sm:gap-5 lg:grid-cols-3">
                    {{-- Calendar: a small week of coloured blocks. --}}
                    <article class="reveal flex flex-col overflow-hidden rounded-[1.75rem] bg-white p-8 lg:col-span-2">
                        <p class="text-sm font-semibold text-zinc-500">{{ $tile('calendar')['eyebrow'] }}</p>
                        <h3 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $tile('calendar')['title'] }}</h3>
                        <p class="mt-2 max-w-md text-zinc-500">{{ $tile('calendar')['text'] }}</p>

                        @php
                            // [top %, height %, colour] per block, per day.
                            $week = [
                                [[0, 42, '#7c3aed'], [52, 16, null], [72, 22, '#7c3aed']],
                                [[8, 34, '#16a34a'], [50, 40, '#7c3aed']],
                                [[0, 16, '#16a34a'], [24, 58, '#16a34a']],
                                [[16, 34, '#ea580c'], [58, 20, null]],
                                [[4, 26, '#7c3aed']],
                            ];
                        @endphp

                        <div class="mt-8 grid h-44 grid-cols-5 gap-2 sm:h-52">
                            @foreach ($week as $blocks)
                                <div class="relative rounded-xl bg-zinc-50">
                                    @foreach ($blocks as [$top, $height, $color])
                                        <span
                                            @class([
                                                'absolute inset-x-1.5 rounded-lg',
                                                'bg-zinc-200 bg-[repeating-linear-gradient(135deg,rgb(0_0_0/0.06)_0_5px,transparent_5px_10px)]' => $color === null,
                                            ])
                                            style="top: {{ $top }}%; height: {{ $height }}%; @if ($color) background: color-mix(in srgb, {{ $color }} 22%, white); @endif"
                                        ></span>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </article>

                    {{-- Rates: the number itself. --}}
                    <article class="reveal flex flex-col justify-between rounded-[1.75rem] bg-white p-8">
                        <div>
                            <p class="text-sm font-semibold text-zinc-500">{{ $tile('rates')['eyebrow'] }}</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight">{{ $tile('rates')['title'] }}</h3>
                        </div>

                        <p class="my-10 text-5xl font-semibold tracking-tight tabular-nums">{{ $rate }}</p>

                        <p class="text-zinc-500">{{ $tile('rates')['text'] }}</p>
                    </article>

                    {{-- Billable: the toggle, on and off. --}}
                    <article class="reveal flex flex-col justify-between rounded-[1.75rem] bg-white p-8">
                        <div>
                            <p class="text-sm font-semibold text-zinc-500">{{ $tile('billable')['eyebrow'] }}</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight">{{ $tile('billable')['title'] }}</h3>
                        </div>

                        <div class="my-10 flex items-center gap-4" aria-hidden="true">
                            <span class="flex size-16 items-center justify-center rounded-2xl border-2 border-emerald-600 bg-emerald-50 text-2xl font-semibold text-emerald-700">€</span>
                            <span class="flex size-16 items-center justify-center rounded-2xl border-2 border-zinc-200 text-2xl font-semibold text-zinc-300">€</span>
                        </div>

                        <p class="text-zinc-500">{{ $tile('billable')['text'] }}</p>
                    </article>

                    {{-- Report: a sheet of paper with the total. --}}
                    <article class="reveal flex flex-col overflow-hidden rounded-[1.75rem] bg-white p-8 lg:col-span-2 lg:flex-row lg:items-center lg:gap-10">
                        <div class="lg:flex-1">
                            <p class="text-sm font-semibold text-zinc-500">{{ $tile('report')['eyebrow'] }}</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $tile('report')['title'] }}</h3>
                            <p class="mt-2 text-zinc-500">{{ $tile('report')['text'] }}</p>
                        </div>

                        <div class="mx-auto mt-10 w-full max-w-64 -rotate-2 rounded-xl border border-zinc-200 bg-white p-5 shadow-2xl shadow-zinc-900/10 lg:mt-0" aria-hidden="true">
                            <div class="h-2 w-16 rounded bg-zinc-900"></div>
                            <div class="mt-4 space-y-2">
                                @foreach ([90, 72, 84, 60, 78] as $width)
                                    <div class="flex items-center justify-between gap-3">
                                        <div class="h-1.5 rounded bg-zinc-200" style="width: {{ $width }}%"></div>
                                        <div class="h-1.5 w-6 rounded bg-zinc-200"></div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="mt-5 rounded-lg bg-zinc-900 px-3 py-2 text-right text-sm font-semibold text-white tabular-nums">{{ $amount }}</div>
                        </div>
                    </article>

                    {{-- Clients: a few of them, as chips. --}}
                    <article class="reveal flex flex-col justify-between rounded-[1.75rem] bg-white p-8">
                        <div>
                            <p class="text-sm font-semibold text-zinc-500">{{ $tile('clients')['eyebrow'] }}</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight">{{ $tile('clients')['title'] }}</h3>
                        </div>

                        <div class="my-8 flex flex-wrap gap-2" aria-hidden="true">
                            @foreach ([['Acme d.o.o.', '#7c3aed'], ['Tennis Club', '#16a34a'], ['Globex GmbH', '#ea580c']] as [$name, $color])
                                <span class="flex items-center gap-2 rounded-full bg-zinc-100 px-3 py-1.5 text-sm font-medium">
                                    <span class="size-2 rounded-full" style="background: {{ $color }}"></span>
                                    {{ $name }}
                                </span>
                            @endforeach
                        </div>

                        <p class="text-zinc-500">{{ $tile('clients')['text'] }}</p>
                    </article>

                    {{-- Languages: the codes, large. --}}
                    <article class="reveal flex flex-col justify-between rounded-[1.75rem] bg-white p-8">
                        <div>
                            <p class="text-sm font-semibold text-zinc-500">{{ $tile('languages')['eyebrow'] }}</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight">{{ $tile('languages')['title'] }}</h3>
                        </div>

                        <p class="my-8 flex flex-wrap gap-x-3 text-4xl font-semibold tracking-tight" aria-hidden="true">
                            @foreach (\App\Support\Locale::codes() as $code)
                                <span @class(['text-zinc-900' => $code === app()->getLocale(), 'text-zinc-300' => $code !== app()->getLocale()])>{{ strtoupper($code) }}</span>
                            @endforeach
                        </p>

                        <p class="text-zinc-500">{{ $tile('languages')['text'] }}</p>
                    </article>

                    {{-- Privacy: dark, with a lock. --}}
                    <article class="reveal flex flex-col justify-between rounded-[1.75rem] bg-zinc-900 p-8 text-white">
                        <div>
                            <p class="text-sm font-semibold text-zinc-400">{{ $tile('privacy')['eyebrow'] }}</p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight">{{ $tile('privacy')['title'] }}</h3>
                        </div>

                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" class="my-8 size-16 text-white" aria-hidden="true">
                            <path d="M7 11V8a5 5 0 0 1 10 0v3M6 11h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1v-8a1 1 0 0 1 1-1Zm6 4v2" />
                        </svg>

                        <p class="text-zinc-400">{{ $tile('privacy')['text'] }}</p>
                    </article>

                    {{-- Jira: the ticket picker, as it looks in the app. --}}
                    <article class="reveal flex flex-col gap-10 overflow-hidden rounded-[1.75rem] bg-white p-8 lg:col-span-3 lg:flex-row lg:items-center lg:gap-16 lg:p-12">
                        <div class="lg:flex-1">
                            <p class="flex items-center gap-2 text-sm font-semibold text-zinc-500">
                                <svg viewBox="0 0 32 32" class="size-5" aria-hidden="true">
                                    <path fill="#2684FF" d="M29.3 15.1 17.3 3.1l-1.2-1.1-9 9-4.1 4.1a1.2 1.2 0 0 0 0 1.7l8.2 8.2 4.9 4.9 9-9 .1-.1 4.1-4.1a1.2 1.2 0 0 0 0-1.6Zm-13.2 5-4.1-4.1 4.1-4.1 4.1 4.1-4.1 4.1Z" />
                                </svg>
                                {{ $tile('jira')['eyebrow'] }}
                            </p>
                            <h3 class="mt-2 text-2xl font-semibold tracking-tight sm:text-3xl">{{ $tile('jira')['title'] }}</h3>
                            <p class="mt-2 max-w-md text-zinc-500">{{ $tile('jira')['text'] }}</p>
                        </div>

                        <div class="w-full max-w-md self-center rounded-2xl border border-zinc-200 bg-white p-3 shadow-2xl shadow-zinc-900/10 lg:w-[26rem]" aria-hidden="true">
                            <div class="flex items-center justify-between rounded-lg border border-zinc-900 px-3 py-2 text-sm text-zinc-400">
                                {{ __('app.preview.ticket_search') }}
                                <svg viewBox="0 0 20 20" fill="currentColor" class="size-4 text-zinc-500">
                                    <path d="M5.2 7.7a.75.75 0 0 1 1.06.02L10 11.6l3.74-3.88a.75.75 0 1 1 1.08 1.04l-4.28 4.44a.75.75 0 0 1-1.08 0L5.18 8.76a.75.75 0 0 1 .02-1.06Z" />
                                </svg>
                            </div>

                            <ul class="mt-2 text-sm">
                                @foreach ([['ACME-128', 'Checkout: save the delivery address'], ['ACME-131', 'Logo upload with form validation'], ['ACME-137', 'Deploy the release to staging']] as [$key, $title])
                                    <li @class([
                                        'flex items-baseline gap-3 rounded-lg px-3 py-2',
                                        'bg-zinc-100' => $loop->index === 1,
                                    ])>
                                        <span class="shrink-0 font-mono text-xs font-semibold">{{ $key }}</span>
                                        <span class="truncate text-zinc-700">{{ $title }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </article>
                </div>
            </div>
        </section>

        {{-- What it leaves out, crossed off. --}}
        <section id="without" class="scroll-mt-12 py-28 text-center sm:py-40">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <h2 class="reveal text-5xl font-semibold tracking-[-0.03em] sm:text-7xl">{{ __('app.landing.without_title') }}</h2>
                <p class="reveal mt-5 text-lg text-zinc-500 sm:text-xl">{{ __('app.landing.without_text') }}</p>

                <ul class="mt-14 flex flex-col gap-3 sm:gap-4">
                    @foreach (__('app.landing.without') as $item)
                        <li class="reveal text-2xl font-semibold tracking-tight text-zinc-300 line-through decoration-zinc-300 decoration-2 sm:text-4xl">
                            {{ $item }}
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        <section id="how" class="scroll-mt-12 bg-[#f5f5f7] py-24 sm:py-32">
            <div class="mx-auto max-w-5xl px-4 sm:px-6">
                <h2 class="reveal text-center text-4xl font-semibold tracking-tight sm:text-6xl">{{ __('app.landing.steps_title') }}</h2>

                <ol class="mt-16 grid gap-10 md:grid-cols-3 md:gap-8">
                    @foreach (__('app.landing.steps') as $step)
                        <li class="reveal">
                            <span class="block text-7xl font-semibold tracking-tight text-zinc-300 tabular-nums">{{ $loop->iteration }}</span>
                            <h3 class="mt-4 text-2xl font-semibold tracking-tight">{{ $step['title'] }}</h3>
                            <p class="mt-2 text-zinc-500">{{ $step['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
            </div>
        </section>

        <section class="bg-black py-28 text-center text-white sm:py-40">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <h2 class="reveal text-5xl font-semibold tracking-[-0.03em] text-balance sm:text-7xl">{{ __('app.landing.final_title') }}</h2>
                <p class="reveal mt-5 text-lg text-zinc-400 sm:text-xl">{{ __('app.landing.final_text') }}</p>

                <div class="reveal mt-10 flex flex-wrap items-center justify-center gap-x-8 gap-y-4">
                    <a href="/register" class="rounded-full bg-white px-6 py-3 text-[15px] font-medium text-zinc-900 transition hover:bg-zinc-200">
                        {{ __('app.landing.cta_register') }}
                    </a>

                    <a href="#signin" class="group text-[15px] font-medium text-white">
                        {{ __('app.landing.cta_login') }}
                        <span class="inline-block transition group-hover:translate-x-0.5" aria-hidden="true">›</span>
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="bg-[#f5f5f7] text-xs text-zinc-500">
        <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-between gap-3 border-t border-zinc-200 px-4 py-6 sm:px-6">
            <span>{{ config('app.name') }} — {{ __('app.landing.tagline') }}</span>
            <span>© {{ now()->year }}</span>
        </div>
    </footer>

    <script>
        // Fade sections in as they scroll into view.
        const revealed = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    revealed.unobserve(entry.target);
                }
            });
        }, {
            rootMargin: '0px 0px -8% 0px',
        });

        document.querySelectorAll('.reveal').forEach((element) => revealed.observe(element));

        @if ($errors->any())
            // A failed sign-in comes back here; take the user to the form and its message.
            document.getElementById('signin').scrollIntoView();
            document.getElementById('signin-password').focus({ preventScroll: true });
        @endif

        // The app inside the laptop is drawn at 1280 px and scaled to the display.
        document.querySelectorAll('[data-laptop-screen]').forEach((screen) => {
            const content = screen.querySelector('[data-laptop-content]');

            new ResizeObserver(([entry]) => {
                content.style.transform = `scale(${entry.contentRect.width / 1280})`;
            }).observe(screen);
        });

        // The big clock keeps counting, like the real one.
        document.querySelectorAll('[data-landing-clock]').forEach((clock) => {
            let seconds = Number(clock.dataset.landingClock);
            const pad = (value) => String(value).padStart(2, '0');

            setInterval(() => {
                seconds += 1;

                const hours = Math.floor(seconds / 3600);
                const minutes = Math.floor((seconds % 3600) / 60);

                clock.textContent = `${hours}:${pad(minutes)}:${pad(seconds % 60)}`;
            }, 1000);
        });
    </script>
</body>
</html>
