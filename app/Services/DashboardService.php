<?php

namespace App\Services;

use App\Models\Sale;
use App\Repositories\DashboardRepository;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardService
{
    public function __construct(private DashboardRepository $dashboardRepository) {}

    /**
     * @return array{
     *     indicators: array{totalSales: string, salesCount: int, customers: int, productsSold: int},
     *     dailySalesChart: array{items: array<int, array{label: string, fullLabel: string, formattedTotal: string, x: float, y: float}>, points: string, scale: array<int, string>, labels: array<int, string>},
     *     sellerSalesChart: array<int, array{name: string, formattedTotal: string, percentage: float}>,
     *     period: array{startDate: ?string, endDate: ?string}
     * }
     */
    public function overview(?string $startDate = null, ?string $endDate = null): array
    {
        $periodStart = $this->parseStartDate($startDate);
        $periodEnd = $this->parseEndDate($endDate);
        $indicators = $this->dashboardRepository->indicators($periodStart, $periodEnd);
        $indicators['totalSales'] = $this->formatCurrency($indicators['totalSales']);

        return [
            'indicators' => $indicators,
            'dailySalesChart' => $this->prepareDailySalesChart(
                $this->dashboardRepository->salesByDay($periodStart, $periodEnd),
            ),
            'sellerSalesChart' => $this->prepareSellerSalesChart(
                $this->dashboardRepository->salesBySeller($periodStart, $periodEnd),
            ),
            'period' => [
                'startDate' => $startDate,
                'endDate' => $endDate,
            ],
        ];
    }

    private function parseStartDate(?string $date): ?CarbonInterface
    {
        return $date === null ? null : CarbonImmutable::parse($date)->startOfDay();
    }

    private function parseEndDate(?string $date): ?CarbonInterface
    {
        return $date === null ? null : CarbonImmutable::parse($date)->endOfDay();
    }

    /**
     * @param  Collection<int, Sale>  $sales
     * @return array{items: array<int, array{label: string, fullLabel: string, formattedTotal: string, x: float, y: float}>, points: string, scale: array<int, string>, labels: array<int, string>}
     */
    private function prepareDailySalesChart(Collection $sales): array
    {
        $maximum = (float) ($sales->max('total') ?? 0);
        $lastIndex = max($sales->count() - 1, 1);

        $items = $sales->values()->map(function (Sale $sale, int $index) use ($maximum, $lastIndex): array {
            $total = (float) $sale->getAttribute('total');
            $date = CarbonImmutable::parse((string) $sale->getAttribute('sale_date'));

            return [
                'label' => $date->format('d/m'),
                'fullLabel' => $date->format('d/m/Y'),
                'formattedTotal' => $this->formatCurrency((string) $total),
                'x' => round(16 + (($index / $lastIndex) * 568), 2),
                'y' => round(160 - ($maximum > 0 ? ($total / $maximum) * 144 : 0), 2),
            ];
        })->all();

        $labels = collect($items)->pluck('label');

        if ($labels->count() > 3) {
            $labels = collect([
                $labels->first(),
                $labels->get((int) floor(($labels->count() - 1) / 2)),
                $labels->last(),
            ]);
        }

        return [
            'items' => $items,
            'points' => collect($items)->map(fn (array $item): string => $item['x'].','.$item['y'])->implode(' '),
            'scale' => [
                $this->formatCurrency((string) $maximum),
                $this->formatCurrency((string) ($maximum / 2)),
                $this->formatCurrency('0'),
            ],
            'labels' => $labels->filter()->values()->all(),
        ];
    }

    /**
     * @param  Collection<int, Sale>  $sales
     * @return array<int, array{name: string, formattedTotal: string, percentage: float}>
     */
    private function prepareSellerSalesChart(Collection $sales): array
    {
        $maximum = (float) ($sales->max('total') ?? 0);

        return $sales->map(function (Sale $sale) use ($maximum): array {
            $total = (float) $sale->getAttribute('total');

            return [
                'name' => (string) $sale->getAttribute('seller_name'),
                'formattedTotal' => $this->formatCurrency((string) $total),
                'percentage' => round($maximum > 0 ? ($total / $maximum) * 100 : 0, 2),
            ];
        })->all();
    }

    private function formatCurrency(string $value): string
    {
        return 'R$ '.number_format((float) $value, 2, ',', '.');
    }
}
