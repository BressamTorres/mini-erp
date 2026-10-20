export const MAX_IMAGE_SIZE_BYTES = 5 * 1024 * 1024;

export const IMAGE_SIZE_ERROR_MESSAGE = 'A imagem deve ter no máximo 5 MB. Selecione um arquivo menor.';

export function validatePhotoInput(input) {
    const photo = input?.files?.[0] ?? null;
    const error = photo && photo.size > MAX_IMAGE_SIZE_BYTES
        ? IMAGE_SIZE_ERROR_MESSAGE
        : '';

    if (error !== '' && input) {
        input.value = '';
    }

    return {
        photo: error === '' ? photo : null,
        error,
    };
}
