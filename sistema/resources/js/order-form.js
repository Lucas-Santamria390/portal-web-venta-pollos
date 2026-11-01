export function registerOrderForm(Alpine) {
    Alpine.data('orderForm', (config) => ({
        mode: config.mode,
        customers: config.customers,
        csrf: config.csrf,
        autoPrice: Boolean(config.autoPrice),
        pricePerLb: config.initialPricePerLb ?? config.pricePerLb ?? 0,
        quantity: Number(config.initialQuantity) || 1,
        weights: [],
        prices: [],
        customerId: config.initialCustomerId ? String(config.initialCustomerId) : '',
        deliveryDate: config.initialDeliveryDate ?? '',
        note: config.initialNote ?? '',
        paidAmount: config.initialPaidAmount ?? '',
        paidTouched: false,
        method: config.initialMethod ?? 'efectivo',
        quickCreate: false,
        newCustomer: { name: '', phone: '' },
        savingCustomer: false,
        customerError: '',

        init() {
            this.quantity = Math.max(1, Math.min(100, this.quantity));
            this.weights = Array.from({ length: this.quantity }, (_, index) => config.initialWeights?.[index] ?? '');
            this.prices = Array.from({ length: this.quantity }, (_, index) => config.initialPrices?.[index] ?? '');
            this.fillWeightsFromPrices();
            this.syncPaid();
        },

        get derivesWeightFromPrice() {
            return ! this.autoPrice && Number(this.pricePerLb) > 0;
        },

        syncWeightFromPrice(index) {
            if (! this.derivesWeightFromPrice) {
                return;
            }

            const price = Number(this.prices[index]) || 0;

            if (price <= 0) {
                return;
            }

            this.weights[index] = (price / Number(this.pricePerLb)).toFixed(2);
        },

        fillWeightsFromPrices() {
            if (! this.derivesWeightFromPrice) {
                return;
            }

            this.prices.forEach((_, index) => {
                if (! this.weights[index]) {
                    this.syncWeightFromPrice(index);
                }
            });
        },

        resizeRows() {
            const quantity = Math.max(1, Math.min(100, Number(this.quantity) || 1));

            this.quantity = quantity;
            this.weights = Array.from({ length: quantity }, (_, index) => this.weights[index] ?? '');
            this.prices = Array.from({ length: quantity }, (_, index) => this.prices[index] ?? '');
            this.syncPaid();
        },

        chickenPrice(index) {
            if (this.autoPrice) {
                return (Number(this.weights[index]) || 0) * (Number(this.pricePerLb) || 0);
            }

            return Number(this.prices[index]) || 0;
        },

        get total() {
            return Array.from({ length: this.quantity }, (_, index) => this.chickenPrice(index))
                .reduce((sum, value) => sum + value, 0);
        },

        get paid() {
            return Number(this.paidAmount) || 0;
        },

        get balance() {
            return Math.max(this.total - this.paid, 0);
        },

        get isFiado() {
            return this.total > 0 && this.paid < this.total;
        },

        syncPaid() {
            if (this.mode === 'sell' && ! this.paidTouched) {
                this.paidAmount = this.total > 0 ? this.total.toFixed(2) : '';
            }
        },

        money(value) {
            return '$ ' + (Number(value) || 0).toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },

        async createCustomer() {
            this.savingCustomer = true;
            this.customerError = '';

            try {
                const response = await fetch('/clientes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': this.csrf,
                    },
                    body: JSON.stringify(this.newCustomer),
                });

                const data = await response.json();

                if (! response.ok) {
                    this.customerError = data.errors?.name?.[0] ?? 'No se pudo crear el cliente.';
                    return;
                }

                this.customers.push(data);
                this.customerId = String(data.id);
                this.quickCreate = false;
                this.newCustomer = { name: '', phone: '' };
            } catch (error) {
                this.customerError = 'No se pudo crear el cliente.';
            } finally {
                this.savingCustomer = false;
            }
        },
    }));

    Alpine.data('deliveryForm', (config) => ({
        autoPrice: Boolean(config.autoPrice),
        pricePerLb: config.pricePerLb ?? 0,
        weights: config.weights ?? [],
        prices: config.prices ?? [],

        init() {
            this.fillWeightsFromPrices();
        },

        get derivesWeightFromPrice() {
            return ! this.autoPrice && Number(this.pricePerLb) > 0;
        },

        syncWeightFromPrice(index) {
            if (! this.derivesWeightFromPrice) {
                return;
            }

            const price = Number(this.prices[index]) || 0;

            if (price <= 0) {
                return;
            }

            this.weights[index] = (price / Number(this.pricePerLb)).toFixed(2);
        },

        fillWeightsFromPrices() {
            if (! this.derivesWeightFromPrice) {
                return;
            }

            this.prices.forEach((_, index) => {
                if (! this.weights[index]) {
                    this.syncWeightFromPrice(index);
                }
            });
        },

        chickenPrice(index) {
            if (this.autoPrice) {
                return (Number(this.weights[index]) || 0) * (Number(this.pricePerLb) || 0);
            }

            return Number(this.prices[index]) || 0;
        },

        get total() {
            return this.weights.reduce((sum, value, index) => sum + this.chickenPrice(index), 0);
        },

        money(value) {
            return '$ ' + (Number(value) || 0).toLocaleString('es-AR', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            });
        },
    }));
}
