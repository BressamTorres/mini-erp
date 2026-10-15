<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

final class Cnpj implements ValidationRule
{
    public const MESSAGE = 'CNPJ inválido. Verifique o número informado.';

    /** @var list<int> */
    private const FIRST_CHECK_DIGIT_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    /** @var list<int> */
    private const SECOND_CHECK_DIGIT_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || strlen($value) !== 14) {
            return;
        }

        if (preg_match('/^(\d)\1{13}$/', $value) === 1 || ! $this->hasValidCheckDigits($value)) {
            $fail(self::MESSAGE);
        }
    }

    private function hasValidCheckDigits(string $cnpj): bool
    {
        $base = substr($cnpj, 0, 12);
        $firstCheckDigit = $this->calculateCheckDigit($base, self::FIRST_CHECK_DIGIT_WEIGHTS);

        if ((int) $cnpj[12] !== $firstCheckDigit) {
            return false;
        }

        $secondCheckDigit = $this->calculateCheckDigit($base.$firstCheckDigit, self::SECOND_CHECK_DIGIT_WEIGHTS);

        return (int) $cnpj[13] === $secondCheckDigit;
    }

    /**
     * The Receita Federal maps each character to its ASCII value minus 48.
     *
     * @param  list<int>  $weights
     */
    private function calculateCheckDigit(string $base, array $weights): int
    {
        $sum = 0;

        foreach ($weights as $index => $weight) {
            $sum += (ord($base[$index]) - 48) * $weight;
        }

        $remainder = $sum % 11;

        return $remainder < 2 ? 0 : 11 - $remainder;
    }
}
