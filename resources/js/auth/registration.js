import { documentValidationMessage, formatDocument } from '../components/person-document.js';
import { formatPhone } from '../components/phone.js';

export default function registerRegistrationForm(Alpine) {
    Alpine.data('registrationForm', (config) => ({
        step: config.step,
        showPassword: false,
        showPasswordConfirmation: false,
        businessDocument: formatDocument(config.businessDocument),
        businessPhone: formatPhone(config.businessPhone),
        documentError: '',
        submitting: false,

        maskDocument(event) {
            const value = formatDocument(event.target.value);

            event.target.value = value;
            this.businessDocument = value;
            this.documentError = '';
        },

        validateDocument() {
            this.documentError = documentValidationMessage(this.businessDocument);

            return this.documentError === '';
        },

        maskPhone(event) {
            const value = formatPhone(event.target.value);

            event.target.value = value;
            this.businessPhone = value;
        },

        submitForm(event) {
            if (this.submitting) {
                event.preventDefault();

                return;
            }

            if (!this.validateDocument()) {
                event.preventDefault();
                this.$nextTick(() => this.$refs.businessDocument?.focus());

                return;
            }

            this.submitting = true;
        },
    }));
}
