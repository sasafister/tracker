<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Services\JiraClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;

/**
 * Ticket search for one of the user's projects, through the Jira
 * connection that project uses. The browser never talks to Jira itself.
 */
class JiraIssueController extends Controller
{
    public function index(Request $request, Project $project): JsonResponse
    {
        abort_unless($project->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
        ]);

        $connection = $project->jiraConnection;

        if ($connection === null) {
            return response()->json([
                'message' => __('app.jira.not_linked'),
            ], 422);
        }

        try {
            $issues = (new JiraClient($connection))->searchIssues(
                $data['q'] ?? '',
                $project->jira_project_key,
                $project->jira_only_mine,
            );
        } catch (RuntimeException $error) {
            return response()->json([
                'message' => $error->getMessage(),
            ], 502);
        }

        return response()->json($issues);
    }
}
