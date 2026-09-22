{{--
    A static picture of the week calendar, for the landing and auth pages.
    With $screen it draws a full working day at desktop size, to be scaled
    down inside the laptop on the landing page.
--}}
@php
    $screen = $screen ?? false;
    $names = __('app.preview.days');
    $label = fn (string $key) => __('app.preview.blocks.'.$key);

    $days = [
        ['date' => 21, 'name' => $names[0], 'total' => '7h 45m', 'today' => true, 'phone' => true],
        ['date' => 22, 'name' => $names[1], 'total' => '6h 30m', 'today' => false, 'phone' => true],
        ['date' => 23, 'name' => $names[2], 'total' => '8h 00m', 'today' => false, 'phone' => true],
        ['date' => 24, 'name' => $names[3], 'total' => '5h 15m', 'today' => false, 'phone' => false],
        ['date' => 25, 'name' => $names[4], 'total' => '–', 'today' => false, 'phone' => false],
    ];

    // [day, start hour, length in hours, label, project, colour]
    $blocks = [
        [0, 0, 2.5, 'Branding screen', 'Acme', '#7c3aed'],
        [0, 3, 1, 'Mail', null, null],
        [0, 4.25, 1.25, 'Asset upload', 'Acme', '#7c3aed'],
        [1, 0.5, 2, $label('bookings'), 'Tennis', '#16a34a'],
        [1, 3, 2.5, 'Code review', 'Acme', '#7c3aed'],
        [2, 0, 1, $label('standup'), 'Tennis', '#16a34a'],
        [2, 1.5, 3.5, $label('api'), 'Tennis', '#16a34a'],
        [3, 1, 2, $label('quote'), 'Globex', '#ea580c'],
        [3, 3.5, 1.25, $label('invoice'), null, null],
    ];

    $hours = ['9:00', '10:00', '11:00', '12:00', '13:00', '14:00'];
    $hourHeight = 2.75;

    if ($screen) {
        $hours = [...$hours, '15:00', '16:00'];
        $hourHeight = 5.4;
        $days[4]['total'] = '4h 30m';

        $blocks = [
            ...$blocks,
            [1, 6, 1.5, 'Mail', null, null],
            [2, 5.5, 2, 'Code review', 'Acme', '#7c3aed'],
            [3, 5, 2.5, 'Asset upload', 'Acme', '#7c3aed'],
            [4, 0.5, 3, $label('api'), 'Tennis', '#16a34a'],
            [4, 4, 1.5, $label('quote'), 'Globex', '#ea580c'],
        ];
    }
@endphp

<div class="overflow-hidden rounded-2xl border border-slate-200 bg-white text-left shadow-2xl shadow-fuchsia-950/10 {{ $class ?? '' }}">
    <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3">
        <div class="h-8 min-w-0 flex-1 truncate rounded-lg border border-slate-200 px-3 py-1.5 text-xs text-slate-400">{{ __('app.preview.placeholder') }}</div>
        <div @class([
            'h-8 items-center gap-1.5 rounded-lg border border-slate-200 px-3 text-xs text-slate-600',
            'flex' => $screen,
            'hidden sm:flex' => ! $screen,
        ])>
            <span class="size-2 rounded-full bg-violet-600"></span>
            Acme
        </div>
        <div class="flex size-8 items-center justify-center rounded-lg border border-emerald-600 bg-emerald-50 text-sm font-semibold text-emerald-700">€</div>
        <span class="font-mono text-sm tabular-nums text-slate-700">1:42:07</span>
        <div class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white">{{ __('app.preview.stop') }}</div>
    </div>

    <div class="flex border-b border-slate-100 pl-12">
        @foreach ($days as $day)
            <div @class([
                'flex-1 items-center gap-1.5 px-2 py-2',
                'flex' => $day['phone'] || $screen,
                'hidden sm:flex' => ! $day['phone'] && ! $screen,
            ])>
                <span @class([
                    'flex size-7 items-center justify-center rounded-full text-sm',
                    'bg-fuchsia-50 text-fuchsia-800' => $day['today'],
                    'text-slate-700' => ! $day['today'],
                ])>{{ $day['date'] }}</span>

                <span class="flex flex-col leading-tight">
                    <span class="text-[11px] font-medium text-slate-700">{{ $day['name'] }}</span>
                    <span @class([
                        'text-[10px] font-medium',
                        'text-fuchsia-800' => $day['today'],
                        'text-slate-400' => ! $day['today'],
                    ])>{{ $day['total'] }}</span>
                </span>
            </div>
        @endforeach
    </div>

    <div class="relative flex" style="height: {{ count($hours) * $hourHeight }}rem">
        <div class="w-12 shrink-0">
            @foreach ($hours as $hour)
                <div class="pr-2 text-right text-[10px] text-slate-400" style="height: {{ $hourHeight }}rem">{{ $hour }}</div>
            @endforeach
        </div>

        @foreach ($days as $index => $day)
            <div @class([
                'relative flex-1 border-l border-slate-100',
                'hidden sm:block' => ! $day['phone'] && ! $screen,
            ])>
                @foreach ($hours as $hour)
                    <div class="border-t border-slate-100" style="height: {{ $hourHeight }}rem"></div>
                @endforeach

                @foreach ($blocks as [$blockDay, $start, $length, $label, $project, $color])
                    @if ($blockDay === $index)
                        <div
                            @class([
                                'absolute inset-x-1 overflow-hidden rounded-md px-1.5 py-1 text-[10px] leading-snug',
                                'bg-zinc-200/80 text-zinc-600 bg-[repeating-linear-gradient(135deg,rgb(0_0_0/0.05)_0_5px,transparent_5px_10px)]' => $project === null,
                            ])
                            style="top: {{ $start * $hourHeight }}rem; height: calc({{ $length * $hourHeight }}rem - 2px);
                                @if ($color) background: color-mix(in srgb, {{ $color }} 16%, white); color: {{ $color }}; @endif"
                        >
                            <div class="truncate font-medium">{{ $label }}</div>
                            @if ($project && $length >= 1)
                                <div class="truncate opacity-80">{{ $project }}</div>
                            @endif
                        </div>
                    @endif
                @endforeach

                @if ($day['today'])
                    <div class="absolute inset-x-0 border-t-[1.5px] border-fuchsia-800" style="top: {{ 5.5 * $hourHeight }}rem">
                        <span class="absolute -top-[7px] -left-[7px] size-3 rounded-full bg-fuchsia-800"></span>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
