<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\ProductService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(private ProductService $products) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('products.index', [
            'products' => $this->products->paginate($search !== '' ? $search : null),
            'search' => $search,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $this->products->create($request->validated());

        return to_route('products.index')->with('success', 'Produto cadastrado com sucesso.');
    }

    public function update(UpdateProductRequest $request, int $product): RedirectResponse
    {
        $this->products->update($product, $request->validated());

        return to_route('products.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function destroy(int $product): RedirectResponse
    {
        $this->products->destroy($product);

        return to_route('products.index')->with('success', 'Produto excluído com sucesso.');
    }
}
