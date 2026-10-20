import { submissionState } from './components/submission';
import { documentValidationMessage, formatDocument } from './components/person-document';
import { formatPhone } from './components/phone';
import { IMAGE_PROCESSING_MESSAGE, preparePhotoInput } from './components/photo-upload';

export default function registerCustomerForm(Alpine) {
    Alpine.data('customerForm', (config) => ({
        ...submissionState(config.submissionToken),
        modalOpen: config.open,
        mode: config.mode,
        formAction: config.formAction,
        storeUrl: config.storeUrl,
        customerId: config.customerId,
        fields: config.fields,
        uploadMaxBytes: config.uploadMaxBytes ?? null,
        documentError: '',
        showServerErrors: config.open,
        photoPreview: config.photoUrl ?? '',
        existingPhotoUrl: config.photoUrl ?? '',
        photoError: '',
        photoProcessing: false,
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
            this.resetSubmission();
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.customerId = '';
            this.fields = { name: '', document: '', phone: '', email: '', active: '1' };
            this.documentError = '';
            this.resetPhoto();
            this.showServerErrors = false;
            this.modalOpen = true;
            this.focusName();
        },

        openEdit(button) {
            this.resetSubmission();
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
            this.documentError = '';
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
            this.$nextTick(() => this.$refs.customerName?.focus());
        },

        async selectPhoto(event) {
            this.photoError = '';
            this.photoProcessing = true;
            const { photo, error } = await preparePhotoInput(event.target, { maxBytes: this.uploadMaxBytes });
            this.photoProcessing = false;

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
            this.photoProcessing = false;
            this.shouldRemovePhoto = this.existingPhotoUrl !== '';

            if (this.$refs.customerPhoto) {
                this.$refs.customerPhoto.value = '';
            }
        },

        resetPhoto(photoUrl = '') {
            this.releasePhotoPreview();
            this.existingPhotoUrl = photoUrl ?? '';
            this.photoPreview = this.existingPhotoUrl;
            this.photoError = '';
            this.photoProcessing = false;
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
            this.documentError = '';
        },

        validateDocument() {
            this.documentError = documentValidationMessage(this.fields.document);

            return this.documentError === '';
        },

        submitForm(event) {
            if (this.photoProcessing) {
                event.preventDefault();
                this.photoError = IMAGE_PROCESSING_MESSAGE;

                return;
            }

            if (!this.validateDocument() || this.photoError !== '') {
                event.preventDefault();

                if (this.documentError !== '') {
                    this.$nextTick(() => this.$refs.customerDocument?.focus());
                }

                return;
            }

            this.guardSubmit(event);
        },

        maskPhone(event) {
            const value = formatPhone(event.target.value);

            event.target.value = value;
            this.fields.phone = value;
        },
    }));
}
