<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class Cpf implements ValidationRule
{
    public const MESSAGE = 'CPF inválido. Verifique o número informado.';

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) !== 11) {
            return;
        }

        if (preg_match('/^(\d)\1{10}$/', $value) === 1 || ! $this->hasValidCheckDigits($value)) {
            $fail(self::MESSAGE);
        }
    }

    private function hasValidCheckDigits(string $cpf): bool
    {
        for ($checkDigitIndex = 9; $checkDigitIndex <= 10; $checkDigitIndex++) {
            $sum = 0;

            for ($digitIndex = 0; $digitIndex < $checkDigitIndex; $digitIndex++) {
                $sum += (int) $cpf[$digitIndex] * (($checkDigitIndex + 1) - $digitIndex);
            }

            $remainder = $sum % 11;
            $expectedDigit = $remainder < 2 ? 0 : 11 - $remainder;

            if ((int) $cpf[$checkDigitIndex] !== $expectedDigit) {
                return false;
            }
        }

        return true;
    }
}
