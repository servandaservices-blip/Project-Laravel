<?php

namespace App\Http\Controllers;

use App\Models\AttendanceSummaryGabe;
use App\Models\AttendanceSummarySalus;
use App\Models\AttendanceSummaryServanda;
use App\Models\WorkdayTarget;
use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Services\AttendancePeriodService;
use App\Services\AttendanceTargetStore;
use App\Services\SiteAreaSyncService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class AttendanceSummaryController extends Controller
{
    use InteractsWithUserAccess;

    private const MAX_WORKDAY = 26;
    private const MASTER_SITE_AREA_TABLE = 'faizal.master_site_areas';
    private const EMPTY_FILTER_VALUE = '__EMPTY__';

    public function dashboardWorkday(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $selectedYear = (int) ($request->query('year') ?? now()->year);
        $perPage = (int) $request->query('per_page', 10);
        $areaPerPage = (int) $request->query('area_per_page', 10);
        $selectedAreaMonth = $request->query('area_month');
        $selectedBranch = trim((string) $request->query('branch', ''));
        $selectedAreaManager = trim((string) $request->query('area_manager', ''));
        $selectedOperationManager = trim((string) $request->query('operation_manager', ''));
        $selectedDivision = $this->resolveAttendanceSummaryDivisionFilter($selectedCompany, $request->query('division'));
        $siteAreaDivision = $selectedDivision === self::EMPTY_FILTER_VALUE ? null : $selectedDivision;
        $attendanceTargetStore = app(AttendanceTargetStore::class);
        $attendanceTargetSeries = $attendanceTargetStore->dashboardTargets(
            $selectedCompany,
            $selectedYear,
            $selectedDivision,
            $selectedBranch !== '' ? $selectedBranch : null
        );
        $workdayTargets = $this->resolveWorkdayTargets(
            $selectedCompany,
            $selectedYear,
            $selectedDivision,
            $selectedBranch
        );
        $companyConfig = $this->companyConfig($selectedCompany);
        $summaryModel = $this->summaryModel($selectedCompany);
        $summaryTable = (new $summaryModel())->getTable();
        $employeeLookupQuery = $this->employeeLookupQuery($selectedCompany);

        if (! in_array($perPage, [10, 25, 50], true)) {
            $perPage = 10;
        }

        if (! in_array($areaPerPage, [10, 50, 100], true)) {
            $areaPerPage = 10;
        }

        $selectedAreaMonth = is_numeric($selectedAreaMonth)
            ? max(1, min(12, (int) $selectedAreaMonth))
            : null;

        $records = $summaryModel::query()
            ->from($summaryTable . ' as attendance_summaries')
            ->leftJoinSub(
                $employeeLookupQuery,
                'employee_master',
                fn ($join) => $join->on('employee_master.employee_no', '=', 'attendance_summaries.employee_no')
            )
            ->select([
                'attendance_summaries.employee_no',
                'attendance_summaries.employee_name',
                'attendance_summaries.period_month',
                'attendance_summaries.period_year',
                'attendance_summaries.workday_count',
                'attendance_summaries.presence_count',
                'attendance_summaries.absent_count',
                'attendance_summaries.attendance_rate',
                'employee_master.position',
                'employee_master.pay_freq',
                'employee_master.area',
                'employee_master.division',
                'employee_master.status',
            ])
            ->where('attendance_summaries.company_id', $companyConfig['company_id'])
            ->where('attendance_summaries.period_year', $selectedYear)
            ->orderBy('attendance_summaries.period_month')
            ->when($selectedDivision !== null, fn ($query) => $query->where('employee_master.division', $selectedDivision))
            ->when($selectedBranch !== '', function ($query) use ($selectedCompany, $selectedDivision, $selectedBranch) {
                $query->whereExists(function ($branchQuery) use ($selectedCompany, $selectedDivision, $selectedBranch) {
                    $branchQuery->selectRaw('1')
                        ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                        ->whereColumn('site_area_master.area_name', 'employee_master.area')
                        ->where('site_area_master.company', $selectedCompany)
                        ->when(
                            $selectedCompany === 'servanda' && $selectedDivision !== null,
                            fn ($innerQuery) => $innerQuery->where('site_area_master.division', $selectedDivision)
                        )
                        ->where('site_area_master.branch', $selectedBranch);
                });
            })
            ->when($selectedAreaManager !== '', function ($query) use ($selectedCompany, $selectedDivision, $selectedAreaManager) {
                $query->whereExists(function ($managerQuery) use ($selectedCompany, $selectedDivision, $selectedAreaManager) {
                    $managerQuery->selectRaw('1')
                        ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                        ->whereColumn('site_area_master.area_name', 'employee_master.area')
                        ->where('site_area_master.company', $selectedCompany)
                        ->when(
                            $selectedCompany === 'servanda' && $selectedDivision !== null,
                            fn ($innerQuery) => $innerQuery->where('site_area_master.division', $selectedDivision)
                        )
                        ->where('site_area_master.area_manager', $selectedAreaManager);
                });
            })
            ->when($selectedOperationManager !== '', function ($query) use ($selectedCompany, $selectedDivision, $selectedOperationManager) {
                $query->whereExists(function ($managerQuery) use ($selectedCompany, $selectedDivision, $selectedOperationManager) {
                    $managerQuery->selectRaw('1')
                        ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                        ->whereColumn('site_area_master.area_name', 'employee_master.area')
                        ->where('site_area_master.company', $selectedCompany)
                        ->when(
                            $selectedCompany === 'servanda' && $selectedDivision !== null,
                            fn ($innerQuery) => $innerQuery->where('site_area_master.division', $selectedDivision)
                        )
                        ->where('site_area_master.operation_manager', $selectedOperationManager);
                });
            })
            ->get();

        $siteAreaMetadata = $this->siteAreaFilterQuery($selectedCompany)
            ->select([
                'area_name',
                'division',
                'branch',
            ])
            ->get()
            ->groupBy(fn ($item) => strtolower(trim((string) $item->area_name)));

        $records = $records->map(function ($record) use ($siteAreaMetadata) {
            $areaKey = strtolower(trim((string) data_get($record, 'area')));
            $division = trim((string) data_get($record, 'division'));
            $branch = null;

            if ($areaKey !== '' && $siteAreaMetadata->has($areaKey)) {
                $metadata = $siteAreaMetadata->get($areaKey, collect())
                    ->first(function ($item) use ($division) {
                        $itemDivision = trim((string) ($item->division ?? ''));

                        if ($division !== '' && $itemDivision !== '') {
                            return strcasecmp($itemDivision, $division) === 0;
                        }

                        return true;
                    });

                $branch = trim((string) ($metadata->branch ?? ''));
            }

            $record->branch = $branch !== '' ? $branch : null;

            return $record;
        })->values();

        $yearlyEmployeeProfiles = $records
            ->groupBy(function ($record) {
                $employeeNo = trim((string) data_get($record, 'employee_no'));

                return $employeeNo !== '' ? $employeeNo : '__tanpa_nomor__';
            })
            ->map(function ($employeeGroup) {
                $firstRecord = $employeeGroup->first();

                return [
                    'employee_no' => trim((string) data_get($firstRecord, 'employee_no')),
                    'pay_freq' => $this->normalizePayFrequency(data_get($firstRecord, 'pay_freq')),
                    'division' => trim((string) data_get($firstRecord, 'division')) ?: null,
                    'branch' => trim((string) data_get($firstRecord, 'branch')) ?: null,
                ];
            })
            ->values();

        $months = collect(range(1, 12))->map(function (int $month) use ($records, $workdayTargets, $attendanceTargetSeries, $yearlyEmployeeProfiles) {
            $monthRecords = $records->where('period_month', $month)->values();
            $employeesByMonth = $monthRecords
                ->groupBy(function ($record) {
                    $employeeNo = trim((string) data_get($record, 'employee_no'));

                    return $employeeNo !== '' ? $employeeNo : '__tanpa_nomor__';
                })
                ->map(function ($employeeGroup) {
                    $firstRecord = $employeeGroup->first();

                    return [
                        'employee_no' => trim((string) data_get($firstRecord, 'employee_no')),
                        'pay_freq' => $this->normalizePayFrequency(data_get($firstRecord, 'pay_freq')),
                        'division' => trim((string) data_get($firstRecord, 'division')) ?: null,
                        'branch' => trim((string) data_get($firstRecord, 'branch')) ?: null,
                    ];
                })
                ->values();

            $employeeCount = $employeesByMonth->count();
            $workdayTotal = (int) $monthRecords->sum('workday_count');
            $presentTotal = (int) $monthRecords->sum('presence_count');
            $absentTotal = (int) $monthRecords->sum('absent_count');
            $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;
            $avgPresent = $employeeCount > 0 ? round($presentTotal / $employeeCount, 2) : 0;
            $avgAbsent = $employeeCount > 0 ? round($absentTotal / $employeeCount, 2) : 0;
            $avgWorkday = $employeeCount > 0 ? round($workdayTotal / $employeeCount, 2) : 0;
            $targetRows = collect($workdayTargets->get($month, []));
            $weightedTargetWorkday = $employeeCount > 0
                ? $this->calculateWeightedWorkdayTarget($employeesByMonth, $targetRows)
                : (
                    $yearlyEmployeeProfiles->isNotEmpty()
                        ? $this->calculateWeightedWorkdayTarget($yearlyEmployeeProfiles, $targetRows)
                        : $this->resolveConfiguredWorkdayFallback($targetRows)
                );
            $targetCompletion = min(($avgPresent / self::MAX_WORKDAY) * 100, 100);
            $attendanceTarget = round((float) ($attendanceTargetSeries->get($month) ?? AttendanceTargetStore::DEFAULT_TARGET), 2);

            return [
                'month' => $month,
                'label' => Carbon::create()->month($month)->translatedFormat('M'),
                'full_label' => Carbon::create()->month($month)->translatedFormat('F'),
                'period_label' => Carbon::create()->month($month)->translatedFormat('M Y'),
                'employee_count' => $employeeCount,
                'workday_total' => $workdayTotal,
                'present_total' => $presentTotal,
                'absent_total' => $absentTotal,
                'attendance_rate' => $attendanceRate,
                'avg_present' => $avgPresent,
                'avg_absent' => $avgAbsent,
                'avg_workday' => $avgWorkday,
                'target_workday_weighted' => $weightedTargetWorkday,
                'target_attendance' => $attendanceTarget,
                'target_completion' => round($targetCompletion, 2),
                'status_tone' => $this->attendanceTone($attendanceRate),
            ];
        });

        $nonEmptyMonths = $months->where('employee_count', '>', 0)->values();
        $recordCount = $records->count();
        $employeeCountYear = $records
            ->pluck('employee_no')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->count();
        $totalPresentYear = (int) $records->sum('presence_count');
        $totalAbsentYear = (int) $records->sum('absent_count');
        $totalWorkdayYear = (int) $records->sum('workday_count');
        $avgPresentYear = $recordCount > 0 ? round($totalPresentYear / $recordCount, 2) : 0;
        $avgAbsentYear = $recordCount > 0 ? round($totalAbsentYear / $recordCount, 2) : 0;
        $avgWorkdayYear = $recordCount > 0 ? round($totalWorkdayYear / $recordCount, 2) : 0;
        $annualAttendanceRate = $records->sum('workday_count') > 0
            ? round(($totalPresentYear / $totalWorkdayYear) * 100, 2)
            : 0;
        $averageAttendanceRate = $nonEmptyMonths->isNotEmpty()
            ? round((float) $nonEmptyMonths->avg('attendance_rate'), 2)
            : 0;

        $bestMonth = $nonEmptyMonths->sortByDesc('attendance_rate')->first();
        $lowestMonth = $nonEmptyMonths->sortBy('attendance_rate')->first();
        $monthsBelowTarget = $nonEmptyMonths
            ->filter(fn ($month) => (float) $month['attendance_rate'] < (float) ($month['target_attendance'] ?? AttendanceTargetStore::DEFAULT_TARGET))
            ->count();

        $currentMonthData = $nonEmptyMonths->last();
        $currentMonth = $currentMonthData['month'] ?? null;
        $currentMonthLabel = $currentMonth !== null
            ? Carbon::create()->month((int) $currentMonth)->translatedFormat('F')
            : null;
        $currentMonthAreaCount = $currentMonth !== null
            ? $records
                ->where('period_month', $currentMonth)
                ->pluck('area')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => filled($value))
                ->unique(fn ($value) => strtolower($value))
                ->count()
            : 0;

        $positionChart = $this->buildGroupedChartData($records, 'position');
        $areaChart = $this->buildGroupedChartData($records, 'area');
        $payFrequencyChart = $this->buildGroupedChartData(
            $records->map(function ($record) {
                $record->pay_freq = $this->normalizePayFrequency(data_get($record, 'pay_freq'));

                return $record;
            }),
            'pay_freq'
        );

        $areaTableRecords = $selectedAreaMonth !== null
            ? $records->where('period_month', $selectedAreaMonth)->values()
            : $records;

        $areaAttendanceTableCollection = $areaTableRecords
            ->groupBy(function ($record) {
                $value = trim((string) data_get($record, 'area'));

                return $value !== '' ? $value : 'Tanpa Data';
            })
            ->map(function ($group, $label) {
                $employeeGroups = $group
                    ->groupBy(function ($record) {
                        $employeeNo = trim((string) data_get($record, 'employee_no'));

                        return $employeeNo !== '' ? $employeeNo : '__tanpa_nomor__';
                    })
                    ->map(function ($employeeGroup, $employeeNo) {
                        $firstRecord = $employeeGroup->first();
                        $workdayTotal = (int) $employeeGroup->sum('workday_count');
                        $presentTotal = (int) $employeeGroup->sum('presence_count');
                        $absentTotal = (int) $employeeGroup->sum('absent_count');
                        $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;

                        return [
                            'employee_no' => $employeeNo === '__tanpa_nomor__' ? '-' : $employeeNo,
                            'employee_name' => trim((string) data_get($firstRecord, 'employee_name')) ?: 'Tanpa Nama',
                            'position' => trim((string) data_get($firstRecord, 'position')) ?: '-',
                            'pay_freq' => $this->normalizePayFrequency(data_get($firstRecord, 'pay_freq')),
                            'workday_total' => $workdayTotal,
                            'present_total' => $presentTotal,
                            'absent_total' => $absentTotal,
                            'attendance_rate' => $attendanceRate,
                        ];
                    })
                    ->sortBy([
                        ['attendance_rate', 'desc'],
                        ['employee_name', 'asc'],
                    ])
                    ->values();

                $employeeCount = $employeeGroups->count();
                $workdayTotal = (int) $group->sum('workday_count');
                $presentTotal = (int) $group->sum('presence_count');
                $absentTotal = (int) $group->sum('absent_count');
                $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;
                $avgWorkday = $employeeCount > 0 ? round($workdayTotal / $employeeCount, 2) : 0;
                $avgPresent = $employeeCount > 0 ? round($presentTotal / $employeeCount, 2) : 0;
                $avgAbsent = $employeeCount > 0 ? round($absentTotal / $employeeCount, 2) : 0;

                return [
                    'label' => $label,
                    'employee_count' => $employeeCount,
                    'avg_workday' => $avgWorkday,
                    'avg_present' => $avgPresent,
                    'avg_absent' => $avgAbsent,
                    'attendance_rate' => $attendanceRate,
                    'employees' => $employeeGroups,
                ];
            })
            ->sortBy([
                ['attendance_rate', 'desc'],
                ['avg_present', 'desc'],
            ])
            ->values();

        $areaEmployeeCount = $areaTableRecords
            ->pluck('employee_no')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->count();
        $areaGroupCount = $areaAttendanceTableCollection->count();
        $areaAvgWorkday = $areaGroupCount > 0 ? round((float) $areaAttendanceTableCollection->avg('avg_workday'), 2) : 0;
        $areaAvgPresent = $areaGroupCount > 0 ? round((float) $areaAttendanceTableCollection->avg('avg_present'), 2) : 0;
        $areaAvgAbsent = $areaGroupCount > 0 ? round((float) $areaAttendanceTableCollection->avg('avg_absent'), 2) : 0;
        $areaAttendanceRate = $areaGroupCount > 0 ? round((float) $areaAttendanceTableCollection->avg('attendance_rate'), 2) : 0;

        $areaCurrentPage = max((int) $request->query('area_page', 1), 1);
        $areaAttendanceTable = new LengthAwarePaginator(
            $areaAttendanceTableCollection->forPage($areaCurrentPage, $areaPerPage)->values(),
            $areaAttendanceTableCollection->count(),
            $areaPerPage,
            $areaCurrentPage,
            [
                'path' => $request->url(),
                'pageName' => 'area_page',
                'query' => collect($request->query())
                    ->except('area_page')
                    ->all(),
            ]
        );

        $selectedPositionMonth = $request->query('position_month');
        $positionPerPage = (int) $request->query('position_per_page', 10);

        if (! in_array($positionPerPage, [10, 50, 100], true)) {
            $positionPerPage = 10;
        }

        $selectedPositionMonth = is_numeric($selectedPositionMonth)
            ? max(1, min(12, (int) $selectedPositionMonth))
            : null;

        $positionTableRecords = $selectedPositionMonth !== null
            ? $records->where('period_month', $selectedPositionMonth)->values()
            : $records;

        $positionAttendanceTableCollection = $positionTableRecords
            ->groupBy(function ($record) {
                $value = trim((string) data_get($record, 'position'));

                return $value !== '' ? $value : 'Tanpa Data';
            })
            ->map(function ($group, $label) {
                $employeeGroups = $group
                    ->groupBy(function ($record) {
                        $employeeNo = trim((string) data_get($record, 'employee_no'));

                        return $employeeNo !== '' ? $employeeNo : '__tanpa_nomor__';
                    })
                    ->map(function ($employeeGroup, $employeeNo) {
                        $firstRecord = $employeeGroup->first();
                        $workdayTotal = (int) $employeeGroup->sum('workday_count');
                        $presentTotal = (int) $employeeGroup->sum('presence_count');
                        $absentTotal = (int) $employeeGroup->sum('absent_count');
                        $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;

                        return [
                            'employee_no' => $employeeNo === '__tanpa_nomor__' ? '-' : $employeeNo,
                            'employee_name' => trim((string) data_get($firstRecord, 'employee_name')) ?: 'Tanpa Nama',
                            'position' => trim((string) data_get($firstRecord, 'position')) ?: '-',
                            'area' => trim((string) data_get($firstRecord, 'area')) ?: 'Tanpa Data',
                            'pay_freq' => $this->normalizePayFrequency(data_get($firstRecord, 'pay_freq')),
                            'workday_total' => $workdayTotal,
                            'present_total' => $presentTotal,
                            'absent_total' => $absentTotal,
                            'attendance_rate' => $attendanceRate,
                        ];
                    })
                    ->sortBy([
                        ['attendance_rate', 'desc'],
                        ['employee_name', 'asc'],
                    ])
                    ->values();

                $employeeCount = $employeeGroups->count();
                $workdayTotal = (int) $group->sum('workday_count');
                $presentTotal = (int) $group->sum('presence_count');
                $absentTotal = (int) $group->sum('absent_count');
                $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;
                $avgWorkday = $employeeCount > 0 ? round($workdayTotal / $employeeCount, 2) : 0;
                $avgPresent = $employeeCount > 0 ? round($presentTotal / $employeeCount, 2) : 0;
                $avgAbsent = $employeeCount > 0 ? round($absentTotal / $employeeCount, 2) : 0;

                return [
                    'label' => $label,
                    'employee_count' => $employeeCount,
                    'avg_workday' => $avgWorkday,
                    'avg_present' => $avgPresent,
                    'avg_absent' => $avgAbsent,
                    'attendance_rate' => $attendanceRate,
                    'employees' => $employeeGroups,
                ];
            })
            ->sortBy([
                ['attendance_rate', 'desc'],
                ['avg_present', 'desc'],
            ])
            ->values();

        $positionEmployeeCount = $positionTableRecords
            ->pluck('employee_no')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->unique()
            ->count();
        $positionGroupCount = $positionAttendanceTableCollection->count();
        $positionAvgWorkday = $positionGroupCount > 0 ? round((float) $positionAttendanceTableCollection->avg('avg_workday'), 2) : 0;
        $positionAvgPresent = $positionGroupCount > 0 ? round((float) $positionAttendanceTableCollection->avg('avg_present'), 2) : 0;
        $positionAvgAbsent = $positionGroupCount > 0 ? round((float) $positionAttendanceTableCollection->avg('avg_absent'), 2) : 0;
        $positionAttendanceRate = $positionGroupCount > 0 ? round((float) $positionAttendanceTableCollection->avg('attendance_rate'), 2) : 0;

        $positionCurrentPage = max((int) $request->query('position_page', 1), 1);
        $positionAttendanceTable = new LengthAwarePaginator(
            $positionAttendanceTableCollection->forPage($positionCurrentPage, $positionPerPage)->values(),
            $positionAttendanceTableCollection->count(),
            $positionPerPage,
            $positionCurrentPage,
            [
                'path' => $request->url(),
                'pageName' => 'position_page',
                'query' => collect($request->query())
                    ->except('position_page')
                    ->all(),
            ]
        );

        $employeeYearly = $records
            ->groupBy(function ($record) {
                $employeeNo = trim((string) data_get($record, 'employee_no'));

                return $employeeNo !== '' ? $employeeNo : '__tanpa_nomor__';
            })
            ->map(function ($group, $employeeNo) {
                $workdayTotal = (int) $group->sum('workday_count');
                $presentTotal = (int) $group->sum('presence_count');
                $absentTotal = (int) $group->sum('absent_count');
                $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;
                $firstRecord = $group->first();

                return [
                    'employee_no' => $employeeNo === '__tanpa_nomor__' ? '-' : $employeeNo,
                    'employee_name' => trim((string) data_get($firstRecord, 'employee_name')) ?: 'Tanpa Nama',
                    'position' => trim((string) data_get($firstRecord, 'position')) ?: '-',
                    'pay_freq' => $this->normalizePayFrequency(data_get($firstRecord, 'pay_freq')),
                    'area' => trim((string) data_get($firstRecord, 'area')) ?: 'Tanpa Data',
                    'workday_total' => $workdayTotal,
                    'present_total' => $presentTotal,
                    'absent_total' => $absentTotal,
                    'attendance_rate' => $attendanceRate,
                ];
            })
            ->sortBy([
                ['attendance_rate', 'desc'],
                ['employee_name', 'asc'],
            ])
            ->values();

        $topAttendance = $employeeYearly->take(5)->values();
        $lowestAttendance = $employeeYearly->sortBy([
            ['attendance_rate', 'asc'],
            ['absent_total', 'desc'],
        ])->take(5)->values();

        $siteAreaFilterOptions = $this->siteAreaFilterQuery($selectedCompany)
            ->select([
                'branch',
                'area_manager',
                'operation_manager',
            ])
            ->get();

        $branchOptions = $siteAreaFilterOptions
            ->pluck('branch')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->unique(fn ($value) => strtolower($value))
            ->sort()
            ->values();

        $areaManagers = $siteAreaFilterOptions
            ->pluck('area_manager')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->unique(fn ($value) => strtolower($value))
            ->sort()
            ->values();

        $operationManagers = $siteAreaFilterOptions
            ->pluck('operation_manager')
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->unique(fn ($value) => strtolower($value))
            ->sort()
            ->values();

        $currentPage = max((int) $request->query('page', 1), 1);
        $paginatedReports = new LengthAwarePaginator(
            $employeeYearly->forPage($currentPage, $perPage)->values(),
            $employeeYearly->count(),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('dashboard.workday-dashboard', [
            'selectedCompany' => $selectedCompany,
            'selectedYear' => $selectedYear,
            'selectedDivision' => $selectedDivision,
            'selectedBranch' => $selectedBranch,
            'selectedAreaManager' => $selectedAreaManager,
            'selectedOperationManager' => $selectedOperationManager,
            'attendanceTargetSeries' => $attendanceTargetSeries->values(),
            'companyName' => $companyConfig['label'],
            'maxWorkday' => self::MAX_WORKDAY,
            'selectedPerPage' => $perPage,
            'selectedAreaPerPage' => $areaPerPage,
            'selectedAreaMonth' => $selectedAreaMonth,
            'selectedPositionPerPage' => $positionPerPage,
            'selectedPositionMonth' => $selectedPositionMonth,
            'months' => $months,
            'branchOptions' => $branchOptions,
            'areaManagers' => $areaManagers,
            'operationManagers' => $operationManagers,
            'summaryCards' => [
                'employee_count' => $employeeCountYear,
                'current_month_area_count' => $currentMonthAreaCount,
                'current_month_label' => $currentMonthLabel,
                'total_present' => $totalPresentYear,
                'total_absent' => $totalAbsentYear,
                'avg_present' => $avgPresentYear,
                'avg_absent' => $avgAbsentYear,
                'avg_workday' => $avgWorkdayYear,
                'annual_attendance_rate' => $annualAttendanceRate,
                'average_attendance_rate' => $averageAttendanceRate,
                'months_below_target' => $monthsBelowTarget,
            ],
            'bestMonth' => $bestMonth,
            'lowestMonth' => $lowestMonth,
            'positionChart' => $positionChart,
            'areaChart' => $areaChart,
            'payFrequencyChart' => $payFrequencyChart,
            'areaAttendanceTable' => $areaAttendanceTable,
            'areaSummaryCards' => [
                'employee_count' => $areaEmployeeCount,
                'group_count' => $areaGroupCount,
                'avg_workday' => $areaAvgWorkday,
                'avg_present' => $areaAvgPresent,
                'avg_absent' => $areaAvgAbsent,
                'attendance_rate' => $areaAttendanceRate,
            ],
            'positionAttendanceTable' => $positionAttendanceTable,
            'positionSummaryCards' => [
                'employee_count' => $positionEmployeeCount,
                'group_count' => $positionGroupCount,
                'avg_workday' => $positionAvgWorkday,
                'avg_present' => $positionAvgPresent,
                'avg_absent' => $positionAvgAbsent,
                'attendance_rate' => $positionAttendanceRate,
            ],
            'topAttendance' => $topAttendance,
            'lowestAttendance' => $lowestAttendance,
            'reports' => $paginatedReports,
        ]);
    }

    public function dashboardAttendanceArea(Request $request)
    {
        $attendanceAreaRequest = $request;

        if (! $request->filled('area_month')) {
            $attendanceAreaRequest = $request->duplicate(
                array_merge($request->query(), ['area_month' => now()->month]),
                $request->request->all()
            );
        }

        $workdayView = $this->dashboardWorkday($attendanceAreaRequest);

        return view('dashboard.attendance-area-dashboard', $workdayView->getData());
    }

    public function dashboardAttendancePosition(Request $request)
    {
        $attendancePositionRequest = $request;

        if (! $request->filled('position_month')) {
            $attendancePositionRequest = $request->duplicate(
                array_merge($request->query(), ['position_month' => now()->month]),
                $request->request->all()
            );
        }

        $workdayView = $this->dashboardWorkday($attendancePositionRequest);

        return view('dashboard.attendance-position-dashboard', $workdayView->getData());
    }

    public function index(Request $request, AttendancePeriodService $periodService)
    {
        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);
        $perPage = (int) $request->query('per_page', 10);
        $search = trim((string) $request->query('search', ''));
        $attendanceRateFilter = (string) $request->query('attendance_rate_filter', '');
        $positionFilters = collect($request->query('position', []))
            ->when(! is_array($request->query('position')), fn ($collection) => collect([(string) $request->query('position', '')]))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->values();
        $payFrequencyFilter = trim((string) $request->query('pay_freq', ''));
        $areaFilters = collect($request->query('area', []))
            ->when(! is_array($request->query('area')), fn ($collection) => collect([(string) $request->query('area', '')]))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->values();
        $selectedAreaManager = trim((string) $request->query('area_manager', ''));
        $selectedOperationManager = trim((string) $request->query('operation_manager', ''));
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $selectedDivision = $this->resolveAttendanceSummaryDivisionFilter($selectedCompany, $request->query('division'));
        $siteAreaDivision = $selectedDivision === self::EMPTY_FILTER_VALUE ? null : $selectedDivision;
        $companyConfig = $this->companyConfig($selectedCompany);
        $summaryModel = $this->summaryModel($selectedCompany);
        $summaryTable = (new $summaryModel())->getTable();
        $allowedPerPage = [10, 50, 100];
        $allowedAttendanceRateFilters = ['gte_90', 'lt_90'];
        $allowedPayFrequencyFilters = ['Harian', 'Bulanan', 'Tanpa Data'];

        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 10;
        }

        if (! in_array($attendanceRateFilter, $allowedAttendanceRateFilters, true)) {
            $attendanceRateFilter = '';
        }

        if (! in_array($payFrequencyFilter, $allowedPayFrequencyFilters, true)) {
            $payFrequencyFilter = '';
        }

        $period = $periodService->buildPeriod($month, $year);
        $employeeLookupQuery = $this->employeeLookupQuery($selectedCompany);

        $baseQuery = $summaryModel::query()
            ->from($summaryTable . ' as attendance_summaries')
            ->leftJoinSub(
                $employeeLookupQuery,
                'employee_master',
                fn ($join) => $join->on('employee_master.employee_no', '=', 'attendance_summaries.employee_no')
            )
            ->select([
                'attendance_summaries.*',
                'employee_master.position',
                'employee_master.pay_freq',
                'employee_master.area',
                'employee_master.division',
            ])
            ->where('attendance_summaries.company_id', $companyConfig['company_id'])
            ->where('attendance_summaries.period_month', $period['period_month'])
            ->where('attendance_summaries.period_year', $period['period_year']);

        if ($selectedDivision === self::EMPTY_FILTER_VALUE) {
            $baseQuery->where(function ($query) {
                $query->whereNull('employee_master.division')
                    ->orWhereRaw("TRIM(COALESCE(employee_master.division, '')) = ''")
                    ->orWhereRaw("LOWER(TRIM(COALESCE(employee_master.division, ''))) = '-'");
            });
        } elseif ($selectedDivision !== null) {
            $baseQuery->where('employee_master.division', $selectedDivision);
        }

        if ($search !== '') {
            $baseQuery->where(function ($query) use ($search) {
                $query->where('attendance_summaries.employee_name', 'like', '%' . $search . '%')
                    ->orWhere('attendance_summaries.employee_no', 'like', '%' . $search . '%')
                    ->orWhere('employee_master.area', 'like', '%' . $search . '%');
            });
        }

        if ($attendanceRateFilter === 'gte_90') {
            $baseQuery->where('attendance_rate', '>=', 90);
        }

        if ($attendanceRateFilter === 'lt_90') {
            $baseQuery->where('attendance_rate', '<', 90);
        }

        if ($positionFilters->isNotEmpty()) {
            $positionValues = $positionFilters
                ->reject(fn ($value) => $value === self::EMPTY_FILTER_VALUE)
                ->values();
            $includeEmptyPosition = $positionFilters->contains(self::EMPTY_FILTER_VALUE);

            $baseQuery->where(function ($query) use ($positionValues, $includeEmptyPosition) {
                if ($positionValues->isNotEmpty()) {
                    $query->whereIn('employee_master.position', $positionValues->all());
                }

                if ($includeEmptyPosition) {
                    $method = $positionValues->isNotEmpty() ? 'orWhere' : 'where';

                    $query->{$method}(function ($emptyQuery) {
                        $emptyQuery
                            ->whereNull('employee_master.position')
                            ->orWhereRaw("TRIM(COALESCE(employee_master.position, '')) = ''")
                            ->orWhereRaw("LOWER(TRIM(COALESCE(employee_master.position, ''))) = '-'");
                    });
                }
            });
        }

        if ($areaFilters->isNotEmpty()) {
            $areaValues = $areaFilters
                ->reject(fn ($value) => $value === self::EMPTY_FILTER_VALUE)
                ->values();
            $includeEmptyArea = $areaFilters->contains(self::EMPTY_FILTER_VALUE);

            $baseQuery->where(function ($query) use ($areaValues, $includeEmptyArea) {
                if ($areaValues->isNotEmpty()) {
                    $query->whereIn('employee_master.area', $areaValues->all());
                }

                if ($includeEmptyArea) {
                    $method = $areaValues->isNotEmpty() ? 'orWhere' : 'where';

                    $query->{$method}(function ($emptyQuery) {
                        $emptyQuery
                            ->whereNull('employee_master.area')
                            ->orWhereRaw("TRIM(COALESCE(employee_master.area, '')) = ''")
                            ->orWhereRaw("LOWER(TRIM(COALESCE(employee_master.area, ''))) = '-'");
                    });
                }
            });
        }

        if ($selectedAreaManager !== '') {
            $baseQuery->where(function ($outerQuery) use ($selectedCompany, $siteAreaDivision, $selectedAreaManager) {
                if ($selectedAreaManager === self::EMPTY_FILTER_VALUE) {
                    $outerQuery->where(function ($query) use ($selectedCompany, $siteAreaDivision) {
                        $query->whereNull('employee_master.area')
                            ->orWhereRaw("TRIM(COALESCE(employee_master.area, '')) = ''")
                            ->orWhereRaw("LOWER(TRIM(COALESCE(employee_master.area, ''))) = '-'")
                            ->orWhereExists(function ($managerQuery) use ($selectedCompany, $siteAreaDivision) {
                                $managerQuery->selectRaw('1')
                                    ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                                    ->whereColumn('site_area_master.area_name', 'employee_master.area')
                                    ->where('site_area_master.company', $selectedCompany)
                                    ->when(
                                        $selectedCompany === 'servanda' && $siteAreaDivision !== null,
                                        fn ($innerQuery) => $innerQuery->where('site_area_master.division', $siteAreaDivision)
                                    )
                                    ->where(function ($emptyManagerQuery) {
                                        $emptyManagerQuery
                                            ->whereNull('site_area_master.area_manager')
                                            ->orWhereRaw("TRIM(COALESCE(site_area_master.area_manager, '')) = ''")
                                            ->orWhereRaw("LOWER(TRIM(COALESCE(site_area_master.area_manager, ''))) = '-'");
                                    });
                            });
                    });

                    return;
                }

                $outerQuery->whereExists(function ($query) use ($selectedCompany, $siteAreaDivision, $selectedAreaManager) {
                    $query->selectRaw('1')
                        ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                        ->whereColumn('site_area_master.area_name', 'employee_master.area')
                        ->where('site_area_master.company', $selectedCompany)
                        ->when(
                            $selectedCompany === 'servanda' && $siteAreaDivision !== null,
                            fn ($innerQuery) => $innerQuery->where('site_area_master.division', $siteAreaDivision)
                        )
                        ->where('site_area_master.area_manager', $selectedAreaManager);
                });
            });
        }

        if ($selectedOperationManager !== '') {
            $baseQuery->where(function ($outerQuery) use ($selectedCompany, $siteAreaDivision, $selectedOperationManager) {
                if ($selectedOperationManager === self::EMPTY_FILTER_VALUE) {
                    $outerQuery->where(function ($query) use ($selectedCompany, $siteAreaDivision) {
                        $query->whereNull('employee_master.area')
                            ->orWhereRaw("TRIM(COALESCE(employee_master.area, '')) = ''")
                            ->orWhereRaw("LOWER(TRIM(COALESCE(employee_master.area, ''))) = '-'")
                            ->orWhereExists(function ($managerQuery) use ($selectedCompany, $siteAreaDivision) {
                                $managerQuery->selectRaw('1')
                                    ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                                    ->whereColumn('site_area_master.area_name', 'employee_master.area')
                                    ->where('site_area_master.company', $selectedCompany)
                                    ->when(
                                        $selectedCompany === 'servanda' && $siteAreaDivision !== null,
                                        fn ($innerQuery) => $innerQuery->where('site_area_master.division', $siteAreaDivision)
                                    )
                                    ->where(function ($emptyManagerQuery) {
                                        $emptyManagerQuery
                                            ->whereNull('site_area_master.operation_manager')
                                            ->orWhereRaw("TRIM(COALESCE(site_area_master.operation_manager, '')) = ''")
                                            ->orWhereRaw("LOWER(TRIM(COALESCE(site_area_master.operation_manager, ''))) = '-'");
                                    });
                            });
                    });

                    return;
                }

                $outerQuery->whereExists(function ($query) use ($selectedCompany, $siteAreaDivision, $selectedOperationManager) {
                    $query->selectRaw('1')
                        ->from(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
                        ->whereColumn('site_area_master.area_name', 'employee_master.area')
                        ->where('site_area_master.company', $selectedCompany)
                        ->when(
                            $selectedCompany === 'servanda' && $siteAreaDivision !== null,
                            fn ($innerQuery) => $innerQuery->where('site_area_master.division', $siteAreaDivision)
                        )
                        ->where('site_area_master.operation_manager', $selectedOperationManager);
                });
            });
        }

        if ($payFrequencyFilter !== '') {
            $this->applyPayFrequencyFilter($baseQuery, $payFrequencyFilter);
        }

        $filterOptions = DB::query()
            ->fromSub($employeeLookupQuery, 'employee_master')
            ->select([
                'employee_master.position',
                'employee_master.pay_freq',
                'employee_master.area',
            ])
            ->get();

        $positions = $this->prependEmptyFilterOption(
            $filterOptions
                ->pluck('position')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => filled($value))
                ->unique(fn ($value) => strtolower($value))
                ->sort()
                ->values()
        );

        $areas = $this->prependEmptyFilterOption(
            $filterOptions
                ->pluck('area')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => filled($value))
                ->unique(fn ($value) => strtolower($value))
                ->sort()
                ->values()
        );

        $siteAreaFilterOptions = $this->siteAreaFilterQuery($selectedCompany, $siteAreaDivision)
            ->select([
                'area_manager',
                'operation_manager',
            ])
            ->get();

        $areaManagers = $this->prependEmptyFilterOption(
            $siteAreaFilterOptions
                ->pluck('area_manager')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => filled($value))
                ->unique(fn ($value) => strtolower($value))
                ->sort()
                ->values()
        );

        $operationManagers = $this->prependEmptyFilterOption(
            $siteAreaFilterOptions
                ->pluck('operation_manager')
                ->map(fn ($value) => trim((string) $value))
                ->filter(fn ($value) => filled($value))
                ->unique(fn ($value) => strtolower($value))
                ->sort()
                ->values()
        );

        $payFrequencies = collect(['Harian', 'Bulanan', 'Tanpa Data']);

        $summaryStats = [
            'employee_count' => (clone $baseQuery)->count(),
            'avg_workday' => round((float) ((clone $baseQuery)->avg('workday_count') ?? 0), 2),
            'avg_present' => round((float) ((clone $baseQuery)->avg('presence_count') ?? 0), 2),
            'avg_absent' => round((float) ((clone $baseQuery)->avg('absent_count') ?? 0), 2),
            'avg_attendance_rate' => round((float) ((clone $baseQuery)->avg('attendance_rate') ?? 0), 2),
        ];

        $reports = (clone $baseQuery)
            ->orderBy('employee_name')
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.attendance-summary', [
            'reports' => $reports,
            'selectedMonth' => $month,
            'selectedYear' => $year,
            'selectedPerPage' => $perPage,
            'selectedSearch' => $search,
            'selectedAttendanceRateFilter' => $attendanceRateFilter,
            'selectedPositionFilters' => $positionFilters,
            'selectedPayFrequencyFilter' => $payFrequencyFilter,
            'selectedAreaFilters' => $areaFilters,
            'selectedAreaManager' => $selectedAreaManager,
            'selectedOperationManager' => $selectedOperationManager,
            'selectedCompany' => $selectedCompany,
            'selectedDivision' => $selectedDivision,
            'companyName' => $companyConfig['label'],
            'period' => $period,
            'summaryStats' => $summaryStats,
            'positions' => $positions,
            'payFrequencies' => $payFrequencies,
            'areas' => $areas,
            'areaManagers' => $areaManagers,
            'operationManagers' => $operationManagers,
        ]);
    }

    public function sync(Request $request, AttendancePeriodService $periodService, SiteAreaSyncService $siteAreaSyncService): RedirectResponse
    {
        $month = (int) ($request->month ?? now()->month);
        $year = (int) ($request->year ?? now()->year);
        $perPage = (int) ($request->input('per_page', 10));
        $search = trim((string) $request->input('search', ''));
        $attendanceRateFilter = (string) $request->input('attendance_rate_filter', '');
        $positionFilters = collect($request->input('position', []))
            ->when(! is_array($request->input('position')), fn ($collection) => collect([(string) $request->input('position', '')]))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->values();
        $payFrequencyFilter = trim((string) $request->input('pay_freq', ''));
        $areaFilters = collect($request->input('area', []))
            ->when(! is_array($request->input('area')), fn ($collection) => collect([(string) $request->input('area', '')]))
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => filled($value))
            ->values();
        $selectedAreaManager = trim((string) $request->input('area_manager', ''));
        $selectedOperationManager = trim((string) $request->input('operation_manager', ''));
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $selectedDivision = $this->resolveDivisionFilter($selectedCompany, $request->input('division'));
        $companyConfig = $this->companyConfig($selectedCompany);
        $summaryModel = $this->summaryModel($selectedCompany);

        if (! $this->hasValidApiKey($companyConfig['api_key'] ?? null)) {
            return redirect()
                ->route('attendance.summary', [
                    'company' => $selectedCompany,
                    'month' => $month,
                    'year' => $year,
                    'per_page' => $perPage,
                    'search' => $search,
                    'attendance_rate_filter' => $attendanceRateFilter,
                    'position' => $positionFilters->all(),
                    'pay_freq' => $payFrequencyFilter,
                    'area' => $areaFilters->all(),
                    'area_manager' => $selectedAreaManager,
                    'operation_manager' => $selectedOperationManager,
                    'division' => $selectedDivision,
                ])
                ->with('error', 'API key SmartPresence untuk company ini belum diatur dengan benar di file .env.');
        }

        $period = $periodService->buildPeriod($month, $year);

        try {
            $response = Http::timeout(60)
                ->withHeaders([
                    'Content-Type' => 'text/plain',
                    'Accept' => '/',
                    'Cache-Control' => 'no-cache',
                    'apikey' => $companyConfig['api_key'],
                ])
                ->get(rtrim(config('services.smartpresence.base_url'), '/') . '/virtusabsence', [
                    'startdate' => $period['period_start'],
                    'enddate' => $period['period_end'],
                ]);

            $response->throw();

            $payload = $response->json();
            $rows = collect($payload['data'] ?? []);

            if (($payload['status'] ?? null) !== 'OK') {
                throw new \RuntimeException('Response SmartPresence tidak valid.');
            }

            $timestamp = Carbon::now();

            $records = $rows->map(function (array $row) use ($companyConfig, $period, $timestamp) {
                $workdayCount = max((int) ($row['workday_count'] ?? 0), 0);
                $presenceCount = max((int) ($row['presence_count'] ?? 0), 0);
                $absentCount = max($workdayCount - $presenceCount, 0);
                $attendanceRate = $workdayCount > 0
                    ? round(($presenceCount / $workdayCount) * 100, 2)
                    : 0;

                return [
                    'company_id' => $companyConfig['company_id'],
                    'employee_id' => null,
                    'employee_no' => trim((string) ($row['employee_number'] ?? '')),
                    'employee_name' => trim((string) ($row['employee_name'] ?? '')),
                    'smartpresence_employee_id' => isset($row['employee_id']) ? (int) $row['employee_id'] : null,
                    'period_label' => $period['period_label'],
                    'period_month' => $period['period_month'],
                    'period_year' => $period['period_year'],
                    'period_start' => $period['period_start'],
                    'period_end' => $period['period_end'],
                    'workday_count' => $workdayCount,
                    'presence_count' => $presenceCount,
                    'absent_count' => $absentCount,
                    'attendance_rate' => $attendanceRate,
                    'last_sync_at' => $timestamp,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            })
            ->filter(fn (array $record) => filled($record['employee_no']) && filled($record['employee_name']))
            ->values()
            ->all();

            if (! empty($records)) {
                collect($records)
                    ->chunk(200)
                    ->each(function ($chunk) use ($summaryModel) {
                        $summaryModel::upsert(
                            $chunk->all(),
                            ['company_id', 'employee_no', 'period_start', 'period_end'],
                            [
                                'employee_name',
                                'smartpresence_employee_id',
                                'period_label',
                                'period_month',
                                'period_year',
                                'workday_count',
                                'presence_count',
                                'absent_count',
                                'attendance_rate',
                                'last_sync_at',
                                'updated_at',
                            ]
                        );
                    });
            }

            $siteAreaSyncService->sync($selectedCompany);

            return redirect()
                ->route('attendance.summary', [
                    'company' => $selectedCompany,
                    'month' => $month,
                    'year' => $year,
                    'per_page' => $perPage,
                    'search' => $search,
                    'attendance_rate_filter' => $attendanceRateFilter,
                    'position' => $positionFilters->all(),
                    'pay_freq' => $payFrequencyFilter,
                    'area' => $areaFilters->all(),
                    'area_manager' => $selectedAreaManager,
                    'operation_manager' => $selectedOperationManager,
                    'division' => $selectedDivision,
                ])
                ->with('success', 'Syncronisasi berhasil. Data attendance summary dan master Site Area sudah diperbarui.');
        } catch (\Throwable $e) {
            Log::error('Attendance summary sync failed', [
                'company' => $selectedCompany,
                'month' => $month,
                'year' => $year,
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('attendance.summary', [
                    'company' => $selectedCompany,
                    'month' => $month,
                    'year' => $year,
                    'per_page' => $perPage,
                    'search' => $search,
                    'attendance_rate_filter' => $attendanceRateFilter,
                    'position' => $positionFilters->all(),
                    'pay_freq' => $payFrequencyFilter,
                    'area' => $areaFilters->all(),
                    'area_manager' => $selectedAreaManager,
                    'operation_manager' => $selectedOperationManager,
                    'division' => $selectedDivision,
                ])
                ->with('error', 'Syncronisasi gagal. Periksa konfigurasi API atau response SmartPresence.');
        }
    }

    private function siteAreaFilterQuery(string $companyKey, ?string $division = null)
    {
        return DB::table(self::MASTER_SITE_AREA_TABLE . ' as site_area_master')
            ->where('site_area_master.company', $companyKey)
            ->when(
                $companyKey === 'servanda' && $division !== null,
                fn ($query) => $query->where('site_area_master.division', $division)
            );
    }

    private function resolveWorkdayTargets(string $company, int $year, ?string $division = null, ?string $branch = null)
    {
        $targets = WorkdayTarget::query()
            ->where('company', $company)
            ->where('year', $year)
            ->when(
                $company === 'servanda',
                fn ($query) => $query
                    ->whereNotNull('division')
                    ->where('division', '!=', '')
            )
            ->when(
                $company === 'servanda' && $division !== null,
                fn ($query) => $query->where('division', $division)
            )
            ->when(
                $branch !== null,
                fn ($query) => $query->where(function ($branchQuery) use ($branch) {
                    $branchQuery
                        ->where('branch', $branch)
                        ->orWhereNull('branch');
                })
            )
            ->get();

        return collect(range(1, 12))->mapWithKeys(function (int $month) use ($targets) {
            return [
                $month => $targets
                    ->where('month', $month)
                    ->map(function ($target) {
                        return [
                            'division' => $this->normalizeNullableFilterValue($target->division),
                            'branch' => $this->normalizeNullableFilterValue($target->branch),
                            'monthly_target' => $target->monthly_target !== null
                                ? (float) $target->monthly_target
                                : 21.00,
                            'daily_target' => $target->daily_target !== null
                                ? (float) $target->daily_target
                                : (float) self::MAX_WORKDAY,
                        ];
                    })
                    ->values(),
            ];
        });
    }

    private function resolveMatchingWorkdayTarget($targets, ?string $division = null, ?string $branch = null): array
    {
        $normalizedDivision = $this->normalizeNullableFilterValue($division);
        $normalizedBranch = $this->normalizeNullableFilterValue($branch);

        $match = collect($targets)
            ->map(function ($target) {
                return [
                    'division' => $this->normalizeNullableFilterValue(data_get($target, 'division')),
                    'branch' => $this->normalizeNullableFilterValue(data_get($target, 'branch')),
                    'monthly_target' => (float) (data_get($target, 'monthly_target', 21.00)),
                    'daily_target' => (float) (data_get($target, 'daily_target', self::MAX_WORKDAY)),
                ];
            })
            ->filter(function (array $target) use ($normalizedDivision, $normalizedBranch) {
                if ($target['division'] !== null && $target['division'] !== $normalizedDivision) {
                    return false;
                }

                if ($target['branch'] !== null && $target['branch'] !== $normalizedBranch) {
                    return false;
                }

                return true;
            })
            ->sortByDesc(function (array $target) {
                $score = 0;

                if ($target['division'] !== null) {
                    $score += 2;
                }

                if ($target['branch'] !== null) {
                    $score += 1;
                }

                return $score;
            })
            ->first();

        return $match ?? [
            'division' => null,
            'branch' => null,
            'monthly_target' => 21.00,
            'daily_target' => (float) self::MAX_WORKDAY,
        ];
    }

    private function calculateWeightedWorkdayTarget($employees, $targetRows): float
    {
        $employees = collect($employees)->values();
        $employeeCount = $employees->count();

        if ($employeeCount === 0) {
            return $this->resolveConfiguredWorkdayFallback($targetRows);
        }

        $employeeTargetTotal = $employees->sum(function ($employee) use ($targetRows) {
            $target = $this->resolveMatchingWorkdayTarget(
                $targetRows,
                data_get($employee, 'division'),
                data_get($employee, 'branch')
            );

            $monthlyTarget = (float) ($target['monthly_target'] ?? 21.00);
            $dailyTarget = (float) ($target['daily_target'] ?? self::MAX_WORKDAY);
            $payFrequency = data_get($employee, 'pay_freq', 'Tanpa Data');

            return match ($payFrequency) {
                'Bulanan' => $monthlyTarget,
                'Harian' => $dailyTarget,
                default => 0,
            };
        });

        return round($employeeTargetTotal / $employeeCount, 2);
    }

    private function resolveConfiguredWorkdayFallback($targetRows): float
    {
        $targetRows = collect($targetRows)->map(function ($target) {
            return [
                'division' => $this->normalizeNullableFilterValue(data_get($target, 'division')),
                'branch' => $this->normalizeNullableFilterValue(data_get($target, 'branch')),
                'monthly_target' => (float) data_get($target, 'monthly_target', 21.00),
                'daily_target' => (float) data_get($target, 'daily_target', self::MAX_WORKDAY),
            ];
        })->values();

        if ($targetRows->isEmpty()) {
            return 21.00;
        }

        $genericTarget = $targetRows->first(function (array $target) {
            return $target['division'] === null && $target['branch'] === null;
        });

        if ($genericTarget !== null) {
            return round((float) $genericTarget['monthly_target'], 2);
        }

        return round((float) $targetRows->avg('monthly_target'), 2);
    }

    private function normalizeNullableFilterValue(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function prependEmptyFilterOption($options)
    {
        $normalizedOptions = collect($options)
            ->map(fn ($option) => trim((string) $option))
            ->filter(fn ($option) => filled($option))
            ->reject(fn ($option) => $option === self::EMPTY_FILTER_VALUE)
            ->values();

        return collect([self::EMPTY_FILTER_VALUE])->merge($normalizedOptions)->values();
    }

    private function resolveCompanyKey(?string $company): string
    {
        $companies = array_keys(config('services.smartpresence.companies', []));

        return $this->resolveAccessibleCompany($company, $companies);
    }

    private function companyConfig(string $companyKey): array
    {
        return config("services.smartpresence.companies.{$companyKey}", [
            'company_id' => 1,
            'label' => 'Servanda',
            'api_key' => null,
        ]);
    }

    private function summaryModel(string $companyKey): string
    {
        return match ($companyKey) {
            'gabe' => AttendanceSummaryGabe::class,
            'salus' => AttendanceSummarySalus::class,
            default => AttendanceSummaryServanda::class,
        };
    }

    private function hasValidApiKey(?string $apiKey): bool
    {
        if (blank($apiKey)) {
            return false;
        }

        return ! str_starts_with($apiKey, 'ISI_API_KEY_');
    }

    private function employeeLookupQuery(string $companyKey)
    {
        if ($companyKey === 'servanda') {
            return DB::table('employee_servanda as employee_master')
                ->select([
                    'employee_master.employee_no',
                    'employee_master.position',
                    'employee_master.status',
                    'employee_master.pay_freq',
                    DB::raw("
                        CASE
                            WHEN COALESCE(NULLIF(employee_master.site_area_ss, ''), NULLIF(employee_master.site_area_ss_bpp, '')) IS NOT NULL THEN 'Security'
                            WHEN COALESCE(NULLIF(employee_master.site_area_cfs, ''), NULLIF(employee_master.site_area_cs_bpp, '')) IS NOT NULL THEN 'Cleaning'
                            ELSE NULL
                        END as division
                    "),
                    DB::raw("COALESCE(NULLIF(employee_master.site_area_ss, ''), NULLIF(employee_master.site_area_cfs, ''), NULLIF(employee_master.site_area_cs_bpp, ''), NULLIF(employee_master.site_area_ss_bpp, '')) as area"),
                ])
                ->whereNotNull('employee_master.employee_no')
                ->where('employee_master.employee_no', '!=', '')
                ->distinct();
        }

        $table = match ($companyKey) {
            'gabe' => 'employee_gabe',
            'salus' => 'employee_salus',
            default => 'employee',
        };

        $areaColumn = match ($companyKey) {
            'gabe' => 'site_area_gabe',
            'salus' => 'site_area_salus',
            default => 'area',
        };

        return DB::table($table . ' as employee_master')
            ->select([
                'employee_master.employee_no',
                'employee_master.position',
                'employee_master.status',
                'employee_master.pay_freq',
                DB::raw('NULL as division'),
                DB::raw("NULLIF(employee_master.{$areaColumn}, '') as area"),
            ])
            ->whereNotNull('employee_master.employee_no')
            ->where('employee_master.employee_no', '!=', '')
            ->distinct();
    }

    private function applyPayFrequencyFilter($query, string $payFrequency): void
    {
        $normalized = strtolower(trim($payFrequency));

        if ($normalized === 'harian') {
            $query->whereRaw('LOWER(TRIM(employee_master.pay_freq)) IN (?, ?)', ['harian', 'daily']);
        }

        if ($normalized === 'bulanan') {
            $query->whereRaw('LOWER(TRIM(employee_master.pay_freq)) IN (?, ?)', ['bulanan', 'monthly']);
        }

        if ($normalized === 'tanpa data') {
            $query->where(function ($emptyQuery) {
                $emptyQuery
                    ->whereNull('employee_master.pay_freq')
                    ->orWhereRaw("TRIM(COALESCE(employee_master.pay_freq, '')) = ''")
                    ->orWhereRaw("LOWER(TRIM(COALESCE(employee_master.pay_freq, ''))) = '-'");
            });
        }
    }

    private function activeEmployeeCount(string $companyKey, ?string $division = null): int
    {
        $query = DB::query()
            ->fromSub($this->employeeLookupQuery($companyKey), 'employee_master')
            ->whereNotNull('employee_master.employee_no')
            ->where('employee_master.employee_no', '!=', '');

        $this->applyActiveStatusFilter($query, 'employee_master.status');

        if ($companyKey === 'servanda' && $division !== null) {
            $query->where('employee_master.division', $division);
        }

        return (int) $query->distinct()->count('employee_master.employee_no');
    }

    private function applyActiveStatusFilter($query, string $column = 'status'): void
    {
        $query->where(function ($statusQuery) use ($column) {
            $statusQuery
                ->whereRaw("LOWER(TRIM({$column})) IN (?, ?, ?, ?)", ['aktif', 'active', 'actived', 'enable'])
                ->orWhere(function ($activeQuery) use ($column) {
                    $activeQuery
                        ->whereRaw("LOWER(TRIM({$column})) like ?", ['%aktif%'])
                        ->whereRaw("LOWER(TRIM({$column})) NOT like ?", ['%tidak aktif%'])
                        ->whereRaw("LOWER(TRIM({$column})) NOT like ?", ['%non aktif%']);
                })
                ->orWhere(function ($activeQuery) use ($column) {
                    $activeQuery
                        ->whereRaw("LOWER(TRIM({$column})) like ?", ['%active%'])
                        ->whereRaw("LOWER(TRIM({$column})) NOT like ?", ['%inactive%']);
                });
        });
    }

    private function attendanceTone(float $attendanceRate): string
    {
        if ($attendanceRate >= 90) {
            return 'good';
        }

        if ($attendanceRate >= 75) {
            return 'warning';
        }

        return 'critical';
    }

    private function buildGroupedInsights($records, string $key)
    {
        return $records
            ->groupBy(function ($record) use ($key) {
                $value = trim((string) data_get($record, $key));

                return $value !== '' ? $value : 'Tanpa Data';
            })
            ->map(function ($group, $label) {
                $employeeCount = $group->pluck('employee_no')->filter()->unique()->count();
                $workdayTotal = $group->sum('workday_count');
                $presentTotal = $group->sum('presence_count');
                $absentTotal = $group->sum('absent_count');
                $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;
                $avgPresent = $employeeCount > 0 ? round($presentTotal / $employeeCount, 2) : 0;
                $avgAbsent = $employeeCount > 0 ? round($absentTotal / $employeeCount, 2) : 0;

                return [
                    'label' => $label,
                    'employee_count' => $employeeCount,
                    'attendance_rate' => $attendanceRate,
                    'avg_present' => $avgPresent,
                    'avg_absent' => $avgAbsent,
                    'tone' => $this->attendanceTone($attendanceRate),
                ];
            })
            ->sortBy([
                ['attendance_rate', 'asc'],
                ['avg_absent', 'desc'],
            ])
            ->take(5)
            ->values();
    }

    private function buildGroupedChartData($records, string $key)
    {
        return $records
            ->groupBy(function ($record) use ($key) {
                $value = trim((string) data_get($record, $key));

                return $value !== '' ? $value : 'Tanpa Data';
            })
            ->map(function ($group, $label) {
                $employeeCount = $group->pluck('employee_no')->filter()->unique()->count();
                $workdayTotal = (int) $group->sum('workday_count');
                $presentTotal = (int) $group->sum('presence_count');
                $absentTotal = (int) $group->sum('absent_count');
                $attendanceRate = $workdayTotal > 0 ? round(($presentTotal / $workdayTotal) * 100, 2) : 0;

                return [
                    'label' => $label,
                    'employee_count' => $employeeCount,
                    'present_total' => $presentTotal,
                    'absent_total' => $absentTotal,
                    'attendance_rate' => $attendanceRate,
                ];
            })
            ->sortByDesc('attendance_rate')
            ->values();
    }

    private function normalizePayFrequency(?string $payFrequency): string
    {
        $normalized = strtolower(trim((string) $payFrequency));

        return match ($normalized) {
            'daily', 'harian' => 'Harian',
            'monthly', 'bulanan' => 'Bulanan',
            default => $normalized !== '' ? ucwords($normalized) : 'Tanpa Data',
        };
    }

    private function resolveDivisionFilter(string $companyKey, ?string $division): ?string
    {
        return $this->resolveRoleBasedDivision($division, $companyKey);
    }

    private function resolveAttendanceSummaryDivisionFilter(string $companyKey, mixed $division): ?string
    {
        $normalized = trim((string) $division);

        if (
            $companyKey === 'servanda'
            && $normalized === self::EMPTY_FILTER_VALUE
            && $this->forcedDivisionByRole() === null
        ) {
            return self::EMPTY_FILTER_VALUE;
        }

        return $this->resolveDivisionFilter($companyKey, $normalized);
    }

}
