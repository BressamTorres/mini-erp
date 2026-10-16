export default function registerCatalogSharing(Alpine) {
    Alpine.data('catalogSharing', (catalogUrl) => ({
        catalogUrl,
        copied: false,
        copyFailed: false,

        async copyLink() {
            this.copied = false;
            this.copyFailed = false;

            try {
                await copyText(this.catalogUrl);
                this.copied = true;
                window.setTimeout(() => {
                    this.copied = false;
                }, 2500);
            } catch {
                this.copyFailed = true;
            }
        },
    }));
}

async function copyText(value) {
    if (navigator.clipboard?.writeText) {
        await navigator.clipboard.writeText(value);

        return;
    }

    const textArea = document.createElement('textarea');
    textArea.value = value;
    textArea.setAttribute('readonly', '');
    textArea.style.position = 'fixed';
    textArea.style.opacity = '0';
    document.body.appendChild(textArea);
    textArea.select();

    const copied = document.execCommand('copy');
    textArea.remove();

    if (! copied) {
        throw new Error('Clipboard unavailable');
    }
}
