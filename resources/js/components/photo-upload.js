const SUPPORTED_IMAGE_TYPES = new Set(['image/jpeg', 'image/png', 'image/webp']);
const SUPPORTED_IMAGE_EXTENSIONS = new Set(['jpg', 'jpeg', 'png', 'webp']);

export const TECHNICAL_LIMIT_ERROR_MESSAGE = 'A imagem excede o limite técnico do servidor. Selecione um arquivo menor.';
export const IMAGE_TYPE_ERROR_MESSAGE = 'A foto deve estar no formato JPG, JPEG, PNG ou WEBP.';
export const IMAGE_DIMENSIONS_ERROR_MESSAGE = 'A imagem possui dimensões muito grandes. Selecione uma imagem menor.';
export const IMAGE_PROCESSING_MESSAGE = 'Aguarde a otimização da imagem.';
export const MAX_OPTIMIZED_DIMENSION = 2560;
export const MAX_SOURCE_PIXELS = 40_000_000;
export const MAX_SOURCE_DIMENSION = 10_000;
const MAX_SOURCE_TO_SERVER_RATIO = 8;

class UnsafeImageDimensionsError extends Error {}

export function optimizedDimensions(width, height, maxDimension = MAX_OPTIMIZED_DIMENSION) {
    const scale = Math.min(1, maxDimension / Math.max(width, height));

    return {
        width: Math.max(1, Math.round(width * scale)),
        height: Math.max(1, Math.round(height * scale)),
    };
}

export async function preparePhotoInput(input, options = {}) {
    const photo = input?.files?.[0] ?? null;

    if (!photo) {
        return { photo: null, error: '' };
    }

    if (!isSupportedImage(photo)) {
        clearInput(input);

        return { photo: null, error: IMAGE_TYPE_ERROR_MESSAGE };
    }

    const optimizer = options.optimizer ?? optimizeImage;
    const replaceFile = options.replaceFile ?? replaceInputFile;
    const maxBytes = Number(options.maxBytes ?? 0);

    if (maxBytes > 0 && photo.size > maxBytes * MAX_SOURCE_TO_SERVER_RATIO) {
        clearInput(input);

        return { photo: null, error: TECHNICAL_LIMIT_ERROR_MESSAGE };
    }

    let preparedPhoto = photo;

    try {
        const optimizedPhoto = await optimizer(photo);

        if (optimizedPhoto !== photo && replaceFile(input, optimizedPhoto)) {
            preparedPhoto = optimizedPhoto;
        }
    } catch (error) {
        if (error instanceof UnsafeImageDimensionsError) {
            clearInput(input);

            return { photo: null, error: IMAGE_DIMENSIONS_ERROR_MESSAGE };
        }
    }

    if (maxBytes > 0 && preparedPhoto.size > maxBytes) {
        clearInput(input);

        return { photo: null, error: TECHNICAL_LIMIT_ERROR_MESSAGE };
    }

    return { photo: preparedPhoto, error: '' };
}

async function optimizeImage(photo) {
    const decoded = await decodeImage(photo);

    try {
        if (
            decoded.width > MAX_SOURCE_DIMENSION
            || decoded.height > MAX_SOURCE_DIMENSION
            || decoded.width * decoded.height > MAX_SOURCE_PIXELS
        ) {
            throw new UnsafeImageDimensionsError();
        }

        const dimensions = optimizedDimensions(decoded.width, decoded.height);
        const canvas = document.createElement('canvas');
        canvas.width = dimensions.width;
        canvas.height = dimensions.height;

        const context = canvas.getContext('2d', { alpha: photo.type !== 'image/jpeg' });

        if (!context) {
            return photo;
        }

        context.drawImage(decoded.source, 0, 0, dimensions.width, dimensions.height);

        const blob = await canvasToBlob(canvas, photo.type, photo.type === 'image/png' ? undefined : 0.85);

        if (!blob || blob.size >= photo.size) {
            return photo;
        }

        return new File([blob], photo.name, {
            type: photo.type,
            lastModified: photo.lastModified,
        });
    } finally {
        decoded.release();
    }
}

async function decodeImage(photo) {
    if (typeof createImageBitmap === 'function') {
        let bitmap;

        try {
            bitmap = await createImageBitmap(photo, { imageOrientation: 'from-image' });
        } catch {
            bitmap = await createImageBitmap(photo);
        }

        return {
            source: bitmap,
            width: bitmap.width,
            height: bitmap.height,
            release: () => bitmap.close(),
        };
    }

    const url = URL.createObjectURL(photo);
    const image = new Image();

    try {
        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = reject;
            image.src = url;
        });

        return {
            source: image,
            width: image.naturalWidth,
            height: image.naturalHeight,
            release: () => URL.revokeObjectURL(url),
        };
    } catch (error) {
        URL.revokeObjectURL(url);
        throw error;
    }
}

function canvasToBlob(canvas, type, quality) {
    return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
}

function replaceInputFile(input, photo) {
    if (typeof DataTransfer !== 'function') {
        return false;
    }

    try {
        const transfer = new DataTransfer();
        transfer.items.add(photo);
        input.files = transfer.files;

        return input.files?.[0]?.name === photo.name
            && input.files[0].size === photo.size
            && input.files[0].type === photo.type;
    } catch {
        return false;
    }
}

function isSupportedImage(photo) {
    const extension = photo.name?.split('.').pop()?.toLowerCase() ?? '';

    return SUPPORTED_IMAGE_TYPES.has(photo.type) && SUPPORTED_IMAGE_EXTENSIONS.has(extension);
}

function clearInput(input) {
    if (input) {
        input.value = '';
    }
}
