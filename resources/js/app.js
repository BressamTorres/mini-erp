

import Alpine from 'alpinejs';
import registerCustomerForm from './customers';
import registerSellerForm from './sellers';

window.Alpine = Alpine;

registerCustomerForm(Alpine);
registerSellerForm(Alpine);

Alpine.start();
