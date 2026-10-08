<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use App\Services\PersonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private PersonService $persons) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();
        $showInactive = $request->boolean('show_inactive');

        return view('customers.index', [
            'customers' => $this->persons->paginate(Person::TYPE_CUSTOMER, $search !== '' ? $search : null, $showInactive),
            'search' => $search,
            'showInactive' => $showInactive,
        ]);
    }

    public function store(StorePersonRequest $request): RedirectResponse
    {
        $this->persons->create(Person::TYPE_CUSTOMER, $request->validated());

        return to_route('customers.index')
            ->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function update(UpdatePersonRequest $request, int $customer): RedirectResponse
    {
        $this->persons->update(Person::TYPE_CUSTOMER, $customer, $request->validated());

        return to_route('customers.index')
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function deactivate(int $customer): RedirectResponse
    {
        $this->persons->deactivate(Person::TYPE_CUSTOMER, $customer);

        return to_route('customers.index')
            ->with('success', 'Cliente inativado com sucesso.');
    }
}
