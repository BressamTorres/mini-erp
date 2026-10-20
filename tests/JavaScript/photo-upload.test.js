import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import {
    IMAGE_TYPE_ERROR_MESSAGE,
    TECHNICAL_LIMIT_ERROR_MESSAGE,
    optimizedDimensions,
    preparePhotoInput,
} from '../../resources/js/components/photo-upload.js';

function photo(size, overrides = {}) {
    return {
        size,
        name: 'imagem.jpg',
        type: 'image/jpeg',
        ...overrides,
    };
}

function fileInput(file) {
    return {
        files: [file],
        value: file.name,
    };
}

describe('photo upload preparation', () => {
    it('accepts files above the old fixed limit when the infrastructure allows them', async () => {
        const original = photo(8 * 1024 * 1024);
        const input = fileInput(original);

        const result = await preparePhotoInput(input, {
            maxBytes: 10 * 1024 * 1024,
            optimizer: async (file) => file,
        });

        assert.equal(result.error, '');
        assert.equal(result.photo, original);
        assert.equal(input.value, 'imagem.jpg');
    });

    it('replaces a large image with the optimized file before upload', async () => {
        const original = photo(12 * 1024 * 1024);
        const optimized = photo(900 * 1024);
        const input = fileInput(original);
        let replacement = null;

        const result = await preparePhotoInput(input, {
            maxBytes: 2 * 1024 * 1024,
            optimizer: async () => optimized,
            replaceFile: (target, file) => {
                replacement = file;
                target.files = [file];

                return true;
            },
        });

        assert.equal(result.error, '');
        assert.equal(result.photo, optimized);
        assert.equal(replacement, optimized);
    });

    it('rejects a file that remains above the technical server limit', async () => {
        const original = photo(12 * 1024 * 1024);
        const input = fileInput(original);

        const result = await preparePhotoInput(input, {
            maxBytes: 2 * 1024 * 1024,
            optimizer: async (file) => file,
        });

        assert.equal(result.error, TECHNICAL_LIMIT_ERROR_MESSAGE);
        assert.equal(result.photo, null);
        assert.equal(input.value, '');
    });

    it('does not decode a source disproportionately larger than the server limit', async () => {
        const input = fileInput(photo(20 * 1024 * 1024));
        let optimizerCalled = false;

        const result = await preparePhotoInput(input, {
            maxBytes: 2 * 1024 * 1024,
            optimizer: async (file) => {
                optimizerCalled = true;

                return file;
            },
        });

        assert.equal(result.error, TECHNICAL_LIMIT_ERROR_MESSAGE);
        assert.equal(result.photo, null);
        assert.equal(optimizerCalled, false);
    });

    it('rejects unsupported formats before upload', async () => {
        const input = fileInput(photo(100, { name: 'arquivo.svg', type: 'image/svg+xml' }));

        const result = await preparePhotoInput(input);

        assert.equal(result.error, IMAGE_TYPE_ERROR_MESSAGE);
        assert.equal(result.photo, null);
        assert.equal(input.value, '');
    });

    it('reduces dimensions while preserving the image proportion', () => {
        assert.deepEqual(optimizedDimensions(6000, 4000), { width: 2560, height: 1707 });
        assert.deepEqual(optimizedDimensions(800, 600), { width: 800, height: 600 });
    });
});
