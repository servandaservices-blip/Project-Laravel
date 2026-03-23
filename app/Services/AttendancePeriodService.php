<?php

namespace App\Services;

use Carbon\Carbon;
use InvalidArgumentException;

class AttendancePeriodService
{
    public function buildPeriod(int $month, int $year): array
    {
        if ($month < 1 || $month > 12) {
            throw new InvalidArgumentException('Bulan harus antara 1 sampai 12.');
        }

        if ($year < 2000 || $year > 2100) {
            throw new InvalidArgumentException('Tahun tidak valid.');
        }

        Carbon::setLocale('id');

        $periodEnd = Carbon::create($year, $month, 20)->startOfDay();
        $periodStart = $periodEnd->copy()->subMonth()->day(21)->startOfDay();

        return [
            'period_label' => $periodEnd->translatedFormat('F Y'),
            'period_month' => $month,
            'period_year' => $year,
            'period_start' => $periodStart->toDateString(),
            'period_end' => $periodEnd->toDateString(),
        ];
    }
}
