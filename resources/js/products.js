import { submissionState } from './components/submission';
import { validatePhotoInput } from './components/photo-upload';

function formatSalePrice(value) {
    const digits = String(value ?? '').replace(/\D/g, '');

    if (digits === '') {
        return '';
    }

    const normalized = digits.replace(/^0+(?=\d)/, '').padStart(3, '0');
    const integer = normalized.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    const cents = normalized.slice(-2);

    return `R$ ${integer},${cents}`;
}

export default function registerProductForm(Alpine) {
    Alpine.data('productForm', (config) => ({
        ...submissionState(config.submissionToken),
        modalOpen: config.open,
        mode: config.mode,
        formAction: config.formAction,
        storeUrl: config.storeUrl,
        productId: config.productId,
        fields: config.fields,
        showServerErrors: config.open,
        photoPreview: config.photoUrl ?? '',
        existingPhotoUrl: config.photoUrl ?? '',
        photoError: '',
        shouldRemovePhoto: config.removePhoto ?? false,
        deactivateOpen: false,
        deactivateAction: '',
        deactivateName: '',

        init() {
            this.fields.sale_price = formatSalePrice(this.fields.sale_price);
            this.$watch('modalOpen', () => this.syncBodyScroll());
            this.$watch('deactivateOpen', () => this.syncBodyScroll());

            if (this.modalOpen) {
                this.focusName();
            }

            this.syncBodyScroll();
        },

        openCreate() {
            this.resetSubmission();
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.productId = '';
            this.fields = { sku: '', name: '', sale_price: '', active: '1' };
            this.resetPhoto();
            this.showServerErrors = false;
            this.modalOpen = true;
            this.focusName();
        },

        openEdit(button) {
            this.resetSubmission();
            this.mode = 'edit';
            this.formAction = button.dataset.action;
            this.productId = button.dataset.id;
            this.fields = {
                sku: button.dataset.sku,
                name: button.dataset.name,
                sale_price: formatSalePrice(button.dataset.salePrice),
                active: button.dataset.active,
            };
            this.resetPhoto(button.dataset.photoUrl);
            this.showServerErrors = false;
            this.modalOpen = true;
            this.focusName();
        },

        closeModal() {
            if (this.submitting) {
                return;
            }

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
            this.$nextTick(() => this.$refs.productName?.focus());
        },

        selectPhoto(event) {
            const { photo, error } = validatePhotoInput(event.target);

            this.photoError = error;

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
            this.photoError = '';
            this.shouldRemovePhoto = this.existingPhotoUrl !== '';

            if (this.$refs.productPhoto) {
                this.$refs.productPhoto.value = '';
            }
        },

        resetPhoto(photoUrl = '') {
            this.releasePhotoPreview();
            this.existingPhotoUrl = photoUrl ?? '';
            this.photoPreview = this.existingPhotoUrl;
            this.photoError = '';
            this.shouldRemovePhoto = false;

            this.$nextTick(() => {
                if (this.$refs.productPhoto) {
                    this.$refs.productPhoto.value = '';
                }
            });
        },

        releasePhotoPreview() {
            if (this.photoPreview.startsWith('blob:')) {
                URL.revokeObjectURL(this.photoPreview);
            }
        },

        submitForm(event) {
            if (this.photoError !== '') {
                event.preventDefault();

                return;
            }

            this.guardSubmit(event);
        },

        maskSalePrice(event) {
            const value = formatSalePrice(event.target.value);

            event.target.value = value;
            this.fields.sale_price = value;
        },
    }));
}
