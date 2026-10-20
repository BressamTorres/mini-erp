import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import {
    IMAGE_SIZE_ERROR_MESSAGE,
    MAX_IMAGE_SIZE_BYTES,
    validatePhotoInput,
} from '../../resources/js/components/photo-upload.js';

function fileInput(size) {
    return {
        files: [{ size }],
        value: 'imagem.jpg',
    };
}

describe('photo upload size validation', () => {
    it('accepts an image with four megabytes', () => {
        const input = fileInput(4 * 1024 * 1024);

        const result = validatePhotoInput(input);

        assert.equal(result.error, '');
        assert.equal(result.photo.size, 4 * 1024 * 1024);
        assert.equal(input.value, 'imagem.jpg');
    });

    it('accepts an image with exactly five megabytes', () => {
        const input = fileInput(MAX_IMAGE_SIZE_BYTES);

        const result = validatePhotoInput(input);

        assert.equal(result.error, '');
        assert.equal(result.photo.size, MAX_IMAGE_SIZE_BYTES);
        assert.equal(input.value, 'imagem.jpg');
    });

    it('rejects and clears only an image larger than five megabytes', () => {
        const input = fileInput(MAX_IMAGE_SIZE_BYTES + 1);

        const result = validatePhotoInput(input);

        assert.equal(result.error, IMAGE_SIZE_ERROR_MESSAGE);
        assert.equal(result.photo, null);
        assert.equal(input.value, '');
    });

});
