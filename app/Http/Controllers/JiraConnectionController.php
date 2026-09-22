<?php

namespace App\Http\Controllers;

use App\Models\JiraConnection;
use App\Services\JiraClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * The signed-in user's Jira connections. Saving one checks the token
 * against Jira first; the token itself is never sent back.
 */
class JiraConnectionController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $connections = $request->user()
            ->jiraConnections()
            ->orderBy('name')
            ->get()
            ->map(fn (JiraConnection $connection) => $this->present($connection));

        return response()->json($connections);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validated($request);

        $connection = $request->user()->jiraConnections()->make($data);

        $this->verify($connection);
        $connection->save();

        return response()->json($this->present($connection), 201);
    }

    /**
     * An empty token keeps the saved one, so a rename does not need it again.
     */
    public function update(Request $request, JiraConnection $jiraConnection): JsonResponse
    {
        $this->ensureOwnedBy($request, $jiraConnection);

        $data = $this->validated($request, $jiraConnection);

        if (($data['token'] ?? null) === null) {
            unset($data['token']);
        }

        $jiraConnection->fill($data);

        if ($jiraConnection->isDirty(['site', 'email', 'token'])) {
            $jiraConnection->base_url = null;
            $this->verify($jiraConnection);
        }

        $jiraConnection->save();

        return response()->json($this->present($jiraConnection));
    }

    /**
     * Projects using it simply lose their link to Jira.
     */
    public function destroy(Request $request, JiraConnection $jiraConnection): JsonResponse
    {
        $this->ensureOwnedBy($request, $jiraConnection);

        $jiraConnection->delete();

        return response()->json(null, 204);
    }

    private function validated(Request $request, ?JiraConnection $connection = null): array
    {
        $request->merge([
            'site' => $this->siteHost($request->input('site')),
        ]);

        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('jira_connections', 'name')
                    ->where('user_id', $request->user()->id)
                    ->ignore($connection),
            ],
            'site' => ['required', 'string', 'regex:/^[a-z0-9][a-z0-9-]*\.atlassian\.net$/'],
            'email' => ['required', 'email', 'max:255'],
            'token' => [$connection === null ? 'required' : 'nullable', 'string', 'max:500'],
        ], [
            'site.regex' => __('app.jira.site_format'),
        ]);
    }

    /**
     * "https://shoring.atlassian.net/jira/…" and "shoring.atlassian.net"
     * both mean the host.
     */
    private function siteHost(mixed $site): ?string
    {
        if (! is_string($site) || trim($site) === '') {
            return null;
        }

        $site = strtolower(trim($site));
        $host = parse_url(str_contains($site, '://') ? $site : 'https://'.$site, PHP_URL_HOST);

        return is_string($host) ? $host : $site;
    }

    private function verify(JiraConnection $connection): void
    {
        try {
            (new JiraClient($connection))->verify();
        } catch (RuntimeException $error) {
            throw ValidationException::withMessages([
                'token' => $error->getMessage(),
            ]);
        }
    }

    private function ensureOwnedBy(Request $request, JiraConnection $connection): void
    {
        abort_unless($connection->user_id === $request->user()->id, 404);
    }

    private function present(JiraConnection $connection): array
    {
        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'site' => $connection->site,
            'email' => $connection->email,
            'account_name' => $connection->account_name,
        ];
    }
}
