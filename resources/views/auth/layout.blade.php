<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>@yield('title') · {{ config('app.name') }}</title>

    <link rel="icon" href="/favicon.svg" type="image/svg+xml">

    @vite(['resources/css/app.css'])
</head>
<body class="h-full bg-white text-slate-900 antialiased">
    <div class="flex min-h-full">
        <main class="flex flex-1 flex-col px-4 py-8 sm:px-10 lg:flex-none lg:basis-[34rem] lg:px-16">
            <div class="flex items-center justify-between gap-4">
                @include('partials.logo')
                @include('partials.language-switcher')
            </div>

            <div class="my-auto w-full max-w-sm self-center py-12 lg:self-start">
                <h1 class="text-2xl font-semibold tracking-tight">@yield('title')</h1>
                <p class="mt-2 text-sm text-slate-500">@yield('subtitle')</p>

                @yield('form')

                <p class="mt-8 text-sm text-slate-500">
                    @yield('footer')
                </p>
            </div>
        </main>

        <aside class="relative hidden flex-1 overflow-hidden bg-slate-950 lg:flex lg:flex-col lg:justify-center lg:px-16">
            <div class="pointer-events-none absolute -top-32 -right-32 size-[36rem] rounded-full bg-fuchsia-600/30 blur-3xl"></div>
            <div class="pointer-events-none absolute -bottom-40 -left-20 size-[30rem] rounded-full bg-violet-600/20 blur-3xl"></div>

            <div class="relative">
                <p class="max-w-md text-3xl font-semibold tracking-tight text-balance text-white">
                    {{ __('app.auth.aside_title') }}
                </p>

                <p class="mt-4 max-w-md text-slate-400">
                    {{ __('app.auth.aside_text') }}
                </p>

                <div class="mt-12 origin-top-left scale-[0.9] xl:scale-100">
                    @include('partials.week-preview', ['class' => 'max-w-2xl shadow-black/40'])
                </div>
            </div>
        </aside>
    </div>
</body>
</html>
