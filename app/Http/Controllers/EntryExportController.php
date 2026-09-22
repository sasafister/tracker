<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\TimeEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\Response;

/**
 * A PDF report of the signed-in user's entries for a period: a summary, the
 * split per project, and every entry grouped by day. With a client it covers
 * only that client's projects and puts the client in the header.
 */
class EntryExportController extends Controller
{
    public function pdf(Request $request): Response
    {
        $data = $request->validate([
            'from' => ['required', 'date'],
            'to' => ['required', 'date', 'after:from'],
            'timezone' => ['required', 'timezone:all'],
            'client_id' => [
                'nullable',
                'integer',
                Rule::exists('clients', 'id')->where('user_id', $request->user()->id),
            ],
        ]);

        $client = isset($data['client_id']) ? Client::find($data['client_id']) : null;

        $user = $request->user();
        $timezone = $data['timezone'];
        $from = CarbonImmutable::parse($data['from'])->setTimezone($timezone);
        $to = CarbonImmutable::parse($data['to'])->setTimezone($timezone);
        $now = CarbonImmutable::now();

        $entries = $user->timeEntries()
            ->with('project.client')
            ->when(
                $client !== null,
                fn ($query) => $query->whereHas(
                    'project',
                    fn ($project) => $project->where('client_id', $client->id),
                ),
            )
            ->where('started_at', '>=', $from->utc())
            ->where('started_at', '<', $to->utc())
            ->orderBy('started_at')
            ->get()
            ->map(fn (TimeEntry $entry) => $this->row($entry, $timezone, $now));

        $billable = $entries->where('billable', true);

        $summary = [
            'seconds' => $entries->sum('seconds'),
            'billable_seconds' => $billable->sum('seconds'),
            'non_billable_seconds' => $entries->where('billable', false)->sum('seconds'),
            'amount' => $entries->sum('amount'),
            'entries' => $entries->count(),
            'days' => $entries->pluck('day_key')->unique()->count(),
        ];

        $pdf = Pdf::loadView('exports.entries-pdf', [
            'user' => $user,
            'client' => $client,
            // A freshly created user has not read the column default back yet.
            'currency' => $user->currency ?: 'EUR',
            'period' => $this->periodLabel($from, $to),
            'generatedAt' => $now->setTimezone($timezone),
            'summary' => $summary,
            'projects' => $this->byProject($entries),
            'days' => $this->byDay($entries),
        ])
            ->setPaper('a4')
            // Embed only the glyphs used rather than the whole DejaVu font.
            ->setOption('isFontSubsettingEnabled', true);

        $filename = sprintf(
            'timer-%s%s-%s.pdf',
            Str::slug($user->name) ?: 'unosi',
            $client ? '-'.Str::slug($client->name) : '',
            $from->format('Y-m'),
        );

        // Inline, so it opens in the browser's PDF viewer. Chrome blocks file
        // downloads over plain http, but not viewing, and the viewer saves it.
        return $pdf->stream($filename);
    }

    /**
     * Everything the report shows about one entry, in the reader's timezone.
     * A running entry counts up to now.
     */
    private function row(TimeEntry $entry, string $timezone, CarbonImmutable $now): array
    {
        $startedAt = CarbonImmutable::parse($entry->started_at)->setTimezone($timezone);
        $endedAt = $entry->ended_at === null
            ? null
            : CarbonImmutable::parse($entry->ended_at)->setTimezone($timezone);

        $seconds = (int) $startedAt->diffInSeconds($endedAt ?? $now, absolute: true);
        $rate = (float) $entry->hourly_rate;

        return [
            'day_key' => $startedAt->format('Y-m-d'),
            'day' => $startedAt,
            'description' => $entry->description,
            'project' => $entry->project?->name,
            'client' => $entry->project?->client?->name,
            'project_id' => $entry->project_id,
            'start' => $startedAt->format('H:i'),
            'end' => $endedAt?->format('H:i'),
            'seconds' => $seconds,
            'billable' => $entry->billable,
            'rate' => $rate,
            'amount' => $entry->billable ? $seconds / 3600 * $rate : 0.0,
        ];
    }

    private function byProject(Collection $entries): Collection
    {
        return $entries
            ->groupBy(fn (array $row) => $row['project'] ?? '')
            ->map(function (Collection $rows, string $name) {
                $billable = $rows->where('billable', true);

                return [
                    'name' => $name === '' ? __('app.report.no_project') : $name,
                    'client' => $rows->first()['client'],
                    'seconds' => $rows->sum('seconds'),
                    'billable_seconds' => $billable->sum('seconds'),
                    'rates' => $billable->pluck('rate')->unique()->sort()->values(),
                    'amount' => $rows->sum('amount'),
                ];
            })
            ->sortByDesc('seconds')
            ->values();
    }

    private function byDay(Collection $entries): Collection
    {
        return $entries
            ->groupBy('day_key')
            ->map(fn (Collection $rows) => [
                'date' => $rows->first()['day'],
                'rows' => $rows,
                'seconds' => $rows->sum('seconds'),
                'amount' => $rows->sum('amount'),
            ])
            ->values();
    }

    /**
     * "Rujan 2026" for a whole month, otherwise the two dates.
     */
    private function periodLabel(CarbonImmutable $from, CarbonImmutable $to): string
    {
        $isWholeMonth = $from->day === 1
            && $from->isStartOfDay()
            && $to->equalTo($from->addMonthNoOverflow());

        if ($isWholeMonth) {
            return Str::ucfirst($from->locale(app()->getLocale())->translatedFormat('F Y'));
        }

        $last = $to->subSecond();

        $format = __('app.report.date_format');

        return $from->format($format).' – '.$last->format($format);
    }
}
