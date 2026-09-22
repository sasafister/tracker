{{-- Guests pick a language here; signed-in users do it in their settings. --}}
<nav class="flex items-center gap-1 text-xs font-medium {{ $class ?? '' }}" aria-label="{{ __('app.language') }}">
    @foreach (\App\Support\Locale::codes() as $code)
        <a
            href="{{ route('locale', $code) }}"
            hreflang="{{ $code }}"
            @class([
                'rounded px-1.5 py-1 uppercase transition',
                'bg-slate-900 text-white' => app()->getLocale() === $code,
                'text-slate-500 hover:text-slate-900' => app()->getLocale() !== $code,
            ])
            @if (app()->getLocale() === $code) aria-current="true" @endif
        >
            {{ $code }}
        </a>
    @endforeach
</nav>
