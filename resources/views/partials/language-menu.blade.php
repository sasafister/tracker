{{--
    The language picker in the top right corner. A <details> element, so it
    opens and closes without any script; the small script only closes it on
    a click elsewhere.
--}}
@php
    $names = [
        'hr' => 'Hrvatski',
        'en' => 'English',
        'de' => 'Deutsch',
        'sl' => 'Slovenščina',
    ];
    $current = app()->getLocale();
@endphp

<details class="language-menu relative" data-language-menu>
    <summary
        class="flex cursor-pointer list-none items-center gap-1.5 rounded-lg border border-zinc-200 bg-white px-2.5 py-1.5 text-sm font-medium text-zinc-700 transition hover:border-zinc-300 [&::-webkit-details-marker]:hidden"
        aria-label="{{ __('app.language') }}"
    >
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" class="size-4 text-zinc-500" aria-hidden="true">
            <circle cx="12" cy="12" r="9" />
            <path d="M3 12h18M12 3c2.5 2.7 3.8 5.7 3.8 9s-1.3 6.3-3.8 9c-2.5-2.7-3.8-5.7-3.8-9S9.5 5.7 12 3Z" />
        </svg>
        <span>{{ strtoupper($current) }}</span>
        <svg viewBox="0 0 20 20" fill="currentColor" class="size-3.5 text-zinc-400" aria-hidden="true">
            <path d="M5.2 7.7a.75.75 0 0 1 1.06.02L10 11.6l3.74-3.88a.75.75 0 1 1 1.08 1.04l-4.28 4.44a.75.75 0 0 1-1.08 0L5.18 8.76a.75.75 0 0 1 .02-1.06Z" />
        </svg>
    </summary>

    <ul class="absolute right-0 z-30 mt-2 w-44 overflow-hidden rounded-xl border border-zinc-200 bg-white py-1 text-sm shadow-lg">
        @foreach ($names as $code => $name)
            <li>
                <a
                    href="{{ route('locale', $code) }}"
                    hreflang="{{ $code }}"
                    lang="{{ $code }}"
                    @class([
                        'flex items-center justify-between px-3 py-2 transition hover:bg-zinc-50',
                        'font-semibold text-zinc-900' => $current === $code,
                        'text-zinc-600' => $current !== $code,
                    ])
                    @if ($current === $code) aria-current="true" @endif
                >
                    {{ $name }}
                    <span class="text-xs uppercase text-zinc-400">{{ $code }}</span>
                </a>
            </li>
        @endforeach
    </ul>
</details>

@once
    <script>
        document.addEventListener('click', (event) => {
            document.querySelectorAll('[data-language-menu][open]').forEach((menu) => {
                if (! menu.contains(event.target)) {
                    menu.removeAttribute('open');
                }
            });
        });
    </script>
@endonce
