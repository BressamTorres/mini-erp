<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\CustomerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(private CustomerService $customers) {}

    public function index(Request $request): View
    {
        $search = $request->string('search')->trim()->toString();

        return view('customers.index', [
            'customers' => $this->customers->paginate($search !== '' ? $search : null),
            'search' => $search,
        ]);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $this->customers->create($request->validated());

        return to_route('customers.index')
            ->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function update(UpdateCustomerRequest $request, int $customer): RedirectResponse
    {
        $this->customers->update($customer, $request->validated());

        return to_route('customers.index')
            ->with('success', 'Cliente atualizado com sucesso.');
    }

    public function destroy(int $customer): RedirectResponse
    {
        $this->customers->destroy($customer);

        return to_route('customers.index')
            ->with('success', 'Cliente excluído com sucesso.');
    }
}
