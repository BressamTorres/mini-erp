<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Models\Person;
use App\Services\FormSubmissionService;
use App\Services\PersonService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CustomerController extends Controller
{
    public function __construct(
        private PersonService $persons,
        private FormSubmissionService $submissions,
    ) {}

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
        $data = $request->safe()->except('submission_token');

        $this->submissions->run(
            'customers.store',
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('submission_token'),
            $data,
            fn () => $this->persons->create(Person::TYPE_CUSTOMER, $data),
        );

        return to_route('customers.index')
            ->with('success', 'Cliente cadastrado com sucesso.');
    }

    public function update(UpdatePersonRequest $request, int $customer): RedirectResponse
    {
        $data = $request->safe()->except('submission_token');

        $this->submissions->run(
            "customers.update.{$customer}",
            (int) $request->user()->getAuthIdentifier(),
            $request->validated('submission_token'),
            $data,
            fn () => $this->persons->update(Person::TYPE_CUSTOMER, $customer, $data),
        );

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
