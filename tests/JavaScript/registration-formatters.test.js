import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { documentValidationMessage, formatDocument } from '../../resources/js/components/person-document.js';
import { formatPhone } from '../../resources/js/components/phone.js';

describe('registration phone formatter', () => {
    it('formats fixed and mobile phones dynamically', () => {
        assert.equal(formatPhone('4433334444'), '(44) 3333-4444');
        assert.equal(formatPhone('44999999999'), '(44) 99999-9999');
        assert.equal(formatPhone('449999999999'), '(44) 99999-9999');
    });

    it('allows editing an already formatted phone without duplicating punctuation', () => {
        assert.equal(formatPhone('(44) 99999-9999'), '(44) 99999-9999');
        assert.equal(formatPhone('(44) 3333-444'), '(44) 3333-444');
    });
});

describe('registration document formatter', () => {
    it('formats CPF and numeric CNPJ with or without an existing mask', () => {
        assert.equal(formatDocument('52998224725'), '529.982.247-25');
        assert.equal(formatDocument('529.982.247-25'), '529.982.247-25');
        assert.equal(formatDocument('11222333000181'), '11.222.333/0001-81');
        assert.equal(formatDocument('11.222.333/0001-81'), '11.222.333/0001-81');
    });

    it('preserves and formats valid alphanumeric CNPJ characters', () => {
        assert.equal(formatDocument('12abc34501de35'), '12.ABC.345/01DE-35');
        assert.equal(formatDocument('12.ABC.345/01DE-35'), '12.ABC.345/01DE-35');
        assert.equal(documentValidationMessage('12.ABC.345/01DE-35'), '');
    });
});
