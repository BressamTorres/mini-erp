import { documentValidationMessage, formatDocument } from './components/person-document';
import { formatPhone } from './components/phone';

export default function registerBusinessSettings(Alpine) {
    Alpine.data('businessSettings', (config) => ({
        document: formatDocument(config.document),
        phone: formatPhone(config.phone),
        documentError: '',

        maskDocument(event) {
            const value = formatDocument(event.target.value);

            event.target.value = value;
            this.document = value;
            this.documentError = '';
        },

        validateDocument() {
            this.documentError = documentValidationMessage(this.document);

            return this.documentError === '';
        },

        maskPhone(event) {
            const value = formatPhone(event.target.value);

            event.target.value = value;
            this.phone = value;
        },

        submit(event) {
            if (this.validateDocument()) {
                return;
            }

            event.preventDefault();
            this.$nextTick(() => this.$refs.document?.focus());
        },
    }));
}
