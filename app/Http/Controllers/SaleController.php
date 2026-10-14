<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Services\FormSubmissionService;
use App\Services\SaleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SaleController extends Controller
{
    public function __construct(
        private SaleService $sales,
        private FormSubmissionService $submissions,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $showInactive = $request->boolean('show_inactive');
        $formOptions = $this->sales->formOptions();
        $oldSaleId = (string) $request->session()->getOldInput('sale_id', '');
        $formSale = $request->session()->getOldInput('form_mode') === 'edit' && ctype_digit($oldSaleId)
            ? $this->sales->details((int) $oldSaleId)
            : null;

        return view('sales.index', [
            'sales' => $this->sales->paginate($search !== '' ? $search : null, $showInactive),
            'search' => $search,
            'showInactive' => $showInactive,
            'formSale' => $formSale,
            ...$formOptions,
        ]);
    }

    public function show(int $sale): JsonResponse
    {
        return response()->json($this->sales->details($sale));
    }

    public function store(StoreSaleRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('submission_token');

        $this->submissions->run(
            'sales.store',
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('submission_token'),
            $data,
            fn () => $this->sales->create($data),
        );

        return to_route('sales.index')->with('success', 'Venda registrada com sucesso.');
    }

    public function update(UpdateSaleRequest $request, int $sale): RedirectResponse
    {
        $data = $request->safe()->except('submission_token');

        $this->submissions->run(
            "sales.update.{$sale}",
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('submission_token'),
            $data,
            fn () => $this->sales->update($sale, $data),
        );

        return to_route('sales.index')->with('success', 'Venda atualizada com sucesso.');
    }

    public function deactivate(int $sale): RedirectResponse
    {
        $this->sales->deactivate($sale);

        return to_route('sales.index')->with('success', 'Venda inativada com sucesso.');
    }
}
