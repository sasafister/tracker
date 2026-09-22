<a href="/" class="flex items-center gap-2.5 {{ $class ?? '' }}">
    @include('partials.logo-mark', ['class' => 'size-8'])

    <span class="text-lg font-semibold tracking-tight">{{ config('app.name') }}</span>
</a>
