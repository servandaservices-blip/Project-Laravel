<?php

namespace App\Support;

use Illuminate\Support\Collection;

class DashboardFilterOptions
{
    public static function normalize(iterable $values, bool $prependEmptyOption = false, string $emptyValue = '__EMPTY__'): Collection
    {
        $items = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value) && strtolower($value) !== '-')
            ->unique(fn ($value) => strtolower($value))
            ->sort(fn ($left, $right) => strcasecmp($left, $right))
            ->values();

        if (! $prependEmptyOption) {
            return $items;
        }

        return collect([$emptyValue])
            ->merge($items->reject(fn ($value) => $value === $emptyValue))
            ->values();
    }

    public static function display(mixed $value, string $emptyLabel = 'Tanpa Data'): string
    {
        $normalized = trim((string) $value);

        return $normalized === '' || $normalized === '__EMPTY__' || $normalized === '-'
            ? $emptyLabel
            : $normalized;
    }

    public static function summary(array $selectedValues, string $fallback = 'Semua', string $emptyValue = '__EMPTY__', string $emptyLabel = 'Tanpa Data'): string
    {
        $selectedValues = array_values(array_filter($selectedValues, fn ($value) => filled($value)));

        if ($selectedValues === []) {
            return $fallback;
        }

        $displayValues = array_map(
            fn ($value) => static::display($value, $emptyLabel),
            $selectedValues
        );

        if (count($displayValues) <= 2) {
            return implode(', ', $displayValues);
        }

        return $displayValues[0] . ', ' . $displayValues[1] . ' +' . (count($displayValues) - 2);
    }
}
