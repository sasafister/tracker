<?php

namespace Tests\Feature;

use App\Http\Controllers\EntryExportController;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class EntryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_month_downloads_as_a_pdf_with_only_the_users_entries(): void
    {
        $user = User::factory()->create([
            'name' => 'Saša Fišter',
            'hourly_rate' => 40,
        ]);
        $other = User::factory()->create();
        $project = $user->projects()->create(['name' => 'Furniloy']);

        $user->timeEntries()->create([
            'description' => 'Branding',
            'project_id' => $project->id,
            'started_at' => '2026-09-01 06:00:00',
            'ended_at' => '2026-09-01 14:00:00',
        ]);

        $other->timeEntries()->create([
            'description' => 'Tuđi unos',
            'started_at' => '2026-09-02 06:00:00',
            'ended_at' => '2026-09-02 14:00:00',
        ]);

        $response = $this->actingAs($user)->get('/api/export/pdf?'.http_build_query([
            'from' => '2026-08-31T22:00:00.000Z',
            'to' => '2026-09-30T22:00:00.000Z',
            'timezone' => 'Europe/Zagreb',
        ]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
        $disposition = $response->headers->get('content-disposition');
        $this->assertStringStartsWith('inline', $disposition);
        $this->assertStringContainsString('timer-sasa-fister-2026-09.pdf', $disposition);
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_the_report_totals_billable_time_by_rate(): void
    {
        $this->withoutVite();

        $user = User::factory()->create([
            'hourly_rate' => 40,
            'company_name' => 'Moja tvrtka d.o.o.',
            'company_address' => "Ilica 1\n10000 Zagreb",
            'company_tax_id' => '12345678903',
            'company_iban' => 'HR1210010051863000160',
        ]);

        $user->timeEntries()->create([
            'description' => 'Naplativo',
            'started_at' => '2026-09-01 06:00:00',
            'ended_at' => '2026-09-01 08:30:00',
        ]);

        $user->timeEntries()->create([
            'description' => 'Sastanak',
            'billable' => false,
            'started_at' => '2026-09-01 09:00:00',
            'ended_at' => '2026-09-01 10:00:00',
        ]);

        // Render the report's HTML the way the controller hands it to dompdf.
        $this->actingAs($user);

        $controller = new EntryExportController;
        $request = Request::create('/api/export/pdf', 'GET', [
            'from' => '2026-08-31T22:00:00Z',
            'to' => '2026-09-30T22:00:00Z',
            'timezone' => 'Europe/Zagreb',
        ]);
        $request->setUserResolver(fn () => $user);

        Pdf::shouldReceive('loadView')
            ->once()
            ->withArgs(function (string $view, array $data) {
                $this->assertSame('exports.entries-pdf', $view);
                $this->assertSame('Rujan 2026', $data['period']);
                $this->assertSame(12600, $data['summary']['seconds']);
                $this->assertSame(9000, $data['summary']['billable_seconds']);
                $this->assertEqualsWithDelta(100.0, $data['summary']['amount'], 0.001);

                $html = view($view, $data)->render();
                // Formatted like the app: a no-break space, then the sign.
                $this->assertStringContainsString("100,00\u{00A0}€", $html);
                $this->assertStringContainsString('nenaplativo', $html);
                $this->assertStringContainsString('Moja tvrtka d.o.o.', $html);
                $this->assertStringContainsString('Ilica 1<br />', $html);
                $this->assertStringContainsString('OIB: 12345678903', $html);
                $this->assertStringContainsString('IBAN: HR12 1001 0051 8630 0016 0', $html);

                return true;
            })
            ->andReturnSelf();

        Pdf::shouldReceive('setPaper')->andReturnSelf();

        Pdf::shouldReceive('setOption')->andReturnSelf();
        Pdf::shouldReceive('stream')->andReturn(response('pdf'));

        $controller->pdf($request);
    }
}
