import { submissionState } from './components/submission';

function decimalToCents(value) {
    const [integer = '0', fraction = ''] = String(value ?? '0').split('.');

    return (Number.parseInt(integer, 10) * 100)
        + Number.parseInt(fraction.padEnd(2, '0').slice(0, 2), 10);
}

function formatBRL(cents) {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
}

export default function registerSalesPage(Alpine) {
    Alpine.data('salesPage', (config) => ({
        ...submissionState(config.form.submissionToken),
        detailsOpen: false,
        detailsTrigger: null,
        detailsOpenedByKeyboard: false,
        formOpen: config.form.open,
        loading: false,
        formLoading: false,
        error: '',
        formError: '',
        sale: null,
        mode: config.form.mode,
        formAction: config.form.action,
        storeUrl: config.storeUrl,
        saleId: config.form.saleId,
        fields: config.form.fields,
        items: [],
        selectedProductId: '',
        newQuantity: 1,
        showServerErrors: config.form.open,
        baseCustomers: config.customers,
        baseSellers: config.sellers,
        baseProducts: config.products,
        customerOptions: [...config.customers],
        sellerOptions: [...config.sellers],
        productOptions: [...config.products],
        historicalItems: new Map(),
        editRequestVersion: 0,
        deactivateOpen: false,
        deactivateAction: '',
        deactivateName: '',

        init() {
            this.resetOptions();

            if (config.form.historicalSale) {
                this.loadSaleIntoForm(config.form.historicalSale, config.form.items);
                this.fields = config.form.fields;
            } else {
                this.hydrateItems(config.form.items);
            }

            this.$watch('detailsOpen', () => this.syncBodyScroll());
            this.$watch('formOpen', () => this.syncBodyScroll());
            this.$watch('deactivateOpen', () => this.syncBodyScroll());
            this.syncBodyScroll();
        },

        get visualTotal() {
            return this.items.reduce(
                (total, item) => total + (item.unitPriceCents * Number(item.quantity || 0)),
                0,
            );
        },

        get canSubmit() {
            const hasEditTarget = this.mode !== 'edit' || this.saleId !== '';

            return hasEditTarget
                && this.items.length > 0
                && !this.formLoading
                && !this.formError
                && !this.submitting;
        },

        get customerComboboxOptions() {
            return this.customerOptions.map((customer) => ({
                value: String(customer.id),
                label: customer.active ? customer.name : `${customer.name} (inativo)`,
                disabled: !customer.active,
            }));
        },

        get sellerComboboxOptions() {
            return this.sellerOptions.map((seller) => ({
                value: String(seller.id),
                label: seller.active ? seller.name : `${seller.name} (inativo)`,
                disabled: !seller.active,
            }));
        },

        get productComboboxOptions() {
            return this.productOptions
                .filter((product) => product.active)
                .map((product) => ({
                    value: String(product.id),
                    label: `${product.sku} - ${product.name}`,
                }));
        },

        get statusComboboxOptions() {
            return [
                { value: '1', label: 'Ativo' },
                { value: '0', label: 'Inativo' },
            ];
        },

        async fetchSale(url) {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('Não foi possível carregar os dados da venda.');
            }

            return response.json();
        },

        async openDetails(url, trigger = null, openedByKeyboard = false) {
            this.detailsTrigger = trigger;
            this.detailsOpenedByKeyboard = openedByKeyboard;
            this.detailsOpen = true;
            this.loading = true;
            this.error = '';
            this.sale = null;
            this.$nextTick(() => this.$refs.detailsClose?.focus({ preventScroll: true }));

            try {
                this.sale = await this.fetchSale(url);
            } catch (error) {
                this.error = error.message || 'Não foi possível carregar os detalhes da venda.';
            } finally {
                this.loading = false;
            }
        },

        openCreate() {
            this.resetSubmission();
            this.editRequestVersion += 1;
            this.mode = 'create';
            this.formAction = this.storeUrl;
            this.saleId = '';
            this.fields = { customer_id: '', seller_id: '', active: '1' };
            this.items = [];
            this.historicalItems = new Map();
            this.selectedProductId = '';
            this.newQuantity = 1;
            this.formError = '';
            this.formLoading = false;
            this.showServerErrors = false;
            this.resetOptions();
            this.formOpen = true;
        },

        async openEdit(detailsUrl, updateUrl) {
            this.resetSubmission();
            const requestVersion = ++this.editRequestVersion;

            this.mode = 'edit';
            this.formAction = updateUrl;
            this.saleId = '';
            this.fields = { customer_id: '', seller_id: '', active: '1' };
            this.items = [];
            this.historicalItems = new Map();
            this.selectedProductId = '';
            this.newQuantity = 1;
            this.formError = '';
            this.showServerErrors = false;
            this.formLoading = true;
            this.resetOptions();
            this.formOpen = true;

            try {
                const sale = await this.fetchSale(detailsUrl);

                if (requestVersion !== this.editRequestVersion) {
                    return;
                }

                this.saleId = String(sale.id);
                this.loadSaleIntoForm(sale, sale.items);
            } catch (error) {
                if (requestVersion !== this.editRequestVersion) {
                    return;
                }

                this.formError = error.message || 'Não foi possível carregar os dados da venda.';
            } finally {
                if (requestVersion === this.editRequestVersion) {
                    this.formLoading = false;
                }
            }
        },

        loadSaleIntoForm(sale, rawItems) {
            this.resetOptions();
            this.addOption(this.customerOptions, sale.customer);
            this.addOption(this.sellerOptions, sale.seller);
            sale.items.forEach((item) => {
                const option = {
                    id: item.product_id,
                    sku: item.sku,
                    name: item.product,
                    sale_price: item.unit_price_value,
                    photo_url: item.photo_url,
                    active: item.product_active,
                };
                if (option.active) {
                    this.addOption(this.productOptions, option);
                }
                this.historicalItems.set(String(item.sale_item_id), item);
            });
            this.fields = {
                customer_id: String(sale.customer.id),
                seller_id: String(sale.seller.id),
                active: sale.active ? '1' : '0',
            };
            this.hydrateItems(rawItems);
        },

        hydrateItems(rawItems) {
            this.items = (rawItems ?? []).map((rawItem) => {
                const productId = String(rawItem.product_id);
                const saleItemId = String(rawItem.sale_item_id ?? '');
                const historical = saleItemId !== ''
                    ? this.historicalItems.get(saleItemId)
                    : null;
                const product = this.productOptions.find((option) => String(option.id) === productId);
                const price = historical?.unit_price_value ?? product?.sale_price ?? '0.00';

                return {
                    saleItemId,
                    productId,
                    sku: historical?.sku ?? product?.sku ?? '',
                    name: historical?.product ?? product?.name ?? 'Produto indisponível',
                    photoUrl: historical?.photo_url ?? product?.photo_url ?? '',
                    quantity: Number(rawItem.quantity || 1),
                    unitPriceCents: decimalToCents(price),
                };
            });
        },

        addItem() {
            const product = this.productOptions.find(
                (option) => String(option.id) === String(this.selectedProductId),
            );
            const quantity = Math.max(1, Number.parseInt(this.newQuantity, 10) || 1);

            if (!product) {
                return;
            }

            const existing = this.items.find(
                (item) => item.productId === String(product.id),
            );

            if (existing) {
                existing.quantity += quantity;
            } else {
                this.items.push({
                    saleItemId: '',
                    productId: String(product.id),
                    sku: product.sku,
                    name: product.name,
                    photoUrl: product.photo_url ?? '',
                    quantity,
                    unitPriceCents: decimalToCents(product.sale_price),
                });
            }

            this.selectedProductId = '';
            this.newQuantity = 1;
        },

        removeItem(index) {
            this.items.splice(index, 1);
        },

        normalizeQuantity(item) {
            item.quantity = Math.max(1, Number.parseInt(item.quantity, 10) || 1);
        },

        itemSubtotal(item) {
            return formatBRL(item.unitPriceCents * Number(item.quantity || 0));
        },

        money(cents) {
            return formatBRL(cents);
        },

        resetOptions() {
            this.customerOptions = [...this.baseCustomers];
            this.sellerOptions = [...this.baseSellers];
            this.productOptions = [...this.baseProducts];
            this.historicalItems = new Map();
        },

        addOption(options, option) {
            if (!options.some((current) => String(current.id) === String(option.id))) {
                options.push(option);
            }
        },

        closeDetails() {
            const trigger = this.detailsTrigger;
            const restoreKeyboardFocus = this.detailsOpenedByKeyboard;

            this.detailsOpen = false;
            this.$refs.detailsClose?.blur();

            this.$nextTick(() => {
                if (restoreKeyboardFocus && trigger?.isConnected) {
                    trigger.focus({ preventScroll: true });
                } else {
                    trigger?.blur();
                }
            });

            this.detailsTrigger = null;
            this.detailsOpenedByKeyboard = false;
        },

        closeForm() {
            if (this.submitting) {
                return;
            }

            this.editRequestVersion += 1;
            this.formOpen = false;
            this.formLoading = false;
            this.showServerErrors = false;
            window.dispatchEvent(new CustomEvent('close-comboboxes'));
        },

        guardSaleSubmit(event) {
            if (!this.canSubmit) {
                event.preventDefault();

                return;
            }

            this.guardSubmit(event);
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
            document.body.classList.toggle('overflow-hidden', this.detailsOpen || this.formOpen || this.deactivateOpen);
        },
    }));
}
