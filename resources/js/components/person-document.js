export const INVALID_CPF_MESSAGE = 'CPF inválido. Verifique o número informado.';
export const INVALID_CNPJ_MESSAGE = 'CNPJ inválido. Verifique o número informado.';

const INVALID_DOCUMENT_LENGTH_MESSAGE = 'Informe um CPF com 11 dígitos ou CNPJ com 14 caracteres.';
const FIRST_CNPJ_CHECK_DIGIT_WEIGHTS = [5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];
const SECOND_CNPJ_CHECK_DIGIT_WEIGHTS = [6, 5, 4, 3, 2, 9, 8, 7, 6, 5, 4, 3, 2];

function normalizeDocument(value) {
    return String(value ?? '').toUpperCase().replace(/[^A-Z0-9]/g, '').slice(0, 14);
}

export function formatDocument(value) {
    const document = normalizeDocument(value);

    if (/^\d{0,11}$/.test(document)) {
        return document
            .replace(/^(\d{3})(\d)/, '$1.$2')
            .replace(/^(\d{3})\.(\d{3})(\d)/, '$1.$2.$3')
            .replace(/^(\d{3})\.(\d{3})\.(\d{3})(\d)/, '$1.$2.$3-$4');
    }

    return document
        .replace(/^([A-Z0-9]{2})([A-Z0-9])/, '$1.$2')
        .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})([A-Z0-9])/, '$1.$2.$3')
        .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})\.([A-Z0-9]{3})([A-Z0-9])/, '$1.$2.$3/$4')
        .replace(/^([A-Z0-9]{2})\.([A-Z0-9]{3})\.([A-Z0-9]{3})\/([A-Z0-9]{4})(\d)/, '$1.$2.$3/$4-$5');
}

export function documentValidationMessage(value) {
    const document = normalizeDocument(value);

    if (document.length === 0) {
        return '';
    }

    if (/^\d{11}$/.test(document)) {
        return isValidCpf(document) ? '' : INVALID_CPF_MESSAGE;
    }

    if (document.length !== 14) {
        return INVALID_DOCUMENT_LENGTH_MESSAGE;
    }

    if (!/^[A-Z0-9]{12}\d{2}$/.test(document)) {
        return INVALID_CNPJ_MESSAGE;
    }

    return isValidCnpj(document) ? '' : INVALID_CNPJ_MESSAGE;
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

function isValidCnpj(cnpj) {
    if (/^(\d)\1{13}$/.test(cnpj)) {
        return false;
    }

    const base = cnpj.slice(0, 12);
    const firstCheckDigit = calculateCnpjCheckDigit(base, FIRST_CNPJ_CHECK_DIGIT_WEIGHTS);

    if (Number(cnpj[12]) !== firstCheckDigit) {
        return false;
    }

    const secondCheckDigit = calculateCnpjCheckDigit(`${base}${firstCheckDigit}`, SECOND_CNPJ_CHECK_DIGIT_WEIGHTS);

    return Number(cnpj[13]) === secondCheckDigit;
}

function calculateCnpjCheckDigit(base, weights) {
    const sum = weights.reduce((total, weight, index) => (
        total + ((base.charCodeAt(index) - 48) * weight)
    ), 0);
    const remainder = sum % 11;

    return remainder < 2 ? 0 : 11 - remainder;
}
