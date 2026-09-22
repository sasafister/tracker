<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\TaxId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TaxIdTest extends TestCase
{
    use RefreshDatabase;

    public static function accepted(): array
    {
        return [
            'OIB' => ['12345678903', '12345678903', 'OIB'],
            'OIB with spaces' => ['123 4567 8903', '12345678903', 'OIB'],
            'Croatian VAT ID' => ['HR12345678903', 'HR12345678903', 'VAT ID'],
            'German VAT ID' => ['DE355361438', 'DE355361438', 'VAT ID'],
            'German VAT ID, spaced, lower case' => ['de 355 361 438', 'DE355361438', 'VAT ID'],
            'Austrian VAT ID' => ['ATU12345678', 'ATU12345678', 'VAT ID'],
        ];
    }

    #[DataProvider('accepted')]
    public function test_oibs_and_vat_ids_are_accepted(string $input, string $stored, string $label): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/clients', [
                'name' => 'Klijent',
                'tax_id' => $input,
            ])
            ->assertCreated()
            ->assertJsonPath('tax_id', $stored);

        $this->assertSame($label, TaxId::label($stored));
    }

    public static function rejected(): array
    {
        return [
            'OIB with a wrong check digit' => ['12345678901'],
            'HR with a wrong check digit' => ['HR12345678901'],
            'HR with too few digits' => ['HR1234'],
            'no country code' => ['355361438'],
            'not a number at all' => ['nema'],
        ];
    }

    #[DataProvider('rejected')]
    public function test_malformed_numbers_are_rejected(string $input): void
    {
        $this->actingAs(User::factory()->create())
            ->postJson('/api/clients', [
                'name' => 'Klijent',
                'tax_id' => $input,
            ])
            ->assertJsonValidationErrors('tax_id');
    }

    public function test_the_own_company_takes_a_vat_id_too(): void
    {
        $this->actingAs(User::factory()->create())
            ->putJson('/api/company', ['company_tax_id' => 'DE355361438'])
            ->assertOk()
            ->assertJsonPath('company_tax_id', 'DE355361438');
    }
}
