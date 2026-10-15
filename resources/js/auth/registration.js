import { documentValidationMessage, formatDocument } from '../components/person-document';
import { formatPhone } from '../components/phone';

export default function registerRegistrationForm(Alpine) {
    Alpine.data('registrationForm', (config) => ({
        step: config.step,
        showPassword: false,
        showPasswordConfirmation: false,
        businessDocument: formatDocument(config.businessDocument),
        businessPhone: formatPhone(config.businessPhone),
        documentError: '',

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
            if (this.validateDocument()) {
                return;
            }

            event.preventDefault();
            this.$nextTick(() => this.$refs.businessDocument?.focus());
        },
    }));
}
