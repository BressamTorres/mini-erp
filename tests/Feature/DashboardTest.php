<?php

namespace Tests\Feature;

use App\Models\Person;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect(route('login'));
    }

    public function test_dashboard_displays_zero_indicators_and_empty_state_without_records(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertSee('Visão geral do seu negócio.')
            ->assertSee('R$ 0,00')
            ->assertViewHas('indicators', [
                'totalSales' => 'R$ 0,00',
                'salesCount' => 0,
                'customers' => 0,
                'productsSold' => 0,
            ])
            ->assertViewHas('dailySalesChart', fn (array $chart): bool => $chart['items'] === [])
            ->assertViewHas('sellerSalesChart', [])
            ->assertSee('Vendas por dia')
            ->assertSee('Vendas por vendedor')
            ->assertSee('Nenhuma venda no período')
            ->assertDontSee('Últimas vendas');
    }

    public function test_dashboard_indicators_only_include_active_records(): void
    {
        $user = User::factory()->create();
        $activeCustomer = Person::factory()->customer()->create(['active' => true]);
        Person::factory()->customer()->create(['active' => false]);
        $seller = Person::factory()->seller()->create(['active' => true]);

        $activeSale = Sale::factory()
            ->for($activeCustomer, 'customer')
            ->for($seller, 'seller')
            ->create(['active' => true]);
        $activeSale->updateQuietly(['total' => '1234.56']);
        $activeSale->items()->update(['quantity' => 4]);

        $inactiveSale = Sale::factory()
            ->for($activeCustomer, 'customer')
            ->for($seller, 'seller')
            ->inactive()
            ->create();
        $inactiveSale->updateQuietly(['total' => '9999.99']);
        $inactiveSale->items()->update(['quantity' => 9]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response
            ->assertOk()
            ->assertViewHas('indicators', [
                'totalSales' => 'R$ 1.234,56',
                'salesCount' => 1,
                'customers' => 1,
                'productsSold' => 4,
            ])
            ->assertSee('R$ 1.234,56')
            ->assertDontSee('R$ 9.999,99')
            ->assertDontSee('Últimas vendas');
    }

    public function test_period_filter_applies_to_all_indicators_and_charts(): void
    {
        $user = User::factory()->create();
        $firstCustomer = Person::factory()->customer()->create();
        $secondCustomer = Person::factory()->customer()->create();
        $firstSeller = Person::factory()->seller()->create(['name' => 'Vendedor Um']);
        $secondSeller = Person::factory()->seller()->create(['name' => 'Vendedor Dois']);

        $firstSale = Sale::factory()
            ->for($firstCustomer, 'customer')
            ->for($firstSeller, 'seller')
            ->create();
        $firstSale->total = '100.00';
        $firstSale->created_at = '2026-01-01 00:00:00';
        $firstSale->saveQuietly();
        $firstSale->items()->update(['quantity' => 2]);

        $secondSale = Sale::factory()
            ->for($secondCustomer, 'customer')
            ->for($secondSeller, 'seller')
            ->create();
        $secondSale->total = '250.00';
        $secondSale->created_at = '2026-01-31 23:59:59';
        $secondSale->saveQuietly();
        $secondSale->items()->update(['quantity' => 3]);

        $outsidePeriodSale = Sale::factory()
            ->for($firstCustomer, 'customer')
            ->for($firstSeller, 'seller')
            ->create();
        $outsidePeriodSale->total = '500.00';
        $outsidePeriodSale->created_at = '2025-12-31 23:59:59';
        $outsidePeriodSale->saveQuietly();
        $outsidePeriodSale->items()->update(['quantity' => 7]);

        $inactiveSale = Sale::factory()
            ->for($firstCustomer, 'customer')
            ->for($firstSeller, 'seller')
            ->inactive()
            ->create();
        $inactiveSale->total = '999.00';
        $inactiveSale->created_at = '2026-01-15 10:00:00';
        $inactiveSale->saveQuietly();
        $inactiveSale->items()->update(['quantity' => 11]);

        $response = $this->actingAs($user)->get(route('dashboard', [
            'start_date' => '2026-01-01',
            'end_date' => '2026-01-31',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('indicators', [
                'totalSales' => 'R$ 350,00',
                'salesCount' => 2,
                'customers' => 2,
                'productsSold' => 5,
            ])
            ->assertViewHas('dailySalesChart', function (array $chart): bool {
                $this->assertSame(['01/01', '31/01'], array_column($chart['items'], 'label'));
                $this->assertSame(['R$ 100,00', 'R$ 250,00'], array_column($chart['items'], 'formattedTotal'));

                return true;
            })
            ->assertViewHas('sellerSalesChart', function (array $chart): bool {
                $this->assertSame(['Vendedor Dois', 'Vendedor Um'], array_column($chart, 'name'));
                $this->assertSame(['R$ 250,00', 'R$ 100,00'], array_column($chart, 'formattedTotal'));

                return true;
            })
            ->assertViewHas('period', [
                'startDate' => '2026-01-01',
                'endDate' => '2026-01-31',
            ])
            ->assertSee('01/01/2026')
            ->assertSee('31/01/2026')
            ->assertDontSee('Últimas vendas');
    }

    public function test_period_without_sales_displays_zero_indicators_and_empty_charts(): void
    {
        $user = User::factory()->create();
        $customer = Person::factory()->customer()->create();
        $seller = Person::factory()->seller()->create();
        $sale = Sale::factory()
            ->for($customer, 'customer')
            ->for($seller, 'seller')
            ->create();
        $sale->created_at = '2026-02-15 10:00:00';
        $sale->saveQuietly();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'start_date' => '2026-03-01',
            'end_date' => '2026-03-31',
        ]));

        $response
            ->assertOk()
            ->assertViewHas('indicators', [
                'totalSales' => 'R$ 0,00',
                'salesCount' => 0,
                'customers' => 0,
                'productsSold' => 0,
            ])
            ->assertViewHas('dailySalesChart', fn (array $chart): bool => $chart['items'] === [])
            ->assertViewHas('sellerSalesChart', [])
            ->assertSee('Nenhuma venda no período');
    }

    public function test_period_rejects_start_date_after_end_date(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get(route('dashboard', [
            'start_date' => '2026-04-30',
            'end_date' => '2026-04-01',
        ]));

        $response
            ->assertRedirect()
            ->assertSessionHasErrors('end_date');
    }
}
