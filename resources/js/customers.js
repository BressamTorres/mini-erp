function onlyDigits(value, limit) {
    return String(value ?? '').replace(/\D/g, '').slice(0, limit);
}

function formatPhone(value) {
    const digits = onlyDigits(value, 11);

    if (digits.length <= 2) {
        return digits.length > 0 ? `(${digits}` : '';
    }

    const areaCode = digits.slice(0, 2);
    const subscriber = digits.slice(2);
    const prefixLength = digits.length === 11 ? 5 : 4;
    const prefix = subscriber.slice(0, prefixLength);
    const suffix = subscriber.slice(prefixLength);

    return `(${areaCode}) ${prefix}${suffix ? `-${suffix}` : ''}`;
}

function formatDocument(value) {
    const digits = onlyDigits(value, 14);

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

export default function registerCustomerForm(Alpine) {
    Alpine.data('customerForm', (config) => ({
        modalOpen: config.open,
        mode: config.mode,
        formAction: config.formAction,
        storeUrl: config.storeUrl,
        customerId: config.customerId,
        fields: config.fields,
        showServerErrors: config.open,
        photoPreview: config.photoUrl ?? '',
        existingPhotoUrl: config.photoUrl ?? '',
        shouldRemovePhoto: config.removePhoto ?? false,
        deactivateOpen: false,
        deactivateAction: '',
        deactivateName: '',

        init() {
            this.fields.document = formatDocument(this.fields.document);
            this.fields.phone = formatPhone(this.fields.phone);

            this.$watch('modalOpen', () => this.syncBodyScroll());
            this.$watch('deactivateOpen', () => this.syncBodyScroll());

            if (this.modalOpen) {
                this.focusName();
            }

            this.syncBodyScroll();
        },

        openCreate() {
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.customerId = '';
            this.fields = { name: '', document: '', phone: '', email: '', active: '1' };
            this.resetPhoto();
            this.showServerErrors = false;
            this.modalOpen = true;
            this.focusName();
        },

        openEdit(button) {
            this.mode = 'edit';
            this.formAction = button.dataset.action;
            this.customerId = button.dataset.id;
            this.fields = {
                name: button.dataset.name,
                document: formatDocument(button.dataset.document),
                phone: formatPhone(button.dataset.phone),
                email: button.dataset.email,
                active: button.dataset.active,
            };
            this.resetPhoto(button.dataset.photoUrl);
            this.showServerErrors = false;
            this.modalOpen = true;
            this.focusName();
        },

        closeModal() {
            this.modalOpen = false;
            this.showServerErrors = false;
        },

        openDeactivate(button) {
            this.deactivateAction = button.dataset.deactivateAction;
            this.deactivateName = button.dataset.deactivateName;
            this.deactivateOpen = true;
        },

        closeDeactivate() {
            this.deactivateOpen = false;
        },

        syncBodyScroll() {
            document.body.classList.toggle('overflow-hidden', this.modalOpen || this.deactivateOpen);
        },

        focusName() {
            this.$nextTick(() => this.$refs.customerName?.focus());
        },

        selectPhoto(event) {
            const [photo] = event.target.files;

            if (!photo) {
                return;
            }

            this.releasePhotoPreview();
            this.photoPreview = URL.createObjectURL(photo);
            this.shouldRemovePhoto = false;
        },

        removePhoto() {
            this.releasePhotoPreview();
            this.photoPreview = '';
            this.shouldRemovePhoto = this.existingPhotoUrl !== '';

            if (this.$refs.customerPhoto) {
                this.$refs.customerPhoto.value = '';
            }
        },

        resetPhoto(photoUrl = '') {
            this.releasePhotoPreview();
            this.existingPhotoUrl = photoUrl ?? '';
            this.photoPreview = this.existingPhotoUrl;
            this.shouldRemovePhoto = false;

            this.$nextTick(() => {
                if (this.$refs.customerPhoto) {
                    this.$refs.customerPhoto.value = '';
                }
            });
        },

        releasePhotoPreview() {
            if (this.photoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.photoPreview);
            }
        },

        maskDocument(event) {
            const value = formatDocument(event.target.value);

            event.target.value = value;
            this.fields.document = value;
        },

        maskPhone(event) {
            const value = formatPhone(event.target.value);

            event.target.value = value;
            this.fields.phone = value;
        },
    }));
}
