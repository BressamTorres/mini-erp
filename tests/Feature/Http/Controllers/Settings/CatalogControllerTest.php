<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_cannot_access_catalog_settings(): void
    {
        $this->get(route('settings.catalog.show'))->assertRedirect(route('login'));
    }

    public function test_page_displays_only_the_authenticated_business_catalog(): void
    {
        config()->set('services.catalog.public_url', 'https://catalogo.meuminierp.tech/');
        config()->set('services.catalog.qr_code_url', 'https://quickchart.test/qr');
        $user = User::factory()->create();
        $business = $user->business;
        $business->forceFill([
            'trade_name' => 'Fetech <script>alert("xss")</script>',
            'catalog_slug' => 'fetech',
            'catalog_api_token' => 'token-que-nao-pode-aparecer',
        ])->save();
        Business::factory()->create([
            'trade_name' => 'Outra Empresa',
            'catalog_slug' => 'outra-empresa',
        ]);
        $expectedCatalogUrl = 'https://catalogo.meuminierp.tech/fetech';
        $expectedWhatsAppUrl = 'https://wa.me/?text=Conhe%C3%A7a%20nosso%20cat%C3%A1logo%20online%3A%20https%3A%2F%2Fcatalogo.meuminierp.tech%2Ffetech';
        $expectedQrCodeUrl = 'https://quickchart.test/qr?text=https%3A%2F%2Fcatalogo.meuminierp.tech%2Ffetech&size=260&margin=2&format=svg&ecLevel=M';

        $response = $this->actingAs($user)
            ->get(route('settings.catalog.show', ['business_id' => 999]));

        $response->assertOk()
            ->assertSee('Meu Catálogo Online')
            ->assertSee($expectedCatalogUrl)
            ->assertSee('Copiar link')
            ->assertSee('Abrir catálogo')
            ->assertDontSee('WhatsApp')
            ->assertDontSee('QR Code do catálogo')
            ->assertDontSee('Aponte a câmera do celular para acessar.')
            ->assertDontSee('outra-empresa')
            ->assertDontSee('token-que-nao-pode-aparecer')
            ->assertDontSee('<script>alert("xss")</script>', false)
            ->assertSee('&lt;script&gt;', false)
            ->assertViewHas('catalogUrl', $expectedCatalogUrl)
            ->assertViewHas('whatsAppUrl', $expectedWhatsAppUrl)
            ->assertViewHas('qrCodeUrl', $expectedQrCodeUrl);
    }

    public function test_page_shows_an_appropriate_state_when_business_has_no_slug(): void
    {
        config()->set('services.catalog.public_url', 'https://catalogo.meuminierp.tech');
        $user = User::factory()->create();
        $user->business->forceFill(['catalog_slug' => ''])->save();

        $response = $this->actingAs($user)->get(route('settings.catalog.show'));

        $response->assertOk()
            ->assertSee('Catálogo indisponível')
            ->assertSee('Sua empresa ainda não possui um endereço de catálogo.')
            ->assertDontSee('https://catalogo.meuminierp.tech/');
    }

    public function test_page_shows_an_appropriate_state_when_public_domain_is_not_configured(): void
    {
        config()->set('services.catalog.public_url');
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('settings.catalog.show'));

        $response->assertOk()
            ->assertSee('Catálogo indisponível')
            ->assertSee('O domínio público do catálogo ainda não está configurado.');
    }

    public function test_page_does_not_generate_a_link_for_an_invalid_slug(): void
    {
        config()->set('services.catalog.public_url', 'https://catalogo.meuminierp.tech');
        $user = User::factory()->create();
        $user->business->forceFill(['catalog_slug' => '../outra-empresa'])->save();

        $response = $this->actingAs($user)->get(route('settings.catalog.show'));

        $response->assertOk()
            ->assertSee('Catálogo indisponível')
            ->assertDontSee('https://catalogo.meuminierp.tech/../outra-empresa');
    }
}
