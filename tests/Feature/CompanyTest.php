<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_details_are_saved_without_spaces(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->putJson('/api/company', [
                'company_name' => 'Moja tvrtka d.o.o.',
                'company_address' => "Ilica 1\n10000 Zagreb",
                'company_tax_id' => '123 4567 8903',
                'company_iban' => 'hr12 1001 0051 8630 0016 0',
            ])
            ->assertOk()
            ->assertJsonPath('company_tax_id', '12345678903')
            ->assertJsonPath('company_iban', 'HR1210010051863000160');

        $this->getJson('/api/settings')
            ->assertJsonPath('company.company_name', 'Moja tvrtka d.o.o.');
    }

    public function test_an_oib_with_a_wrong_check_digit_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->putJson('/api/company', ['company_tax_id' => '12345678901'])
            ->assertJsonValidationErrors('company_tax_id');
    }

    public function test_every_field_can_be_cleared(): void
    {
        $user = User::factory()->create([
            'company_name' => 'Stara tvrtka',
            'company_tax_id' => '12345678903',
        ]);

        $this->actingAs($user)
            ->putJson('/api/company', [
                'company_name' => null,
                'company_address' => null,
                'company_tax_id' => '',
                'company_iban' => null,
            ])
            ->assertOk()
            ->assertJsonPath('company_name', null)
            ->assertJsonPath('company_tax_id', null);
    }
}
