

import Alpine from 'alpinejs';
import registerRegistrationForm from './auth/registration';
import registerCombobox from './components/combobox';
import registerCustomerForm from './customers';
import registerDashboardCharts from './dashboard';
import registerProductForm from './products';
import registerSalesPage from './sales';
import registerSellerForm from './sellers';

window.Alpine = Alpine;

registerRegistrationForm(Alpine);
registerCombobox(Alpine);
registerCustomerForm(Alpine);
registerDashboardCharts(Alpine);
registerProductForm(Alpine);
registerSalesPage(Alpine);
registerSellerForm(Alpine);

Alpine.start();
