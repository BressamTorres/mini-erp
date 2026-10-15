<?php

namespace Tests\Unit\Rules;

use App\Rules\Cnpj;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CnpjTest extends TestCase
{
    #[DataProvider('validCnpjs')]
    public function test_accepts_numeric_and_alphanumeric_cnpj_with_valid_check_digits(string $cnpj): void
    {
        $validator = Validator::make(['cnpj' => $cnpj], ['cnpj' => [new Cnpj]]);

        $this->assertFalse($validator->fails());
    }

    /** @return array<string, array{string}> */
    public static function validCnpjs(): array
    {
        return [
            'traditional numeric' => ['12345678000195'],
            'official alphanumeric example' => ['12ABC34501DE35'],
        ];
    }

    #[DataProvider('invalidCnpjs')]
    public function test_rejects_cnpj_with_invalid_check_digits_or_repeated_sequence(string $cnpj): void
    {
        $validator = Validator::make(['cnpj' => $cnpj], ['cnpj' => [new Cnpj]]);

        $this->assertTrue($validator->fails());
        $this->assertSame('CNPJ inválido. Verifique o número informado.', $validator->errors()->first('cnpj'));
    }

    /** @return array<string, array{string}> */
    public static function invalidCnpjs(): array
    {
        return [
            'numeric invalid check digits' => ['12345678000190'],
            'alphanumeric invalid check digits' => ['12ABC34501DE34'],
            'repeated zero sequence' => ['00000000000000'],
        ];
    }
}
