@props([
    'id',
    'name',
    'model',
    'options',
    'placeholder' => 'Selecione',
    'error' => false,
    'disabled' => 'false',
])

<div
    class="relative"
    x-data="selectCombobox({ value: {{ $model }}, options: {{ $options }}, placeholder: @js($placeholder) })"
    x-effect="setValue({{ $model }}); setOptions({{ $options }})"
    @combobox-change="{{ $model }} = $event.detail.value"
    @close-comboboxes.window="close()"
    @click.outside="close()"
>
    <input type="hidden" name="{{ $name }}" x-bind:value="value" x-bind:disabled="{{ $disabled }}">

    <button
        id="{{ $id }}"
        x-ref="trigger"
        type="button"
        role="combobox"
        aria-haspopup="listbox"
        aria-controls="{{ $id }}-options"
        x-bind:aria-expanded="open"
        x-bind:disabled="{{ $disabled }}"
        @click="toggle"
        @keydown.arrow-down.prevent="open ? move(1) : openMenu()"
        @keydown.arrow-up.prevent="open ? move(-1) : openMenu()"
        @keydown.enter.prevent="open ? select(options[activeIndex]) : openMenu()"
        @keydown.space.prevent="open ? select(options[activeIndex]) : openMenu()"
        @keydown.escape.stop.prevent="close(true)"
        @class([
            'flex w-full items-center justify-between rounded-lg border bg-white px-3.5 py-2.5 text-left text-sm shadow-sm focus:outline-none focus:ring-1 focus:ring-violet-500 disabled:cursor-not-allowed disabled:bg-slate-50 disabled:text-slate-400',
            'border-rose-300 focus:border-rose-400' => $error,
            'border-slate-300 focus:border-violet-500' => ! $error,
        ])
    >
        <span x-bind:class="value ? 'text-slate-700' : 'text-slate-400'" x-text="selectedLabel"></span>
        <svg class="h-4 w-4 shrink-0 text-slate-400 transition-transform duration-150" x-bind:class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div
        id="{{ $id }}-options"
        x-ref="menu"
        x-cloak
        x-show="open"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0 -translate-y-1"
        x-transition:enter-end="opacity-100 translate-y-0"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100 translate-y-0"
        x-transition:leave-end="opacity-0 -translate-y-1"
        role="listbox"
        x-bind:data-placement="placement"
        x-bind:class="{
            'top-full mt-1.5': placement === 'bottom',
            'bottom-full mb-1.5': placement === 'top',
            'invisible': ! positioned,
        }"
        class="absolute z-40 max-h-60 w-full overflow-y-auto rounded-lg border border-slate-200 bg-white p-1.5 shadow-lg shadow-slate-200/70"
    >
        <template x-for="(option, index) in options" x-bind:key="option.value">
            <button
                type="button"
                role="option"
                data-combobox-option
                x-bind:disabled="option.disabled"
                x-bind:aria-selected="value === String(option.value)"
                x-bind:tabindex="index === activeIndex ? 0 : -1"
                x-bind:class="{
                    'bg-violet-50 text-violet-700': value === String(option.value),
                    'text-slate-700 hover:bg-slate-50': value !== String(option.value) && ! option.disabled,
                    'cursor-not-allowed text-slate-400': option.disabled,
                }"
                class="flex w-full items-center justify-between rounded-md px-3 py-2.5 text-left text-sm font-medium transition"
                @click="select(option)"
                @mouseenter="if (! option.disabled) activeIndex = index"
                @focus="activeIndex = index"
                @keydown.arrow-down.prevent="move(1)"
                @keydown.arrow-up.prevent="move(-1)"
                @keydown.home.prevent="activeIndex = 0; focusOption()"
                @keydown.end.prevent="activeIndex = options.length - 1; focusOption()"
                @keydown.escape.stop.prevent="close(true)"
                @keydown.tab="close()"
            >
                <span x-text="option.label"></span>
                <svg x-show="value === String(option.value)" class="h-4 w-4 shrink-0 text-violet-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m5 12 4 4L19 6" />
                </svg>
            </button>
        </template>
    </div>
</div>
