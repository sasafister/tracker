<?php

namespace App\Services;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use SplFileObject;
use Throwable;

/**
 * Reads the CSV that Toggl Track exports from Reports → Detailed and turns
 * each row into a time entry for one user. Projects are matched by name and
 * created when missing. A row that is already there — same start and end —
 * is skipped, so the same file can be imported twice. (Descriptions are
 * encrypted, so they cannot be part of that check.)
 */
class TogglCsvImporter
{
    /**
     * Toggl has renamed its columns over the years; each field accepts any of
     * these headers, compared in lower case.
     */
    private const COLUMNS = [
        'description' => ['description'],
        'project' => ['project'],
        'billable' => ['billable'],
        'start_date' => ['start date'],
        'start_time' => ['start time'],
        'end_date' => ['end date', 'stop date'],
        'end_time' => ['end time', 'stop time'],
    ];

    private const REQUIRED = ['start_date', 'start_time', 'end_date', 'end_time'];

    private const PALETTE = [
        '#2563eb',
        '#7c3aed',
        '#c026d3',
        '#dc2626',
        '#ea580c',
        '#ca8a04',
        '#16a34a',
        '#0891b2',
    ];

    /**
     * @return array{imported: int, skipped: int, projects_created: int, errors: list<string>}
     */
    public function import(User $user, string $path, string $timezone): array
    {
        $file = new SplFileObject($path);
        $file->setFlags(SplFileObject::READ_CSV | SplFileObject::SKIP_EMPTY | SplFileObject::READ_AHEAD);

        $columns = $this->mapHeader($file->current());

        $result = [
            'imported' => 0,
            'skipped' => 0,
            'projects_created' => 0,
            'errors' => [],
        ];

        $projects = $user->projects()->pluck('id', 'name');

        DB::transaction(function () use ($file, $columns, $user, $timezone, $projects, &$result) {
            $file->next();

            while (! $file->eof() && $file->valid()) {
                $line = $file->key() + 1;
                $row = $file->current();
                $file->next();

                if ($row === [null] || $row === false) {
                    continue;
                }

                try {
                    $values = $this->read($row, $columns);
                    $startedAt = $this->toUtc($values['start_date'], $values['start_time'], $timezone);
                    $endedAt = $this->toUtc($values['end_date'], $values['end_time'], $timezone);
                } catch (Throwable $error) {
                    $result['errors'][] = "Red {$line}: {$error->getMessage()}";

                    continue;
                }

                if ($endedAt->lessThanOrEqualTo($startedAt)) {
                    $result['skipped']++;

                    continue;
                }

                $description = $values['description'] !== '' ? $values['description'] : null;

                $exists = $user->timeEntries()
                    ->where('started_at', $startedAt)
                    ->where('ended_at', $endedAt)
                    ->exists();

                if ($exists) {
                    $result['skipped']++;

                    continue;
                }

                $projectId = null;

                if ($values['project'] !== '') {
                    if (! $projects->has($values['project'])) {
                        $project = $user->projects()->create([
                            'name' => $values['project'],
                            'color' => self::PALETTE[$projects->count() % count(self::PALETTE)],
                        ]);

                        $projects->put($project->name, $project->id);
                        $result['projects_created']++;
                    }

                    $projectId = $projects->get($values['project']);
                }

                $user->timeEntries()->create([
                    'description' => $description,
                    'project_id' => $projectId,
                    'billable' => $this->isBillable($values['billable']),
                    'started_at' => $startedAt,
                    'ended_at' => $endedAt,
                ]);

                $result['imported']++;
            }
        });

        return $result;
    }

    /**
     * @return array<string, int>
     */
    private function mapHeader(mixed $header): array
    {
        if (! is_array($header)) {
            throw new RuntimeException('Datoteka je prazna.');
        }

        // Excel and Toggl both like to start the file with a byte-order mark.
        $normalized = array_map(
            fn ($name) => strtolower(trim(preg_replace('/^\x{FEFF}/u', '', (string) $name))),
            $header,
        );

        $columns = [];

        foreach (self::COLUMNS as $field => $aliases) {
            foreach ($aliases as $alias) {
                $index = array_search($alias, $normalized, true);

                if ($index !== false) {
                    $columns[$field] = $index;

                    break;
                }
            }
        }

        $missing = array_diff(self::REQUIRED, array_keys($columns));

        if ($missing !== []) {
            throw new RuntimeException(
                'Ovo ne izgleda kao Toggl CSV (Reports → Detailed → Export CSV).',
            );
        }

        return $columns;
    }

    /**
     * @return array<string, string>
     */
    private function read(array $row, array $columns): array
    {
        $values = [];

        foreach (array_keys(self::COLUMNS) as $field) {
            $index = $columns[$field] ?? null;

            $values[$field] = $index === null ? '' : trim((string) ($row[$index] ?? ''));
        }

        return $values;
    }

    private function toUtc(string $date, string $time, string $timezone): CarbonImmutable
    {
        if ($date === '' || $time === '') {
            throw new RuntimeException('nedostaje datum ili vrijeme.');
        }

        return CarbonImmutable::parse("{$date} {$time}", $timezone)->utc();
    }

    private function isBillable(string $value): bool
    {
        return in_array(strtolower($value), ['yes', 'true', '1', 'da'], true);
    }
}
