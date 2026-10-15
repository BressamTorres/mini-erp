<?php

namespace Tests\Unit\Rules;

use App\Rules\Cpf;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CpfTest extends TestCase
{
    #[DataProvider('validCpfs')]
    public function test_accepts_cpf_with_valid_check_digits(string $cpf): void
    {
        $validator = Validator::make(['cpf' => $cpf], ['cpf' => [new Cpf]]);

        $this->assertFalse($validator->fails());
    }

    /** @return array<string, array{string}> */
    public static function validCpfs(): array
    {
        return [
            'regular digits' => ['52998224725'],
            'another valid cpf' => ['11144477735'],
            'leading zero' => ['01234567890'],
        ];
    }

    #[DataProvider('invalidCpfs')]
    public function test_rejects_cpf_with_invalid_check_digits_or_repeated_sequence(string $cpf): void
    {
        $validator = Validator::make(['cpf' => $cpf], ['cpf' => [new Cpf]]);

        $this->assertTrue($validator->fails());
        $this->assertSame('CPF inválido. Verifique o número informado.', $validator->errors()->first('cpf'));
    }

    /** @return array<string, array{string}> */
    public static function invalidCpfs(): array
    {
        return [
            'invalid check digits' => ['52998224724'],
            'zero sequence' => ['00000000000'],
            'repeated sequence' => ['11111111111'],
        ];
    }
}
