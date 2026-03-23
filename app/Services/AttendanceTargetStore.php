<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AttendanceTargetStore
{
    public const DEFAULT_TARGET = 90.00;

    private const TABLE = 'faizal.attendance_targets';
    private const SERVANDA_DIVISIONS = ['Cleaning', 'Security'];

    public function editableTargets(string $company, int $year, ?string $division = null, ?string $branch = null): Collection
    {
        $rows = $this->rowsForScope($company, $year, $division, $branch)
            ->keyBy(fn ($row) => (int) data_get($row, 'month'));

        return collect(range(1, 12))->map(function (int $month) use ($rows) {
            $row = $rows->get($month);

            return [
                'month' => $month,
                'label' => Carbon::create()->month($month)->translatedFormat('F'),
                'attendance_target' => number_format(
                    $this->normalizeTargetValue(data_get($row, 'attendance_target')) ?? self::DEFAULT_TARGET,
                    2,
                    '.',
                    ''
                ),
                'division_breakdown' => [],
            ];
        });
    }

    public function summaryTargets(string $company, int $year, ?string $branch = null): Collection
    {
        $rows = $this->all($company, $year);

        return collect(range(1, 12))->map(function (int $month) use ($rows, $company, $branch) {
            $breakdown = collect(self::SERVANDA_DIVISIONS)
                ->map(function (string $division) use ($rows, $company, $branch, $month) {
                    $monthEntries = $rows
                        ->where('month', $month)
                        ->values()
                        ->all();

                    return [
                        'division' => $division,
                        'attendance_target' => $this->resolveScopeTarget($monthEntries, $company, $division, $branch),
                    ];
                })
                ->values();

            $available = $breakdown
                ->pluck('attendance_target')
                ->filter(fn ($value) => $value !== null)
                ->values();

            return [
                'month' => $month,
                'label' => Carbon::create()->month($month)->translatedFormat('F'),
                'attendance_target' => number_format(
                    $available->isNotEmpty() ? round((float) $available->avg(), 2) : self::DEFAULT_TARGET,
                    2,
                    '.',
                    ''
                ),
                'division_breakdown' => $breakdown->all(),
            ];
        });
    }

    public function dashboardTargets(string $company, int $year, ?string $division = null, ?string $branch = null): Collection
    {
        if ($company === 'servanda' && $division === null) {
            return $this->summaryTargets($company, $year, $branch)
                ->mapWithKeys(fn (array $target) => [(int) $target['month'] => (float) $target['attendance_target']]);
        }

        $rows = $this->all($company, $year);

        return collect(range(1, 12))->mapWithKeys(function (int $month) use ($rows, $company, $division, $branch) {
            $monthEntries = $rows
                ->where('month', $month)
                ->values()
                ->all();

            $target = $this->resolveScopeTarget($monthEntries, $company, $division, $branch);

            return [$month => $target];
        });
    }

    public function save(string $company, int $year, ?string $division, ?string $branch, array $monthlyTargets): void
    {
        $normalizedDivision = $this->normalizeNullableString($division);
        $normalizedBranch = $this->normalizeNullableString($branch);
        $timestamp = now();

        foreach (range(1, 12) as $month) {
            DB::table(self::TABLE)->updateOrInsert(
                [
                    'company' => $company,
                    'division' => $normalizedDivision,
                    'branch' => $normalizedBranch,
                    'year' => $year,
                    'month' => $month,
                ],
                [
                    'attendance_target' => round((float) ($monthlyTargets[$month] ?? self::DEFAULT_TARGET), 2),
                    'updated_at' => $timestamp,
                    'created_at' => $timestamp,
                ]
            );
        }
    }

    public static function servandaDivisions(): array
    {
        return self::SERVANDA_DIVISIONS;
    }

    private function all(?string $company = null, ?int $year = null): Collection
    {
        try {
            return DB::table(self::TABLE)
                ->when($company !== null, fn ($query) => $query->where('company', $company))
                ->when($year !== null, fn ($query) => $query->where('year', $year))
                ->select([
                    'company',
                    'division',
                    'branch',
                    'year',
                    'month',
                    'attendance_target',
                ])
                ->orderBy('month')
                ->get()
                ->map(function ($entry) {
                    return [
                        'company' => trim((string) data_get($entry, 'company')),
                        'division' => $this->normalizeNullableString(data_get($entry, 'division')),
                        'branch' => $this->normalizeNullableString(data_get($entry, 'branch')),
                        'year' => (int) data_get($entry, 'year'),
                        'month' => (int) data_get($entry, 'month'),
                        'target' => $this->normalizeTargetValue(data_get($entry, 'attendance_target')),
                        'attendance_target' => $this->normalizeTargetValue(data_get($entry, 'attendance_target')),
                    ];
                })
                ->values();
        } catch (\Throwable $exception) {
            return collect();
        }
    }

    private function rowsForScope(string $company, int $year, ?string $division = null, ?string $branch = null): Collection
    {
        $normalizedDivision = $this->normalizeNullableString($division);
        $normalizedBranch = $this->normalizeNullableString($branch);

        return $this->all($company, $year)
            ->filter(function (array $row) use ($normalizedDivision, $normalizedBranch) {
                return $row['division'] === $normalizedDivision
                    && $row['branch'] === $normalizedBranch;
            })
            ->values();
    }

    private function resolveScopeTarget(array $entries, string $company, ?string $division = null, ?string $branch = null): float
    {
        $normalizedDivision = $this->normalizeNullableString($division);
        $normalizedBranch = $this->normalizeNullableString($branch);

        $match = collect($entries)
            ->map(function (array $entry) {
                return [
                    'division' => $this->normalizeNullableString(data_get($entry, 'division')),
                    'branch' => $this->normalizeNullableString(data_get($entry, 'branch')),
                    'target' => $this->normalizeTargetValue(data_get($entry, 'target')),
                ];
            })
            ->filter(function (array $entry) use ($company, $normalizedDivision, $normalizedBranch) {
                if ($company === 'servanda' && $entry['division'] === null) {
                    return false;
                }

                if ($entry['division'] !== null && $entry['division'] !== $normalizedDivision) {
                    return false;
                }

                if ($entry['branch'] !== null && $entry['branch'] !== $normalizedBranch) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(function (array $entry) {
                $score = 0;

                if ($entry['division'] !== null) {
                    $score += 2;
                }

                if ($entry['branch'] !== null) {
                    $score += 1;
                }

                return $score;
            })
            ->first();

        return $match['target'] ?? self::DEFAULT_TARGET;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeTargetValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = str_replace(['%', ' '], '', trim((string) $value));
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        $target = round((float) $normalized, 2);

        if ($target < 0 || $target > 100) {
            return null;
        }

        return $target;
    }

}
