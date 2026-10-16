<?php

namespace Tests\Feature\Http\Controllers\Settings;

use App\Models\Business;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BusinessControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_cannot_access_or_update_business_settings(): void
    {
        $this->get(route('settings.business.edit'))->assertRedirect(route('login'));
        $this->put(route('settings.business.update'), $this->validPayload())
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_sees_only_their_business_data(): void
    {
        $user = User::factory()->create();
        $business = $user->business;
        $business->update(['trade_name' => 'Empresa do Usuário']);
        Business::factory()->create(['trade_name' => 'Empresa de Outro Tenant']);

        $response = $this->actingAs($user)->get(route('settings.business.edit'));

        $response->assertOk()
            ->assertSee('Dados da Empresa')
            ->assertSee('Empresa do Usuário')
            ->assertDontSee('Empresa de Outro Tenant')
            ->assertSee('Meu Catálogo Online');
    }

    public function test_authenticated_user_updates_only_allowed_business_fields(): void
    {
        $user = User::factory()->create();
        $business = $user->business;
        $otherBusiness = Business::factory()->create(['trade_name' => 'Empresa Preservada']);
        $originalSlug = $business->catalog_slug;
        $business->forceFill(['catalog_api_token' => 'credencial-protegida'])->save();

        $response = $this->actingAs($user)->put(route('settings.business.update'), [
            ...$this->validPayload(),
            'business_id' => $otherBusiness->id,
            'catalog_slug' => 'slug-manipulado',
            'catalog_api_token' => 'credencial-manipulada',
        ]);

        $response->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Dados da empresa atualizados com sucesso.')
            ->assertRedirect(route('settings.business.edit'));
        $business->refresh();
        $this->assertSame('Empresa Atualizada', $business->trade_name);
        $this->assertSame('Empresa Atualizada Ltda.', $business->legal_name);
        $this->assertSame('52998224725', $business->document);
        $this->assertSame('44999999999', $business->phone);
        $this->assertSame('contato@empresa.test', $business->email);
        $this->assertSame($originalSlug, $business->catalog_slug);
        $this->assertSame('credencial-protegida', $business->catalog_api_token);
        $this->assertSame('Empresa Preservada', $otherBusiness->fresh()->trade_name);
    }

    public function test_formatted_alphanumeric_cnpj_and_phone_are_normalized(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload();
        $payload['document'] = '12.ABC.345/01DE-35';
        $payload['phone'] = '(44) 3333-4444';

        $response = $this->actingAs($user)
            ->put(route('settings.business.update'), $payload);

        $response->assertSessionHasNoErrors();
        $this->assertSame('12ABC34501DE35', $user->business->fresh()->document);
        $this->assertSame('4433334444', $user->business->fresh()->phone);
    }

    public function test_invalid_business_data_is_rejected_without_changes(): void
    {
        $user = User::factory()->create();
        $business = $user->business;
        $originalAttributes = $business->only([
            'trade_name',
            'legal_name',
            'document',
            'phone',
            'email',
        ]);

        $response = $this->actingAs($user)
            ->from(route('settings.business.edit'))
            ->put(route('settings.business.update'), [
                'trade_name' => '',
                'legal_name' => '',
                'document' => '111.111.111-11',
                'phone' => '(44) 99999-99999',
                'email' => 'email-invalido',
            ]);

        $response->assertRedirect(route('settings.business.edit'))
            ->assertSessionHasErrors([
                'trade_name' => 'Informe o nome fantasia da empresa.',
                'legal_name' => 'Informe a razão social da empresa.',
                'document' => 'CPF inválido. Verifique o número informado.',
                'phone' => 'Informe um telefone com 10 ou 11 dígitos.',
                'email' => 'Informe um e-mail empresarial válido.',
            ]);
        $this->assertSame($originalAttributes, $business->fresh()->only(array_keys($originalAttributes)));
    }

    public function test_document_cannot_duplicate_another_business(): void
    {
        $user = User::factory()->create();
        Business::factory()->create(['document' => '52998224725']);

        $response = $this->actingAs($user)
            ->from(route('settings.business.edit'))
            ->put(route('settings.business.update'), $this->validPayload());

        $response->assertSessionHasErrors([
            'document' => 'Este CPF/CNPJ já está cadastrado.',
        ]);
    }

    /** @return array<string, string> */
    private function validPayload(): array
    {
        return [
            'trade_name' => ' Empresa Atualizada ',
            'legal_name' => ' Empresa Atualizada Ltda. ',
            'document' => '529.982.247-25',
            'phone' => '(44) 99999-9999',
            'email' => ' CONTATO@EMPRESA.TEST ',
        ];
    }
}
