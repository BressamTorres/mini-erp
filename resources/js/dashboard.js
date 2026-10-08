function normalizeItems(items) {
    const normalizedItems = (items ?? []).map((item) => ({
        ...item,
        x: Number(item.x),
        y: Number(item.y),
    }));

    if (normalizedItems.length === 1) {
        normalizedItems[0].x = 300;
    }

    return normalizedItems;
}

function smoothPath(items) {
    if (items.length === 0) {
        return '';
    }

    if (items.length === 1) {
        return `M 16 ${items[0].y} L 584 ${items[0].y}`;
    }

    return items.slice(1).reduce((path, point, index) => {
        const previous = items[index];
        const controlDistance = (point.x - previous.x) * 0.4;

        return `${path} C ${previous.x + controlDistance} ${previous.y}, ${point.x - controlDistance} ${point.y}, ${point.x} ${point.y}`;
    }, `M ${items[0].x} ${items[0].y}`);
}

function formatDate(value) {
    if (!value) {
        return 'DD/MM/AAAA';
    }

    const [year, month, day] = value.split('-');

    return `${day}/${month}/${year}`;
}

export default function registerDashboardCharts(Alpine) {
    Alpine.data('dashboardPeriodFilter', (config) => ({
        startDate: config.startDate ?? '',
        endDate: config.endDate ?? '',
        draftStartDate: '',
        draftEndDate: '',
        open: false,
        validationMessage: '',

        get startDateLabel() {
            return formatDate(this.startDate);
        },

        get endDateLabel() {
            return formatDate(this.endDate);
        },

        get canApply() {
            const bothEmpty = !this.draftStartDate && !this.draftEndDate;
            const completeValidPeriod = this.draftStartDate
                && this.draftEndDate
                && this.draftStartDate <= this.draftEndDate;

            return bothEmpty || completeValidPeriod;
        },

        openPeriod() {
            this.draftStartDate = this.startDate;
            this.draftEndDate = this.endDate;
            this.validationMessage = '';
            this.open = true;

            this.$nextTick(() => this.$refs.startDate?.focus());
        },

        closePeriod(returnFocus = false) {
            if (!this.open) {
                return;
            }

            this.open = false;
            this.draftStartDate = this.startDate;
            this.draftEndDate = this.endDate;
            this.validationMessage = '';

            if (returnFocus) {
                this.$nextTick(() => this.$refs.trigger?.focus());
            }
        },

        validateDraft() {
            if (!this.draftStartDate && !this.draftEndDate) {
                this.validationMessage = '';

                return true;
            }

            if (!this.draftStartDate || !this.draftEndDate) {
                this.validationMessage = 'Selecione a data inicial e a data final.';

                return false;
            }

            if (this.draftStartDate > this.draftEndDate) {
                this.validationMessage = 'A data inicial deve ser anterior ou igual à data final.';

                return false;
            }

            this.validationMessage = '';

            return true;
        },

        clearDraft() {
            this.draftStartDate = '';
            this.draftEndDate = '';
            this.validationMessage = '';
        },

        applyPeriod() {
            if (!this.validateDraft()) {
                return;
            }

            this.startDate = this.draftStartDate;
            this.endDate = this.draftEndDate;
            this.open = false;

            this.submitPeriod();
        },

        submitPeriod() {
            this.$nextTick(() => this.$refs.periodForm.submit());
        },
    }));

    Alpine.data('dashboardLineChart', (chartItems) => ({
        items: normalizeItems(chartItems),
        activeIndex: null,

        get linePath() {
            return smoothPath(this.items);
        },

        get areaPath() {
            if (this.items.length === 0) {
                return '';
            }

            const firstX = this.items.length === 1 ? 16 : this.items[0].x;
            const lastX = this.items.length === 1 ? 584 : this.items[this.items.length - 1].x;

            return `${this.linePath} L ${lastX} 160 L ${firstX} 160 Z`;
        },

        get activeItem() {
            return this.activeIndex === null ? null : this.items[this.activeIndex];
        },

        get tooltipTransform() {
            if (!this.activeItem) {
                return '';
            }

            const x = Math.min(Math.max(this.activeItem.x - 71, 4), 454);
            const y = this.activeItem.y > 68 ? this.activeItem.y - 58 : this.activeItem.y + 14;

            return `translate(${x} ${y})`;
        },

        showTooltip(index) {
            this.activeIndex = index;
        },

        hideTooltip() {
            this.activeIndex = null;
        },
    }));
}
