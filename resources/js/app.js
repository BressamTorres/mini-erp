

import Alpine from 'alpinejs';
import registerCustomerForm from './customers';

window.Alpine = Alpine;

registerCustomerForm(Alpine);

Alpine.start();
