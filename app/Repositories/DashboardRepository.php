<?php

namespace App\Repositories;

use App\Models\Person;
use App\Models\Sale;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class DashboardRepository
{
    /**
     * @return array{totalSales: string, salesCount: int, customers: int, productsSold: int}
     */
    public function indicators(?CarbonInterface $startDate, ?CarbonInterface $endDate): array
    {
        $salesSummary = $this->activeSalesQuery($startDate, $endDate)
            ->selectRaw('COUNT(*) as sales_count, COALESCE(SUM(sales.total), 0) as total_sales')
            ->firstOrFail();

        return [
            'totalSales' => (string) $salesSummary->getAttribute('total_sales'),
            'salesCount' => (int) $salesSummary->getAttribute('sales_count'),
            'customers' => $this->activeSalesQuery($startDate, $endDate)
                ->distinct()
                ->count('sales.customer_id'),
            'productsSold' => (int) $this->activeSalesQuery($startDate, $endDate)
                ->join('sale_items', 'sale_items.sale_id', '=', 'sales.id')
                ->sum('sale_items.quantity'),
        ];
    }

    /** @return Collection<int, Sale> */
    public function salesByDay(?CarbonInterface $startDate, ?CarbonInterface $endDate): Collection
    {
        return $this->activeSalesQuery($startDate, $endDate)
            ->selectRaw('DATE(created_at) as sale_date, COALESCE(SUM(total), 0) as total')
            ->groupByRaw('DATE(created_at)')
            ->orderBy('sale_date')
            ->get();
    }

    /** @return Collection<int, Sale> */
    public function salesBySeller(?CarbonInterface $startDate, ?CarbonInterface $endDate): Collection
    {
        return $this->activeSalesQuery($startDate, $endDate)
            ->join('persons as sellers', 'sellers.id', '=', 'sales.seller_id')
            ->where('sellers.type', Person::TYPE_SELLER)
            ->selectRaw('sellers.id as seller_id, sellers.name as seller_name, COALESCE(SUM(sales.total), 0) as total')
            ->groupBy('sellers.id', 'sellers.name')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    /** @return Builder<Sale> */
    private function activeSalesQuery(?CarbonInterface $startDate, ?CarbonInterface $endDate): Builder
    {
        return Sale::query()
            ->where('sales.active', true)
            ->when($startDate, fn (Builder $query, CarbonInterface $date): Builder => $query->where('sales.created_at', '>=', $date))
            ->when($endDate, fn (Builder $query, CarbonInterface $date): Builder => $query->where('sales.created_at', '<=', $date));
    }
}
