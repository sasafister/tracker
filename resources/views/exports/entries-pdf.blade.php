@php
    // Same formatting as the app on screen: "4.650,00 €", "4.650,00 USD".
    $money = fn (float $amount) => \App\Support\Currency::format($amount, $currency);

    // 8:04 — hours and minutes, as on the timesheet.
    $clock = function (int $seconds) {
        $minutes = intdiv($seconds + 30, 60);

        return intdiv($minutes, 60).':'.str_pad((string) ($minutes % 60), 2, '0', STR_PAD_LEFT);
    };

    // 8,07 h — decimal hours, what the amount is calculated from.
    $decimal = fn (int $seconds) => number_format($seconds / 3600, 2, ',', '.').' h';

    $weekdays = __('app.report.weekdays');

    $mark = base64_encode(
        '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg">'
        .'<rect width="32" height="32" rx="8" fill="#09090b"/>'
        .'<rect x="13.5" y="4.5" width="5" height="2.6" rx="1.3" fill="#fff"/>'
        .'<circle cx="16" cy="18" r="9" fill="none" stroke="#fff" stroke-width="2.4"/>'
        .'<path d="M16 18V11.8A6.2 6.2 0 0 1 22.2 18Z" fill="#fff"/>'
        .'</svg>'
    );
@endphp
<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <title>{{ __('app.report.title') }} · {{ $period }}</title>

    <style>
        @page {
            margin: 18mm 16mm 20mm;
        }

        * {
            box-sizing: border-box;
        }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 8pt;
            color: #18181b;
            line-height: 1.3;
        }

        h1 {
            margin: 0;
            font-size: 16pt;
            letter-spacing: -0.02em;
        }

        h2 {
            margin: 16px 0 5px;
            font-size: 9pt;
        }

        .muted {
            color: #71717a;
        }

        .right {
            text-align: right;
        }

        .nowrap {
            white-space: nowrap;
        }

        .header td {
            vertical-align: top;
        }

        .brand {
            font-size: 11pt;
            font-weight: bold;
        }

        .brand img {
            width: 22px;
            height: 22px;
            vertical-align: middle;
            margin-right: 6px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .summary td {
            width: 25%;
            padding: 7px 10px;
            border: 1px solid #e4e4e7;
        }

        .summary .label {
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.06em;
            color: #71717a;
        }

        .summary .value {
            margin-top: 2px;
            font-size: 11.5pt;
            font-weight: bold;
        }

        .summary .total {
            background: #18181b;
            color: #ffffff;
            border-color: #18181b;
        }

        .summary .total .label {
            color: #a1a1aa;
        }

        .list th {
            padding: 4px 5px;
            border-bottom: 1.5px solid #18181b;
            font-size: 7pt;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            text-align: left;
            color: #52525b;
        }

        .list th.right {
            text-align: right;
        }

        .list td {
            padding: 2.5px 5px;
            border-bottom: 0.5px solid #ececef;
            vertical-align: top;
        }

        .entries td {
            font-size: 7.5pt;
        }

        /* A darker rule where a new day starts; the date shows only there. */
        .entries .first td {
            border-top: 0.75px solid #a1a1aa;
        }

        .entries .date {
            width: 62px;
            font-weight: bold;
        }

        .company {
            font-size: 8pt;
            line-height: 1.4;
        }

        .company .name {
            font-size: 10.5pt;
            font-weight: bold;
        }

        .list .grand td {
            padding-top: 5px;
            border-top: 1.5px solid #18181b;
            border-bottom: none;
            font-weight: bold;
            font-size: 9.5pt;
        }

        .non-billable {
            color: #a1a1aa;
        }

        footer {
            position: fixed;
            bottom: -12mm;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #a1a1aa;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>
<body>
    <footer>
        <table>
            <tr>
                <td>{{ $user->name }} · {{ $period }}</td>
                <td class="right">{{ __('app.report.page') }} <span class="page-number"></span></td>
            </tr>
        </table>
    </footer>

    <table class="header">
        <tr>
            <td style="width: 55%;">
                <div class="brand">
                    <img src="data:image/svg+xml;base64,{{ $mark }}" alt="">
                    {{ config('app.name') }}
                </div>

                <h1 style="margin-top: 10px;">{{ __('app.report.title') }}</h1>
                <div style="margin-top: 1px; font-size: 10.5pt;">{{ $period }}</div>

                @if ($client)
                    <div class="company" style="margin-top: 12px;">
                        <div class="muted" style="font-size: 7pt; text-transform: uppercase; letter-spacing: 0.06em;">{{ __('app.report.client') }}</div>
                        <div class="name">{{ $client->name }}</div>

                        @if ($client->address)
                            <div>{!! nl2br(e($client->address)) !!}</div>
                        @endif

                        @if ($client->tax_id)
                            <div>{{ \App\Support\TaxId::label($client->tax_id) }}: {{ $client->tax_id }}</div>
                        @endif
                    </div>
                @endif
            </td>

            <td class="right company">
                @if ($user->company_name)
                    <div class="name">{{ $user->company_name }}</div>
                @endif

                @if ($user->company_address)
                    <div>{!! nl2br(e($user->company_address)) !!}</div>
                @endif

                @if ($user->company_tax_id)
                    <div>{{ \App\Support\TaxId::label($user->company_tax_id) }}: {{ $user->company_tax_id }}</div>
                @endif

                @if ($user->company_iban)
                    <div>IBAN: {{ trim(chunk_split($user->company_iban, 4, ' ')) }}</div>
                @endif

                <div @class(['muted', 'name' => ! $user->company_name]) style="margin-top: 6px;">
                    {{ $user->name }} · {{ $user->email }}
                </div>

                <div class="muted">
                    {{ __('app.report.generated', [
                        'date' => $generatedAt->format(__('app.report.date_format')),
                        'time' => $generatedAt->format('H:i'),
                    ]) }}
                </div>
            </td>
        </tr>
    </table>

    <table class="summary" style="margin-top: 16px;">
        <tr>
            <td>
                <div class="label">{{ __('app.report.total_hours') }}</div>
                <div class="value">{{ $clock($summary['seconds']) }}</div>
                <div class="muted">{{ $decimal($summary['seconds']) }}</div>
            </td>

            <td>
                <div class="label">{{ __('app.report.billable') }}</div>
                <div class="value">{{ $clock($summary['billable_seconds']) }}</div>
                <div class="muted">{{ $decimal($summary['billable_seconds']) }}</div>
            </td>

            <td>
                <div class="label">{{ __('app.report.non_billable') }}</div>
                <div class="value">{{ $clock($summary['non_billable_seconds']) }}</div>
                <div class="muted">
                    {{ trans_choice('app.report.days', $summary['days']) }},
                    {{ trans_choice('app.report.entries_count', $summary['entries']) }}
                </div>
            </td>

            <td class="total">
                <div class="label">{{ __('app.report.amount_due') }}</div>
                <div class="value">{{ $money($summary['amount']) }}</div>
            </td>
        </tr>
    </table>

    @if ($projects->isNotEmpty())
        <h2>{{ __('app.report.by_project') }}</h2>

        <table class="list">
            <thead>
                <tr>
                    <th>{{ __('app.report.project') }}</th>
                    <th class="right">{{ __('app.report.total') }}</th>
                    <th class="right">{{ __('app.report.billable') }}</th>
                    <th class="right">{{ __('app.report.rate') }}</th>
                    <th class="right">{{ __('app.report.amount') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($projects as $project)
                    <tr>
                        <td>
                            {{ $project['name'] }}
                            @if (! $client && $project['client'])
                                <span class="muted">· {{ $project['client'] }}</span>
                            @endif
                        </td>
                        <td class="right nowrap">{{ $clock($project['seconds']) }}</td>
                        <td class="right nowrap">
                            {{ $clock($project['billable_seconds']) }}
                            <span class="muted">({{ $decimal($project['billable_seconds']) }})</span>
                        </td>
                        <td class="right nowrap">
                            @if ($project['rates']->isEmpty())
                                <span class="muted">–</span>
                            @else
                                {{ $project['rates']->map(fn ($rate) => $money($rate).'/h')->join(', ') }}
                            @endif
                        </td>
                        <td class="right nowrap" style="font-weight: bold;">{{ $money($project['amount']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <h2>{{ __('app.report.entries') }}</h2>

    @if ($days->isEmpty())
        <p class="muted">{{ __('app.report.empty') }}</p>
    @else
        <table class="list entries">
            <thead>
                <tr>
                    <th>{{ __('app.report.date') }}</th>
                    <th>{{ __('app.report.description') }}</th>
                    <th>{{ __('app.report.project') }}</th>
                    <th class="nowrap">{{ __('app.report.from_to') }}</th>
                    <th class="right">{{ __('app.report.hours') }}</th>
                    <th class="right">{{ __('app.report.rate') }}</th>
                    <th class="right">{{ __('app.report.amount') }}</th>
                </tr>
            </thead>

            <tbody>
                @foreach ($days as $day)
                    @foreach ($day['rows'] as $row)
                        <tr @class([
                            'first' => $loop->first,
                            'non-billable' => ! $row['billable'],
                        ])>
                            <td class="date nowrap">
                                @if ($loop->first)
                                    {{ $weekdays[$day['date']->dayOfWeek] }} {{ $day['date']->format(__('app.report.day_format')) }}
                                @endif
                            </td>
                            <td>
                                {{ $row['description'] ?? __('app.report.no_description') }}
                                @unless ($row['billable'])
                                    <span class="muted">· {{ __('app.report.non_billable_note') }}</span>
                                @endunless
                            </td>
                            {{-- The client is in "Po projektima"; here it would wrap every row. --}}
                            <td class="nowrap">{{ $row['project'] ?? '–' }}</td>
                            <td class="nowrap">{{ $row['start'] }}–{{ $row['end'] ?? __('app.report.in_progress') }}</td>
                            <td class="right nowrap">{{ $clock($row['seconds']) }}</td>
                            <td class="right nowrap">{{ $row['billable'] ? $money($row['rate']) : '–' }}</td>
                            <td class="right nowrap">{{ $money($row['amount']) }}</td>
                        </tr>
                    @endforeach
                @endforeach

                <tr class="grand">
                    <td colspan="4">{{ __('app.report.total') }}</td>
                    <td class="right nowrap">{{ $clock($summary['seconds']) }}</td>
                    <td></td>
                    <td class="right nowrap">{{ $money($summary['amount']) }}</td>
                </tr>
            </tbody>
        </table>
    @endif
</body>
</html>
