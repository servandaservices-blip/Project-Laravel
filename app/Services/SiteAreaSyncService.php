<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class SiteAreaSyncService
{
    private const MASTER_SITE_AREA_TABLE = 'faizal.master_site_areas';

    public function sync(?string $company = null): int
    {
        $companies = $company !== null ? [$company] : ['servanda', 'gabe', 'salus'];
        $syncedCount = 0;

        foreach ($companies as $companyKey) {
            $rows = $this->buildRows($companyKey);

            if ($rows === []) {
                continue;
            }

            DB::table(self::MASTER_SITE_AREA_TABLE)->upsert(
                $rows,
                ['company', 'area_name'],
                ['division', 'branch', 'updated_at']
            );

            $syncedCount += count($rows);
        }

        return $syncedCount;
    }

    private function buildRows(string $company): array
    {
        $timestamp = now();

        return match ($company) {
            'servanda' => $this->buildServandaRows($timestamp),
            'gabe' => $this->buildGenericRows('gabe', 'employee_gabe', 'site_area_gabe', $timestamp),
            'salus' => $this->buildGenericRows('salus', 'employee_salus', 'site_area_salus', $timestamp),
            default => [],
        };
    }

    private function buildServandaRows($timestamp): array
    {
        $columnConfigMap = [
            'site_area_ss' => ['division' => 'Security', 'branch' => 'Metro'],
            'site_area_cfs' => ['division' => 'Cleaning', 'branch' => 'Metro'],
            'site_area_cs_bpp' => ['division' => 'Cleaning', 'branch' => 'Balikpapan'],
            'site_area_ss_bpp' => ['division' => 'Security', 'branch' => 'Balikpapan'],
        ];

        $rows = [];

        foreach ($columnConfigMap as $column => $config) {
            $areas = DB::table('employee_servanda')
                ->whereNotNull($column)
                ->where($column, '!=', '')
                ->distinct()
                ->pluck($column);

            foreach ($areas as $area) {
                $areaName = trim((string) $area);

                if ($areaName === '') {
                    continue;
                }

                $key = 'servanda|' . strtolower($areaName);

                $rows[$key] = [
                    'company' => 'servanda',
                    'area_name' => $areaName,
                    'division' => $config['division'],
                    'branch' => $config['branch'],
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }
        }

        return array_values($rows);
    }

    private function buildGenericRows(string $company, string $table, string $column, $timestamp): array
    {
        return DB::table($table)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->pluck($column)
            ->map(fn ($area) => trim((string) $area))
            ->filter(fn ($area) => $area !== '')
            ->unique(fn ($area) => strtolower($area))
            ->values()
            ->map(fn ($area) => [
                'company' => $company,
                'area_name' => $area,
                'division' => 'General',
                'branch' => null,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ])
            ->all();
    }
}
