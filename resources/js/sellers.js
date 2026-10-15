import { submissionState } from './components/submission';
import { documentValidationMessage, formatDocument } from './components/person-document';
import { formatPhone } from './components/phone';

export default function registerSellerForm(Alpine) {
    Alpine.data('sellerForm', (config) => ({
        ...submissionState(config.submissionToken),
        modalOpen: config.open,
        mode: config.mode,
        formAction: config.formAction,
        storeUrl: config.storeUrl,
        sellerId: config.sellerId,
        fields: config.fields,
        documentError: '',
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
            this.resetSubmission();
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.sellerId = '';
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
            this.sellerId = button.dataset.id;
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
            this.$nextTick(() => this.$refs.sellerName?.focus());
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

            if (this.$refs.sellerPhoto) {
                this.$refs.sellerPhoto.value = '';
            }
        },

        resetPhoto(photoUrl = '') {
            this.releasePhotoPreview();
            this.existingPhotoUrl = photoUrl ?? '';
            this.photoPreview = this.existingPhotoUrl;
            this.shouldRemovePhoto = false;

            this.$nextTick(() => {
                if (this.$refs.sellerPhoto) {
                    this.$refs.sellerPhoto.value = '';
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
            if (!this.validateDocument()) {
                event.preventDefault();
                this.$nextTick(() => this.$refs.sellerDocument?.focus());

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
