<?php

namespace App\Services;

use App\Models\Business;
use App\Models\Person;
use Illuminate\Validation\ValidationException;

class CatalogCustomerService
{
    public function __construct(
        private BusinessContext $businessContext,
        private PersonService $people,
    ) {}

    /**
     * @param  array{name: string, document: string, phone: string, email: string}  $data
     */
    public function resolve(array $data): Person
    {
        Business::query()
            ->whereKey($this->businessContext->id())
            ->lockForUpdate()
            ->firstOrFail();

        $customers = Person::query()
            ->where('type', Person::TYPE_CUSTOMER)
            ->whereIn('document', [$data['document'], $this->formatCpf($data['document'])])
            ->lockForUpdate()
            ->limit(2)
            ->get();

        if ($customers->count() > 1) {
            throw ValidationException::withMessages([
                'customer.document' => 'Existe mais de um cliente com este CPF. Corrija a duplicidade no Mini ERP antes de continuar.',
            ]);
        }

        $customer = $customers->first();

        if ($customer !== null) {
            if (! $customer->active) {
                throw ValidationException::withMessages([
                    'customer.document' => 'O cliente cadastrado com este CPF está inativo no Mini ERP.',
                ]);
            }

            return $customer;
        }

        return $this->people->create(Person::TYPE_CUSTOMER, $data);
    }

    private function formatCpf(string $cpf): string
    {
        return sprintf(
            '%s.%s.%s-%s',
            substr($cpf, 0, 3),
            substr($cpf, 3, 3),
            substr($cpf, 6, 3),
            substr($cpf, 9, 2),
        );
    }
}
