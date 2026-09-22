<?php

namespace App\Http\Controllers;

use App\Models\TimeEntry;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Every query here goes through the signed-in user's own entries; another
 * user's entry is answered with 404, as if it did not exist.
 */
class TimeEntryController extends Controller
{
    /**
     * The calendar asks for one week at a time; the list view asks for
     * everything. Both go through here.
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'from' => ['sometimes', 'date'],
            'to' => ['sometimes', 'date'],
        ]);

        // Parsed rather than passed through as-is: the incoming ISO strings
        // ('2026-09-21T00:00:00Z') would be compared as text against the
        // 'Y-m-d H:i:s' the database stores, and never match.
        $from = isset($data['from']) ? Carbon::parse($data['from']) : null;
        $to = isset($data['to']) ? Carbon::parse($data['to']) : null;

        $entries = $request->user()
            ->timeEntries()
            ->when(
                $from !== null,
                fn ($query) => $query->where('started_at', '>=', $from),
            )
            ->when(
                $to !== null,
                fn ($query) => $query->where('started_at', '<', $to),
            )
            ->orderByDesc('started_at')
            ->orderByDesc('id')
            ->limit(1000)
            ->get();

        return response()->json($entries);
    }

    /**
     * Two ways in: the timer bar sends no span and means "now", the calendar
     * sends an explicit span. Only the first one stops whatever is already
     * running — Toggl counts a single thing at a time, but a span drawn on
     * Tuesday should not kill today's timer.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'description' => ['nullable', 'string', 'max:255'],
            'project_id' => ['nullable', 'integer', $this->ownProject($request)],
            'jira_issue_key' => ['nullable', 'string', 'max:64', 'regex:/^[A-Z][A-Z0-9_]*-\d+$/'],
            'jira_issue_summary' => ['nullable', 'string', 'max:255'],
            'billable' => ['sometimes', 'boolean'],
            'started_at' => ['sometimes', 'date'],
            'ended_at' => ['required_with:started_at', 'date', 'after:started_at'],
        ]);

        $user = $request->user();
        $isManual = isset($data['started_at']);
        $now = now();

        if (! $isManual) {
            $user->timeEntries()
                ->running()
                ->update([
                    'ended_at' => $now,
                    'updated_at' => $now,
                ]);
        }

        $entry = $user->timeEntries()->create([
            'description' => $data['description'] ?? null,
            'project_id' => $data['project_id'] ?? null,
            'jira_issue_key' => $data['jira_issue_key'] ?? null,
            'jira_issue_summary' => isset($data['jira_issue_key']) ? ($data['jira_issue_summary'] ?? null) : null,
            'billable' => $data['billable'] ?? true,
            'started_at' => $isManual ? $data['started_at'] : $now,
            'ended_at' => $isManual ? $data['ended_at'] : null,
        ]);

        return response()->json($entry->fresh(), 201);
    }

    /**
     * Rename an entry, stop it, move it in the calendar, or change its
     * project or whether it is billable.
     */
    public function update(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $this->ensureOwnedBy($request, $timeEntry);

        $data = $request->validate([
            'description' => ['sometimes', 'nullable', 'string', 'max:255'],
            'project_id' => ['sometimes', 'nullable', 'integer', $this->ownProject($request)],
            'jira_issue_key' => ['sometimes', 'nullable', 'string', 'max:64', 'regex:/^[A-Z][A-Z0-9_]*-\d+$/'],
            'jira_issue_summary' => ['sometimes', 'nullable', 'string', 'max:255'],
            'billable' => ['sometimes', 'boolean'],
            'started_at' => ['sometimes', 'date'],
            'ended_at' => ['sometimes', 'nullable', 'date'],
            'stop' => ['sometimes', 'boolean'],
        ]);

        if (array_key_exists('description', $data)) {
            $timeEntry->description = $data['description'];
        }

        if (array_key_exists('project_id', $data)) {
            $timeEntry->project_id = $data['project_id'];
        }

        // The title goes with its ticket: set together, cleared together.
        if (array_key_exists('jira_issue_key', $data)) {
            $timeEntry->jira_issue_key = $data['jira_issue_key'];
            $timeEntry->jira_issue_summary = $data['jira_issue_key'] === null
                ? null
                : ($data['jira_issue_summary'] ?? $timeEntry->jira_issue_summary);
        }

        if (isset($data['billable'])) {
            $timeEntry->billable = $data['billable'];
        }

        if (isset($data['started_at'])) {
            $timeEntry->started_at = $data['started_at'];
        }

        if (array_key_exists('ended_at', $data)) {
            $timeEntry->ended_at = $data['ended_at'];
        }

        $shouldStop = ($data['stop'] ?? false) && $timeEntry->ended_at === null;

        if ($shouldStop) {
            $timeEntry->ended_at = now();
        }

        $endsBeforeItStarts = $timeEntry->ended_at !== null
            && $timeEntry->ended_at->lessThanOrEqualTo($timeEntry->started_at);

        if ($endsBeforeItStarts) {
            return response()->json([
                'message' => __('app.errors.entry_end'),
            ], 422);
        }

        $timeEntry->save();

        return response()->json($timeEntry);
    }

    public function destroy(Request $request, TimeEntry $timeEntry): JsonResponse
    {
        $this->ensureOwnedBy($request, $timeEntry);

        $timeEntry->delete();

        return response()->json(null, 204);
    }

    /**
     * Only the user's own projects can be put on their entries.
     */
    private function ownProject(Request $request): Exists
    {
        return Rule::exists('projects', 'id')->where('user_id', $request->user()->id);
    }

    private function ensureOwnedBy(Request $request, TimeEntry $timeEntry): void
    {
        abort_unless($timeEntry->user_id === $request->user()->id, 404);
    }
}
