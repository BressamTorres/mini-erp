<?php

namespace Tests\Feature\View\Layouts;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_displays_active_business_trade_name_and_initial(): void
    {
        $business = Business::factory()->create([
            'trade_name' => 'Fetech Soluções Empresariais com Nome Extenso',
            'legal_name' => 'Fetech Soluções Empresariais Ltda.',
        ]);
        $user = User::factory()->for($business)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Fetech Soluções Empresariais com Nome Extenso')
            ->assertSee('>F</span>', false)
            ->assertSee('max-w-48 truncate', false)
            ->assertDontSee('Administrador');
    }

    public function test_topbar_falls_back_to_legal_name_when_trade_name_is_empty(): void
    {
        $business = Business::factory()->create([
            'trade_name' => 'Nome temporário',
            'legal_name' => 'Razão Social da Empresa Ltda.',
        ]);
        $business->forceFill(['trade_name' => ''])->saveQuietly();
        $user = User::factory()->for($business)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Razão Social da Empresa Ltda.')
            ->assertSee('>R</span>', false)
            ->assertDontSee('Nome temporário');
    }

    public function test_topbar_uses_updated_business_name_after_settings_are_saved(): void
    {
        $business = Business::factory()->create([
            'trade_name' => 'Empresa Antiga',
        ]);
        $originalSlug = $business->catalog_slug;
        $user = User::factory()->for($business)->create();

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->put(route('settings.business.update'), $this->validBusinessPayload());

        $response->assertOk()
            ->assertSee('Empresa Atualizada')
            ->assertSee('>E</span>', false)
            ->assertDontSee('Empresa Antiga');
        $this->assertSame($originalSlug, $business->fresh()->catalog_slug);
    }

    public function test_topbar_does_not_display_another_business_name(): void
    {
        $activeBusiness = Business::factory()->create(['trade_name' => 'Empresa Ativa']);
        Business::factory()->create(['trade_name' => 'Empresa de Outro Tenant']);
        $user = User::factory()->for($activeBusiness)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Empresa Ativa')
            ->assertDontSee('Empresa de Outro Tenant');
    }

    public function test_topbar_escapes_business_name(): void
    {
        $business = Business::factory()->create([
            'trade_name' => 'Fetech <script>alert("menu")</script>',
        ]);
        $user = User::factory()->for($business)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert("menu")</script>', false);
    }

    /** @return array<string, string> */
    private function validBusinessPayload(): array
    {
        return [
            'trade_name' => 'Empresa Atualizada',
            'legal_name' => 'Empresa Atualizada Ltda.',
            'document' => '529.982.247-25',
            'phone' => '(44) 99999-9999',
            'email' => 'contato@empresa.test',
        ];
    }
}
