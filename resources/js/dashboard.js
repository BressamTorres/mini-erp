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

        get startDateLabel() {
            return formatDate(this.startDate);
        },

        get endDateLabel() {
            return formatDate(this.endDate);
        },

        updateStartDate(value) {
            this.startDate = value;

            if (this.startDate && this.endDate && this.startDate > this.endDate) {
                this.endDate = this.startDate;
            }

            this.submitPeriod();
        },

        updateEndDate(value) {
            this.endDate = value;

            if (this.startDate && this.endDate && this.endDate < this.startDate) {
                this.startDate = this.endDate;
            }

            this.submitPeriod();
        },

        submitPeriod() {
            this.$nextTick(() => this.$refs.periodForm.requestSubmit());
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
