<?php

namespace Tests\Feature\View\Layouts;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TopbarTest extends TestCase
{
    use RefreshDatabase;

    public function test_topbar_displays_active_business_name_initial_and_email(): void
    {
        $business = Business::factory()->create([
            'trade_name' => 'Fetech Soluções Empresariais com Nome Extenso',
            'legal_name' => 'Fetech Soluções Empresariais Ltda.',
            'email' => 'empresa@fetech.test',
        ]);
        $user = User::factory()->for($business)->create([
            'email' => 'usuario@fetech.test',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Fetech Soluções Empresariais com Nome Extenso')
            ->assertSee('empresa@fetech.test')
            ->assertSee('>F</span>', false)
            ->assertSee('max-w-48 truncate', false)
            ->assertDontSee('usuario@fetech.test')
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
            'email' => 'antigo@empresa.test',
        ]);
        $originalSlug = $business->catalog_slug;
        $user = User::factory()->for($business)->create([
            'email' => 'usuario@login.test',
        ]);

        $response = $this->actingAs($user)
            ->followingRedirects()
            ->put(route('settings.business.update'), $this->validBusinessPayload());

        $response->assertOk()
            ->assertSee('Empresa Atualizada')
            ->assertSee('contato@empresa.test')
            ->assertSee('>E</span>', false)
            ->assertDontSee('Empresa Antiga')
            ->assertDontSee('antigo@empresa.test')
            ->assertDontSee('usuario@login.test');
        $this->assertSame($originalSlug, $business->fresh()->catalog_slug);
        $this->assertSame('usuario@login.test', $user->fresh()->email);
    }

    public function test_topbar_does_not_display_another_business_name(): void
    {
        $activeBusiness = Business::factory()->create([
            'trade_name' => 'Empresa Ativa',
            'email' => 'ativa@empresa.test',
        ]);
        Business::factory()->create([
            'trade_name' => 'Empresa de Outro Tenant',
            'email' => 'outra@empresa.test',
        ]);
        $user = User::factory()->for($activeBusiness)->create();

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSee('Empresa Ativa')
            ->assertSee('ativa@empresa.test')
            ->assertDontSee('Empresa de Outro Tenant')
            ->assertDontSee('outra@empresa.test');
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
