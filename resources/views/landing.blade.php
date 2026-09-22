<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>{{ config('app.name') }} · {{ __('app.meta.title') }}</title>

    <meta name="description" content="{{ __('app.meta.description') }}">

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css'])
</head>
<body class="bg-white text-slate-900 antialiased">
    <header class="sticky top-0 z-20 border-b border-slate-100 bg-white/80 backdrop-blur">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6">
            @include('partials.logo')

            <nav class="flex items-center gap-2 text-sm font-medium">
                @include('partials.language-switcher', ['class' => 'mr-2 hidden sm:flex'])

                <a href="#features" class="hidden rounded-lg px-3 py-2 text-slate-600 hover:text-slate-900 md:block">{{ __('app.nav.features') }}</a>
                <a href="/login" class="rounded-lg px-3 py-2 text-slate-600 hover:text-slate-900">{{ __('app.nav.login') }}</a>
                <a href="/register" class="rounded-lg bg-slate-900 px-4 py-2 text-white transition hover:bg-slate-700">{{ __('app.nav.start') }}</a>
            </nav>
        </div>
    </header>

    <main>
        <section class="relative overflow-hidden">
            <div class="pointer-events-none absolute inset-x-0 -top-40 -z-10 flex justify-center">
                <div class="h-[36rem] w-[72rem] rounded-full bg-gradient-to-r from-fuchsia-200/60 via-violet-200/50 to-sky-200/40 blur-3xl"></div>
            </div>

            <div class="mx-auto max-w-6xl px-4 pt-20 pb-16 text-center sm:px-6 sm:pt-28">
                <span class="inline-flex items-center gap-2 rounded-full border border-fuchsia-200 bg-white/70 px-3 py-1 text-xs font-medium text-fuchsia-800">
                    <span class="size-1.5 rounded-full bg-fuchsia-600"></span>
                    {{ __('app.landing.badge') }}
                </span>

                <h1 class="mx-auto mt-6 max-w-3xl text-4xl font-semibold tracking-tight text-balance sm:text-6xl">
                    {{ __('app.landing.headline') }}
                    <span class="bg-gradient-to-r from-fuchsia-700 to-violet-600 bg-clip-text text-transparent">{{ __('app.landing.headline_accent') }}</span>
                </h1>

                <p class="mx-auto mt-6 max-w-xl text-lg text-pretty text-slate-600">
                    {{ __('app.landing.lead') }}
                </p>

                <div class="mt-10 flex flex-wrap items-center justify-center gap-3">
                    <a href="/register" class="rounded-xl bg-fuchsia-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-fuchsia-700/25 transition hover:bg-fuchsia-800">
                        {{ __('app.landing.cta_register') }}
                    </a>

                    <a href="/login" class="rounded-xl border border-slate-200 bg-white px-6 py-3 text-sm font-semibold text-slate-700 transition hover:border-slate-300">
                        {{ __('app.landing.cta_login') }}
                    </a>
                </div>
            </div>

            <div class="mx-auto max-w-5xl px-4 pb-24 sm:px-6">
                @include('partials.week-preview')
            </div>
        </section>

        <section id="features" class="border-t border-slate-100 bg-slate-50 py-24">
            <div class="mx-auto max-w-6xl px-4 sm:px-6">
                <h2 class="max-w-xl text-3xl font-semibold tracking-tight">{{ __('app.landing.features_title') }}</h2>

                @php
                    $features = [
                        [
                            'title' => __('app.landing.features.timer.title'),
                            'text' => __('app.landing.features.timer.text'),
                            'icon' => 'M12 7v5l3 3M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z',
                        ],
                        [
                            'title' => __('app.landing.features.calendar.title'),
                            'text' => __('app.landing.features.calendar.text'),
                            'icon' => 'M8 3v3m8-3v3M4 9h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1Z',
                        ],
                        [
                            'title' => __('app.landing.features.rates.title'),
                            'text' => __('app.landing.features.rates.text'),
                            'icon' => 'M12 3v18m4-14H10a3 3 0 0 0 0 6h4a3 3 0 0 1 0 6H7',
                        ],
                        [
                            'title' => __('app.landing.features.billable.title'),
                            'text' => __('app.landing.features.billable.text'),
                            'icon' => 'm5 13 4 4L19 7',
                        ],
                    ];
                @endphp

                <div class="mt-12 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($features as $feature)
                        <div class="rounded-2xl border border-slate-200 bg-white p-6">
                            <span class="flex size-10 items-center justify-center rounded-xl bg-fuchsia-50 text-fuchsia-700">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                                    <path d="{{ $feature['icon'] }}" />
                                </svg>
                            </span>

                            <h3 class="mt-4 font-semibold">{{ $feature['title'] }}</h3>
                            <p class="mt-2 text-sm text-slate-600">{{ $feature['text'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="py-24">
            <div class="mx-auto max-w-4xl px-4 sm:px-6">
                <div class="relative overflow-hidden rounded-3xl bg-slate-950 px-6 py-16 text-center sm:px-16">
                    <div class="pointer-events-none absolute -top-24 left-1/2 h-64 w-[40rem] -translate-x-1/2 rounded-full bg-fuchsia-600/30 blur-3xl"></div>

                    <h2 class="relative text-3xl font-semibold tracking-tight text-white">{{ __('app.landing.final_title') }}</h2>
                    <p class="relative mt-4 text-slate-300">{{ __('app.landing.final_text') }}</p>

                    <a href="/register" class="relative mt-8 inline-block rounded-xl bg-white px-6 py-3 text-sm font-semibold text-slate-900 transition hover:bg-fuchsia-50">
                        {{ __('app.nav.start') }}
                    </a>
                </div>
            </div>
        </section>
    </main>

    <footer class="border-t border-slate-100">
        <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-8 text-sm text-slate-500 sm:px-6">
            @include('partials.logo', ['class' => 'scale-90 origin-left'])
            <div class="flex items-center gap-4">
                @include('partials.language-switcher')
                <span>© {{ now()->year }}</span>
            </div>
        </div>
    </footer>
</body>
</html>
