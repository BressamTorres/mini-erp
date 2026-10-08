function normalizeOptions(options) {
    return (options ?? []).map((option) => ({
        ...option,
        value: String(option.value),
        disabled: option.disabled === true,
    }));
}

export default function registerCombobox(Alpine) {
    Alpine.data('selectCombobox', (config) => ({
        open: false,
        value: String(config.value ?? ''),
        options: normalizeOptions(config.options),
        activeIndex: 0,
        placement: 'bottom',
        positioned: false,
        repositionHandler: null,

        init() {
            this.repositionHandler = () => {
                if (this.open) {
                    this.positionMenu();
                }
            };

            window.addEventListener('resize', this.repositionHandler);
            window.addEventListener('scroll', this.repositionHandler, true);
        },

        destroy() {
            window.removeEventListener('resize', this.repositionHandler);
            window.removeEventListener('scroll', this.repositionHandler, true);
        },

        get selectedOption() {
            return this.options.find((option) => String(option.value) === this.value);
        },

        get selectedLabel() {
            return this.selectedOption?.label ?? config.placeholder ?? 'Selecione';
        },

        setValue(value) {
            const nextValue = String(value ?? '');

            if (this.value !== nextValue) {
                this.value = nextValue;
            }
        },

        setOptions(options) {
            const normalizedOptions = normalizeOptions(options);

            if (JSON.stringify(this.options) === JSON.stringify(normalizedOptions)) {
                return;
            }

            this.options = normalizedOptions;

            if (this.activeIndex >= this.options.length) {
                this.activeIndex = 0;
            }
        },

        toggle() {
            if (this.open) {
                this.close();

                return;
            }

            this.openMenu();
        },

        openMenu() {
            const selectedIndex = this.options.findIndex(
                (option) => String(option.value) === this.value,
            );

            this.activeIndex = selectedIndex >= 0 ? selectedIndex : 0;
            this.positioned = false;
            this.open = true;
            this.positionMenu(true);
        },

        close(returnFocus = false) {
            this.open = false;
            this.positioned = false;

            if (returnFocus) {
                this.$nextTick(() => this.$refs.trigger?.focus());
            }
        },

        select(option) {
            if (!option || option.disabled) {
                return;
            }

            this.value = String(option.value);
            this.$dispatch('combobox-change', { value: this.value });
            this.close(true);
        },

        move(step) {
            const total = this.options.length;

            if (total === 0) {
                return;
            }

            let nextIndex = this.activeIndex;

            do {
                nextIndex = (nextIndex + step + total) % total;
            } while (this.options[nextIndex]?.disabled && nextIndex !== this.activeIndex);

            this.activeIndex = nextIndex;
            this.focusOption();
        },

        focusOption() {
            this.$nextTick(() => {
                const options = this.$root.querySelectorAll('[data-combobox-option]');

                options[this.activeIndex]?.focus();
            });
        },

        positionMenu(focusAfterPositioning = false) {
            this.$nextTick(() => {
                const trigger = this.$refs.trigger;
                const menu = this.$refs.menu;

                if (!trigger || !menu || !this.open) {
                    return;
                }

                const triggerRect = trigger.getBoundingClientRect();
                const boundary = this.clippingBoundary();
                const menuHeight = menu.scrollHeight;
                const gap = 6;
                const spaceBelow = boundary.bottom - triggerRect.bottom - gap;
                const spaceAbove = triggerRect.top - boundary.top - gap;

                this.placement = spaceBelow >= menuHeight || spaceBelow >= spaceAbove
                    ? 'bottom'
                    : 'top';
                this.positioned = true;

                if (focusAfterPositioning) {
                    this.focusOption();
                }
            });
        },

        clippingBoundary() {
            const viewportPadding = 8;
            let ancestor = this.$root.parentElement;

            while (ancestor && ancestor !== document.body) {
                const style = window.getComputedStyle(ancestor);
                const overflow = `${style.overflow} ${style.overflowY} ${style.overflowX}`;

                if (/(auto|scroll|hidden|clip)/.test(overflow)) {
                    const rect = ancestor.getBoundingClientRect();

                    return {
                        top: Math.max(viewportPadding, rect.top + viewportPadding),
                        bottom: Math.min(window.innerHeight - viewportPadding, rect.bottom - viewportPadding),
                    };
                }

                ancestor = ancestor.parentElement;
            }

            return {
                top: viewportPadding,
                bottom: window.innerHeight - viewportPadding,
            };
        },
    }));
}
