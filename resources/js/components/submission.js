function createSubmissionToken() {
    if (typeof window.crypto?.randomUUID === 'function') {
        return window.crypto.randomUUID();
    }

    const bytes = new Uint8Array(16);
    window.crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (byte) => byte.toString(16).padStart(2, '0')).join('');

    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
}

export function submissionState(initialToken = '') {
    return {
        submitting: false,
        submissionToken: initialToken || createSubmissionToken(),

        resetSubmission() {
            this.submitting = false;
            this.submissionToken = createSubmissionToken();
        },

        guardSubmit(event) {
            if (this.submitting) {
                event.preventDefault();

                return;
            }

            this.submitting = true;
        },
    };
}
