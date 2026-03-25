@props([
    'label',
    'name',
    'options' => collect(),
    'selected' => null,
    'placeholder' => 'Semua',
    'locked' => false,
    'lockedValue' => null,
    'lockedLabel' => null,
    'submitOnChange' => true,
    'emptyLabel' => 'Tanpa Data',
    'fieldClass' => 'w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100',
    'staticClass' => 'flex min-h-[50px] items-center rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 text-sm font-semibold text-slate-700 shadow-sm',
    'labelClass' => 'mb-2 block text-sm font-medium text-slate-700',
])

@php
    $rawOptions = collect($options ?? []);
    $isListOptions = array_is_list($rawOptions->all());
    $selectedValue = is_array($selected ?? null) ? (string) data_get($selected, 0, '') : trim((string) ($selected ?? ''));
    $normalizedOptions = $rawOptions
        ->map(function ($option, $key) use ($emptyLabel, $isListOptions) {
            $value = $isListOptions ? $option : $key;
            $display = $option;
            $value = trim((string) $value);
            $display = trim((string) $display);

            return [
                'value' => $value,
                'label' => \App\Support\DashboardFilterOptions::display($display, $emptyLabel),
            ];
        })
        ->values();
@endphp

<div>
    <label class="{{ $labelClass }}">{{ $label }}</label>

    @if ($locked)
        @if (filled($name) && filled($lockedValue))
            <input type="hidden" name="{{ $name }}" value="{{ $lockedValue }}">
        @endif
        <div class="{{ $staticClass }}">
            {{ $lockedLabel ?? $lockedValue ?? $placeholder }}
        </div>
    @else
        <select
            name="{{ $name }}"
            @if ($submitOnChange) onchange="this.form.submit()" @endif
            class="{{ $fieldClass }}"
        >
            <option value="">{{ $placeholder }}</option>
            @foreach ($normalizedOptions as $option)
                <option value="{{ $option['value'] }}" @selected($selectedValue === (string) $option['value'])>
                    {{ $option['label'] }}
                </option>
            @endforeach
        </select>
    @endif
</div>
