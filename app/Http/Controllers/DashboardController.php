<?php

namespace App\Http\Controllers;

use App\Http\Requests\DashboardRequest;
use App\Services\DashboardService;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(private DashboardService $dashboardService) {}

    public function index(DashboardRequest $request): View
    {
        $filters = $request->validated();

        return view('dashboard', $this->dashboardService->overview(
            $filters['start_date'] ?? null,
            $filters['end_date'] ?? null,
        ));
    }
}
