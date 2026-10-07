<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSellerRequest;
use App\Http\Requests\UpdateSellerRequest;
use App\Services\SellerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function __construct(private SellerService $sellers) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('sellers.index', [
            'sellers' => $this->sellers->paginate($search !== '' ? $search : null),
            'search' => $search,
        ]);
    }

    public function store(StoreSellerRequest $request): RedirectResponse
    {
        $this->sellers->create($request->validated());

        return to_route('sellers.index')->with('success', 'Vendedor cadastrado com sucesso.');
    }

    public function update(UpdateSellerRequest $request, int $seller): RedirectResponse
    {
        $this->sellers->update($seller, $request->validated());

        return to_route('sellers.index')->with('success', 'Vendedor atualizado com sucesso.');
    }

    public function destroy(int $seller): RedirectResponse
    {
        $this->sellers->destroy($seller);

        return to_route('sellers.index')->with('success', 'Vendedor excluído com sucesso.');
    }
}
