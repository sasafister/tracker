<?php

namespace App\Services;

use App\Models\JiraConnection;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Talks to Jira Cloud's REST API v3 with a user's API token.
 *
 * A classic token works against the site itself; a scoped token only
 * through Atlassian's gateway at api.atlassian.com/ex/jira/{cloudId}.
 * verify() finds out which, and the connection remembers it.
 */
class JiraClient
{
    private const TIMEOUT = 8;

    public function __construct(
        private readonly JiraConnection $connection,
    ) {}

    /**
     * Checks the credentials and records where they work and whose they are.
     *
     * /myself needs read:jira-user; a token with only read:jira-work is
     * still good for picking tickets, so a refusal there (403) is retried
     * with a ticket search before giving up.
     *
     * @throws RuntimeException when neither address accepts the token
     */
    public function verify(): void
    {
        $answers = [];

        foreach ($this->candidateBaseUrls() as $baseUrl) {
            $jira = $this->request($baseUrl);
            $response = $jira->get('/rest/api/3/myself');
            $answers[$baseUrl] = $response->status();

            if ($response->successful()) {
                $this->connection->base_url = $baseUrl;
                $this->connection->account_name = $response->json('displayName');

                return;
            }

            if ($response->status() === 403) {
                $search = $jira->get('/rest/api/3/issue/picker', ['query' => '']);
                $answers[$baseUrl.' (picker)'] = $search->status();

                if ($search->successful()) {
                    $this->connection->base_url = $baseUrl;
                    $this->connection->account_name = null;

                    return;
                }
            }
        }

        // What Jira said, never the token, so a failed connection can be looked into.
        Log::warning('Jira refused a connection', [
            'site' => $this->connection->site,
            'email' => $this->connection->email,
            'answers' => $answers,
        ]);

        $refusedForScope = in_array(403, $answers, true) && ! in_array(200, $answers, true);

        throw new RuntimeException($refusedForScope
            ? __('app.jira.missing_scope')
            : __('app.jira.invalid_credentials', ['email' => $this->connection->email]));
    }

    /**
     * Issues for a picker: the latest open ones when there is no query,
     * otherwise Jira's own issue picker, narrowed to the project if set.
     *
     * @return list<array{key: string, summary: string, url: string}>
     */
    public function searchIssues(string $query, ?string $projectKey, bool $onlyMine): array
    {
        $filters = [];

        if ($projectKey !== null) {
            $filters[] = 'project = "'.$projectKey.'"';
        }

        if ($onlyMine) {
            $filters[] = 'assignee = currentUser()';
        }

        $query = trim($query);

        $issues = $query === ''
            ? $this->latestOpen($filters)
            : $this->pick($query, $filters, $projectKey);

        return array_map(fn (array $issue) => [
            'key' => $issue['key'],
            'summary' => $issue['summary'],
            'url' => $this->connection->issueUrl($issue['key']),
        ], array_slice($issues, 0, 20));
    }

    private function latestOpen(array $filters): array
    {
        $jql = implode(' AND ', [...$filters, 'statusCategory != Done'])
            .' ORDER BY updated DESC';

        $response = $this->call(fn (PendingRequest $jira) => $jira->post('/rest/api/3/search/jql', [
            'jql' => $jql,
            'fields' => ['summary'],
            'maxResults' => 20,
        ]));

        return array_map(fn (array $issue) => [
            'key' => $issue['key'],
            'summary' => $issue['fields']['summary'] ?? '',
        ], $response->json('issues') ?? []);
    }

    /**
     * The picker's "current search" follows the JQL; its "history" section
     * does not, so issues from other projects are dropped here.
     */
    private function pick(string $query, array $filters, ?string $projectKey): array
    {
        $parameters = [
            'query' => $query,
            'showSubTasks' => 'true',
        ];

        if ($filters !== []) {
            $parameters['currentJQL'] = implode(' AND ', $filters);
        }

        $response = $this->call(fn (PendingRequest $jira) => $jira->get('/rest/api/3/issue/picker', $parameters));

        $issues = [];

        foreach ($response->json('sections') ?? [] as $section) {
            foreach ($section['issues'] ?? [] as $issue) {
                $belongs = $projectKey === null || str_starts_with($issue['key'], $projectKey.'-');

                if ($belongs && ! isset($issues[$issue['key']])) {
                    $issues[$issue['key']] = [
                        'key' => $issue['key'],
                        'summary' => $issue['summaryText'] ?? '',
                    ];
                }
            }
        }

        return array_values($issues);
    }

    private function call(callable $send): Response
    {
        if ($this->connection->base_url === null) {
            $this->verify();
            $this->connection->save();
        }

        $response = $send($this->request($this->connection->base_url));

        if ($response->status() === 401 || $response->status() === 403) {
            throw new RuntimeException(__('app.jira.token_rejected'));
        }

        if ($response->failed()) {
            throw new RuntimeException(__('app.jira.unavailable', ['status' => $response->status()]));
        }

        return $response;
    }

    private function request(string $baseUrl): PendingRequest
    {
        return Http::baseUrl($baseUrl)
            ->withBasicAuth($this->connection->email, $this->connection->token)
            ->acceptJson()
            ->timeout(self::TIMEOUT);
    }

    /**
     * The site first, for classic tokens; then the gateway, for scoped
     * ones, which needs the site's cloud id.
     *
     * @return list<string>
     */
    private function candidateBaseUrls(): array
    {
        $urls = ['https://'.$this->connection->site];

        $cloudId = Http::timeout(self::TIMEOUT)
            ->get('https://'.$this->connection->site.'/_edge/tenant_info')
            ->json('cloudId');

        if (is_string($cloudId) && $cloudId !== '') {
            $urls[] = "https://api.atlassian.com/ex/jira/{$cloudId}";
        }

        return $urls;
    }
}
