<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\FormSubmissionService;
use App\Services\ProductService;
use App\Services\UploadLimitService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private ProductService $products,
        private FormSubmissionService $submissions,
        private UploadLimitService $uploadLimits,
    ) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $showInactive = $request->boolean('show_inactive');

        return view('products.index', [
            'products' => $this->products->paginate($search !== '' ? $search : null, $showInactive),
            'nextSkuPreview' => $this->products->estimateNextSku(),
            'uploadMaxBytes' => $this->uploadLimits->maxFileBytes(),
            'search' => $search,
            'showInactive' => $showInactive,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->safe()->except('submission_token');

        $this->submissions->run(
            'products.store',
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('submission_token'),
            $data,
            fn () => $this->products->create($data),
        );

        return to_route('products.index')->with('success', 'Produto cadastrado com sucesso.');
    }

    public function update(UpdateProductRequest $request, int $product): RedirectResponse
    {
        $data = $request->safe()->except('submission_token');

        $this->submissions->run(
            "products.update.{$product}",
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('submission_token'),
            $data,
            fn () => $this->products->update($product, $data),
        );

        return to_route('products.index')->with('success', 'Produto atualizado com sucesso.');
    }

    public function deactivate(int $product): RedirectResponse
    {
        $this->products->deactivate($product);

        return to_route('products.index')->with('success', 'Produto inativado com sucesso.');
    }
}
