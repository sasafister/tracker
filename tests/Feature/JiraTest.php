<?php

namespace Tests\Feature;

use App\Models\JiraConnection;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class JiraTest extends TestCase
{
    use RefreshDatabase;

    private const GATEWAY = 'https://api.atlassian.com/ex/jira/cloud-123';

    private function connectionFor(User $user, array $attributes = []): JiraConnection
    {
        return $user->jiraConnections()->create([
            'name' => 'Shoring',
            'site' => 'shoring.atlassian.net',
            'email' => 'me@example.com',
            'token' => 'secret-token',
            'base_url' => 'https://shoring.atlassian.net',
            ...$attributes,
        ]);
    }

    public function test_a_classic_token_is_checked_against_the_site_and_kept_secret(): void
    {
        Http::fake([
            'shoring.atlassian.net/_edge/tenant_info' => Http::response(['cloudId' => 'cloud-123']),
            'shoring.atlassian.net/rest/api/3/myself' => Http::response(['displayName' => 'Saša']),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/jira-connections', [
                'name' => 'Shoring',
                'site' => 'https://Shoring.atlassian.net/jira/your-work',
                'email' => 'me@example.com',
                'token' => 'secret-token',
            ])
            ->assertCreated()
            ->assertJsonPath('site', 'shoring.atlassian.net')
            ->assertJsonPath('account_name', 'Saša')
            ->assertJsonMissingPath('token');

        $raw = DB::table('jira_connections')->first();

        $this->assertSame('https://shoring.atlassian.net', $raw->base_url);
        $this->assertStringStartsWith('eyJ', $raw->token);
        $this->assertStringNotContainsString('secret-token', $raw->token);

        Http::assertSent(fn (Request $request) => $request->hasHeader(
            'Authorization',
            'Basic '.base64_encode('me@example.com:secret-token'),
        ));
    }

    public function test_a_scoped_token_falls_back_to_the_gateway(): void
    {
        Http::fake([
            'shoring.atlassian.net/_edge/tenant_info' => Http::response(['cloudId' => 'cloud-123']),
            'shoring.atlassian.net/rest/api/3/myself' => Http::response([], 401),
            self::GATEWAY.'/rest/api/3/myself' => Http::response(['displayName' => 'Saša']),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/jira-connections', [
                'name' => 'Shoring',
                'site' => 'shoring.atlassian.net',
                'email' => 'me@example.com',
                'token' => 'scoped-token',
            ])
            ->assertCreated();

        $this->assertSame(self::GATEWAY, JiraConnection::first()->base_url);
    }

    public function test_a_token_jira_refuses_is_not_saved(): void
    {
        Http::fake([
            'shoring.atlassian.net/_edge/tenant_info' => Http::response(['cloudId' => 'cloud-123']),
            '*/rest/api/3/myself' => Http::response([], 401),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/jira-connections', [
                'name' => 'Shoring',
                'site' => 'shoring.atlassian.net',
                'email' => 'me@example.com',
                'token' => 'wrong',
            ])
            ->assertJsonValidationErrors('token');

        $this->assertSame(0, JiraConnection::count());
    }

    public function test_only_jira_cloud_sites_are_accepted(): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/jira-connections', [
                'name' => 'Server',
                'site' => 'jira.example.com',
                'email' => 'me@example.com',
                'token' => 'x',
            ])
            ->assertJsonValidationErrors('site');
    }

    public function test_renaming_keeps_the_token_and_does_not_call_jira(): void
    {
        Http::fake();

        $user = User::factory()->create();
        $connection = $this->connectionFor($user);

        $this->actingAs($user)
            ->putJson("/api/jira-connections/{$connection->id}", [
                'name' => 'Shoring Cloud',
                'site' => 'shoring.atlassian.net',
                'email' => 'me@example.com',
                'token' => '',
            ])
            ->assertOk()
            ->assertJsonPath('name', 'Shoring Cloud');

        $this->assertSame('secret-token', $connection->fresh()->token);
        Http::assertNothingSent();
    }

    public function test_connections_belong_to_their_user(): void
    {
        $owner = User::factory()->create();
        $connection = $this->connectionFor($owner);
        $other = User::factory()->create();

        $this->actingAs($other)->getJson('/api/jira-connections')->assertExactJson([]);
        $this->deleteJson("/api/jira-connections/{$connection->id}")->assertNotFound();

        $this->postJson('/api/projects', [
            'name' => 'Tuđi Jira projekt',
            'jira_connection_id' => $connection->id,
        ])->assertJsonValidationErrors('jira_connection_id');
    }

    public function test_an_empty_search_lists_the_latest_open_issues_of_the_project(): void
    {
        Http::fake([
            'shoring.atlassian.net/rest/api/3/search/jql' => Http::response([
                'issues' => [
                    ['key' => 'FUR-792', 'fields' => ['summary' => 'Asset upload']],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $project = $user->projects()->create([
            'name' => 'Cockpit',
            'jira_connection_id' => $this->connectionFor($user)->id,
            'jira_project_key' => 'FUR',
            'jira_only_mine' => true,
        ]);

        $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/jira-issues")
            ->assertOk()
            ->assertExactJson([
                [
                    'key' => 'FUR-792',
                    'summary' => 'Asset upload',
                    'url' => 'https://shoring.atlassian.net/browse/FUR-792',
                ],
            ]);

        Http::assertSent(fn (Request $request) => str_contains($request['jql'], 'project = "FUR"')
            && str_contains($request['jql'], 'assignee = currentUser()')
            && str_contains($request['jql'], 'statusCategory != Done'));
    }

    public function test_typing_uses_the_issue_picker_and_keeps_the_project_only(): void
    {
        Http::fake([
            'shoring.atlassian.net/rest/api/3/issue/picker*' => Http::response([
                'sections' => [
                    ['id' => 'hs', 'issues' => [
                        ['key' => 'FUR-792', 'summaryText' => 'Asset upload'],
                        ['key' => 'OPS-1', 'summaryText' => 'Asset server'],
                    ]],
                    ['id' => 'cs', 'issues' => [
                        ['key' => 'FUR-792', 'summaryText' => 'Asset upload'],
                        ['key' => 'FUR-801', 'summaryText' => 'Asset cleanup'],
                    ]],
                ],
            ]),
        ]);

        $user = User::factory()->create();
        $project = $user->projects()->create([
            'name' => 'Cockpit',
            'jira_connection_id' => $this->connectionFor($user)->id,
            'jira_project_key' => 'FUR',
        ]);

        $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/jira-issues?q=asset")
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonPath('0.key', 'FUR-792')
            ->assertJsonPath('1.key', 'FUR-801');

        Http::assertSent(fn (Request $request) => $request['query'] === 'asset'
            && $request['currentJQL'] === 'project = "FUR"');
    }

    public function test_an_expired_token_is_reported(): void
    {
        Http::fake([
            'shoring.atlassian.net/rest/api/3/issue/picker*' => Http::response([], 401),
        ]);

        $user = User::factory()->create(['locale' => 'en']);
        $project = $user->projects()->create([
            'name' => 'Cockpit',
            'jira_connection_id' => $this->connectionFor($user)->id,
        ]);

        $this->actingAs($user)
            ->getJson("/api/projects/{$project->id}/jira-issues?q=x")
            ->assertStatus(502)
            ->assertJsonPath('message', fn (string $message) => str_contains($message, 'expired'));
    }

    public function test_an_entry_keeps_its_ticket(): void
    {
        $user = User::factory()->create();

        $entry = $this->actingAs($user)
            ->postJson('/api/entries', [
                'description' => 'FUR-792 Asset upload',
                'jira_issue_key' => 'FUR-792',
            ])
            ->assertCreated()
            ->assertJsonPath('jira_issue_key', 'FUR-792')
            ->json();

        $this->patchJson("/api/entries/{$entry['id']}", ['jira_issue_key' => 'not a key'])
            ->assertJsonValidationErrors('jira_issue_key');

        $this->patchJson("/api/entries/{$entry['id']}", ['jira_issue_key' => null])
            ->assertOk()
            ->assertJsonPath('jira_issue_key', null);
    }

    public function test_a_token_without_read_jira_user_still_connects_through_a_ticket_search(): void
    {
        Http::fake([
            'shoring.atlassian.net/_edge/tenant_info' => Http::response(['cloudId' => 'cloud-123']),
            'shoring.atlassian.net/rest/api/3/myself' => Http::response([], 401),
            self::GATEWAY.'/rest/api/3/myself' => Http::response([], 403),
            self::GATEWAY.'/rest/api/3/issue/picker*' => Http::response(['sections' => []]),
        ]);

        $this->actingAs(User::factory()->create())
            ->postJson('/api/jira-connections', [
                'name' => 'Shoring',
                'site' => 'shoring.atlassian.net',
                'email' => 'me@example.com',
                'token' => 'work-only-token',
            ])
            ->assertCreated()
            ->assertJsonPath('account_name', null);

        $this->assertSame(self::GATEWAY, JiraConnection::first()->base_url);
    }

    public function test_a_token_without_any_jira_rights_says_so(): void
    {
        Http::fake([
            'shoring.atlassian.net/_edge/tenant_info' => Http::response(['cloudId' => 'cloud-123']),
            'shoring.atlassian.net/rest/api/3/myself' => Http::response([], 401),
            self::GATEWAY.'/*' => Http::response([], 403),
        ]);

        $this->actingAs(User::factory()->create(['locale' => 'en']))
            ->postJson('/api/jira-connections', [
                'name' => 'Shoring',
                'site' => 'shoring.atlassian.net',
                'email' => 'me@example.com',
                'token' => 'no-scopes',
            ])
            ->assertJsonValidationErrors('token')
            ->assertJsonPath('errors.token.0', fn (string $message) => str_contains($message, 'read:jira-work'));
    }

    public function test_wrong_credentials_name_the_email_that_was_tried(): void
    {
        Http::fake([
            'shoring.atlassian.net/_edge/tenant_info' => Http::response(['cloudId' => 'cloud-123']),
            '*/rest/api/3/myself' => Http::response([], 401),
        ]);

        $this->actingAs(User::factory()->create(['locale' => 'en']))
            ->postJson('/api/jira-connections', [
                'name' => 'Shoring',
                'site' => 'shoring.atlassian.net',
                'email' => 'me@example.com',
                'token' => 'wrong',
            ])
            ->assertJsonPath('errors.token.0', fn (string $message) => str_contains($message, 'me@example.com'));
    }

    public function test_an_entry_keeps_the_ticket_title_encrypted_and_drops_it_with_the_ticket(): void
    {
        $user = User::factory()->create();

        $entry = $this->actingAs($user)
            ->postJson('/api/entries', [
                'description' => 'FUR-800 Connect admin branding',
                'jira_issue_key' => 'FUR-800',
                'jira_issue_summary' => 'Connect admin branding',
            ])
            ->assertJsonPath('jira_issue_summary', 'Connect admin branding')
            ->json();

        $raw = DB::table('time_entries')->where('id', $entry['id'])->value('jira_issue_summary');
        $this->assertStringStartsWith('eyJ', $raw);

        $this->patchJson("/api/entries/{$entry['id']}", ['description' => 'Renamed'])
            ->assertJsonPath('jira_issue_summary', 'Connect admin branding');

        $this->patchJson("/api/entries/{$entry['id']}", ['jira_issue_key' => null])
            ->assertJsonPath('jira_issue_key', null)
            ->assertJsonPath('jira_issue_summary', null);
    }
}
