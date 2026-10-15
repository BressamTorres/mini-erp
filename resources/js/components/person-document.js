export const INVALID_CPF_MESSAGE = 'CPF inválido. Verifique o número informado.';

const INVALID_DOCUMENT_LENGTH_MESSAGE = 'Informe um CPF com 11 dígitos ou CNPJ com 14 dígitos.';

function onlyDigits(value, limit = 14) {
    return String(value ?? '').replace(/\D/g, '').slice(0, limit);
}

export function formatDocument(value) {
    const digits = onlyDigits(value);

    if (digits.length <= 11) {
        return digits
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
    }

    return digits
        .replace(/^(\d{2})(\d)/, '$1.$2')
        .replace(/^(\d{2})\.(\d{3})(\d)/, '$1.$2.$3')
        .replace(/^(\d{2})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3/$4')
        .replace(/^(\d{2})\.(\d{3})\.(\d{3})\/(\d{4})(\d)/, '$1.$2.$3/$4-$5');
}

export function documentValidationMessage(value) {
    const digits = onlyDigits(value);

    if (digits.length === 0 || digits.length === 14) {
        return '';
    }

    if (digits.length !== 11) {
        return INVALID_DOCUMENT_LENGTH_MESSAGE;
    }

    return isValidCpf(digits) ? '' : INVALID_CPF_MESSAGE;
}

function isValidCpf(cpf) {
    if (/^(\d)\1{10}$/.test(cpf)) {
        return false;
    }

    for (let checkDigitIndex = 9; checkDigitIndex <= 10; checkDigitIndex += 1) {
        let sum = 0;

        for (let digitIndex = 0; digitIndex < checkDigitIndex; digitIndex += 1) {
            sum += Number(cpf[digitIndex]) * ((checkDigitIndex + 1) - digitIndex);
        }

        const remainder = sum % 11;
        const expectedDigit = remainder < 2 ? 0 : 11 - remainder;

        if (Number(cpf[checkDigitIndex]) !== expectedDigit) {
            return false;
        }
    }

    return true;
}
