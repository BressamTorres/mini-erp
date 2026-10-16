

import Alpine from 'alpinejs';
import registerRegistrationForm from './auth/registration';
import registerBusinessSettings from './business-settings';
import registerCatalogSharing from './catalog-sharing';
import registerCombobox from './components/combobox';
import registerCustomerForm from './customers';
import registerDashboardCharts from './dashboard';
import registerProductForm from './products';
import registerSalesPage from './sales';
import registerSellerForm from './sellers';

window.Alpine = Alpine;

registerRegistrationForm(Alpine);
registerBusinessSettings(Alpine);
registerCatalogSharing(Alpine);
registerCombobox(Alpine);
registerCustomerForm(Alpine);
registerDashboardCharts(Alpine);
registerProductForm(Alpine);
registerSalesPage(Alpine);
registerSellerForm(Alpine);

Alpine.start();
