

import Alpine from 'alpinejs';
import registerCombobox from './components/combobox';
import registerCustomerForm from './customers';
import registerProductForm from './products';
import registerSellerForm from './sellers';

window.Alpine = Alpine;

registerCombobox(Alpine);
registerCustomerForm(Alpine);
registerProductForm(Alpine);
registerSellerForm(Alpine);

Alpine.start();
