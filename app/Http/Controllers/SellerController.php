<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use App\Services\PersonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SellerController extends Controller
{
    public function __construct(private PersonService $persons) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $showInactive = $request->boolean('show_inactive');

        return view('sellers.index', [
            'sellers' => $this->persons->paginate(Person::TYPE_SELLER, $search !== '' ? $search : null, $showInactive),
            'search' => $search,
            'showInactive' => $showInactive,
        ]);
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $this->persons->create(Person::TYPE_SELLER, $request->validated());

        return to_route('sellers.index')->with('success', 'Vendedor cadastrado com sucesso.');
    }

    public function update(UpdatePersonRequest $request, int $seller): RedirectResponse
    {
        $this->persons->update(Person::TYPE_SELLER, $seller, $request->validated());

        return to_route('sellers.index')->with('success', 'Vendedor atualizado com sucesso.');
    }

    public function deactivate(int $seller): RedirectResponse
    {
        $this->persons->deactivate(Person::TYPE_SELLER, $seller);

        return to_route('sellers.index')->with('success', 'Vendedor inativado com sucesso.');
    }
}
