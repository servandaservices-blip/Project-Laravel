<?php

namespace App\Http\Controllers;

use App\Models\WorkdayTarget;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class WorkdayTargetController extends Controller
{
    private const MASTER_SITE_AREA_TABLE = 'faizal.master_site_areas';
    private const DEFAULT_MONTHLY_TARGET = 21.00;
    private const DEFAULT_DAILY_TARGET = 26.00;
    private const SERVANDA_DIVISION_OPTIONS = ['Cleaning', 'Security'];

    private const COMPANY_OPTIONS = [
        'servanda' => 'Servanda',
        'gabe' => 'Gabe',
        'salus' => 'Salus',
    ];

    public function index(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $selectedYear = (int) ($request->query('year') ?: now()->year);
        $selectedDivision = $selectedCompany === 'servanda'
            ? $this->normalizeNullableString($request->query('division'))
            : null;
        $selectedBranch = $this->normalizeNullableString($request->query('branch'));
        $isServandaSummaryMode = $selectedCompany === 'servanda' && $selectedDivision === null;
        $canEditTargets = ! $isServandaSummaryMode;

        $targets = $isServandaSummaryMode
            ? $this->buildServandaSummaryTargets($selectedCompany, $selectedYear, $selectedBranch)
            : $this->buildEditableTargets($selectedCompany, $selectedYear, $selectedDivision, $selectedBranch);

        return view('dashboard.workday-targets', [
            'companyOptions' => self::COMPANY_OPTIONS,
            'selectedCompany' => $selectedCompany,
            'selectedDivision' => $selectedDivision,
            'selectedBranch' => $selectedBranch,
            'selectedYear' => $selectedYear,
            'yearOptions' => range((int) now()->year - 1, 2030),
            'divisionOptions' => self::SERVANDA_DIVISION_OPTIONS,
            'branchOptions' => $this->branchOptions($selectedCompany, $selectedDivision),
            'targets' => $targets,
            'canEditTargets' => $canEditTargets,
            'isServandaSummaryMode' => $isServandaSummaryMode,
            'scopeLabel' => $this->buildScopeLabel($selectedCompany, $selectedDivision, $selectedBranch, $selectedYear),
        ]);
    }

    public function store(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $selectedDivision = $selectedCompany === 'servanda'
            ? $this->normalizeNullableString($request->input('division'))
            : null;
        $selectedBranch = $this->normalizeNullableString($request->input('branch'));

        if ($selectedCompany === 'servanda' && $selectedDivision === null) {
            return redirect()
                ->route('settings.workday-targets.index', [
                    'company' => $selectedCompany,
                    'branch' => $selectedBranch,
                    'year' => (int) $request->input('year', now()->year),
                ])
                ->withErrors(['division' => 'Pilih divisi terlebih dahulu. Mode Semua hanya menampilkan ringkasan otomatis dan tidak bisa diedit.']);
        }

        $validated = $request->validate([
            'company' => ['required', 'string'],
            'division' => $selectedCompany === 'servanda'
                ? ['required', 'string', 'in:' . implode(',', self::SERVANDA_DIVISION_OPTIONS)]
                : ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2024', 'max:2035'],
            'monthly_target' => ['required', 'array', 'size:12'],
            'monthly_target.*' => ['required', 'numeric', 'min:0', 'max:31'],
            'daily_target' => ['required', 'array', 'size:12'],
            'daily_target.*' => ['required', 'numeric', 'min:0', 'max:31'],
        ]);

        foreach (range(1, 12) as $month) {
            $query = WorkdayTarget::query()
                ->where('company', $selectedCompany)
                ->where('year', (int) $validated['year'])
                ->where('month', $month);

            $selectedDivision !== null
                ? $query->where('division', $selectedDivision)
                : $query->whereNull('division');

            $selectedBranch !== null
                ? $query->where('branch', $selectedBranch)
                : $query->whereNull('branch');

            $target = $query->first();

            $payload = [
                'company' => $selectedCompany,
                'division' => $selectedDivision,
                'branch' => $selectedBranch,
                'year' => (int) $validated['year'],
                'month' => $month,
                'monthly_target' => round((float) $validated['monthly_target'][$month], 2),
                'daily_target' => round((float) $validated['daily_target'][$month], 2),
            ];

            if ($target) {
                $target->update($payload);
            } else {
                WorkdayTarget::query()->create($payload);
            }
        }

        return redirect()
            ->route('settings.workday-targets.index', [
                'company' => $selectedCompany,
                'division' => $selectedDivision,
                'branch' => $selectedBranch,
                'year' => (int) $validated['year'],
            ])
            ->with('success', 'Target Workday berhasil diperbarui.');
    }

    private function resolveCompanyKey(?string $company): string
    {
        $company = strtolower(trim((string) $company));

        return array_key_exists($company, self::COMPANY_OPTIONS) ? $company : 'servanda';
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function branchOptions(string $company, ?string $division = null)
    {
        return DB::table(self::MASTER_SITE_AREA_TABLE)
            ->where('company', $company)
            ->when($company === 'servanda' && $division !== null, fn ($query) => $query->where('division', $division))
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->orderBy('branch')
            ->distinct()
            ->pluck('branch');
    }

    private function buildEditableTargets(string $company, int $year, ?string $division = null, ?string $branch = null)
    {
        return collect(range(1, 12))
            ->map(function (int $month) use ($company, $division, $branch, $year) {
                $target = WorkdayTarget::query()
                    ->where('company', $company)
                    ->where('year', $year)
                    ->where('month', $month)
                    ->when(
                        $company === 'servanda',
                        fn ($query) => $query->where('division', $division),
                        fn ($query) => $query->when(
                            $division !== null,
                            fn ($divisionQuery) => $divisionQuery->where('division', $division),
                            fn ($divisionQuery) => $divisionQuery->whereNull('division')
                        )
                    )
                    ->when($branch !== null, fn ($query) => $query->where('branch', $branch), fn ($query) => $query->whereNull('branch'))
                    ->first();

                return [
                    'month' => $month,
                    'label' => Carbon::create()->month($month)->translatedFormat('F'),
                    'monthly_target' => $target?->monthly_target !== null
                        ? number_format((float) $target->monthly_target, 2, '.', '')
                        : number_format(self::DEFAULT_MONTHLY_TARGET, 2, '.', ''),
                    'daily_target' => $target?->daily_target !== null
                        ? number_format((float) $target->daily_target, 2, '.', '')
                        : number_format(self::DEFAULT_DAILY_TARGET, 2, '.', ''),
                    'division_breakdown' => [],
                ];
            })
            ->values();
    }

    private function buildServandaSummaryTargets(string $company, int $year, ?string $branch = null)
    {
        $summaryRows = WorkdayTarget::query()
            ->where('company', $company)
            ->where('year', $year)
            ->whereIn('division', self::SERVANDA_DIVISION_OPTIONS)
            ->when($branch !== null, fn ($query) => $query->where('branch', $branch), fn ($query) => $query->whereNull('branch'))
            ->get()
            ->groupBy('month');

        return collect(range(1, 12))
            ->map(function (int $month) use ($summaryRows) {
                $rows = collect($summaryRows->get($month, []))
                    ->keyBy(fn ($target) => trim((string) $target->division));

                $divisionBreakdown = collect(self::SERVANDA_DIVISION_OPTIONS)
                    ->map(function (string $division) use ($rows) {
                        $row = $rows->get($division);

                        return [
                            'division' => $division,
                            'monthly_target' => $row?->monthly_target !== null
                                ? round((float) $row->monthly_target, 2)
                                : null,
                            'daily_target' => $row?->daily_target !== null
                                ? round((float) $row->daily_target, 2)
                                : null,
                        ];
                    })
                    ->values();

                $availableMonthlyTargets = $divisionBreakdown
                    ->pluck('monthly_target')
                    ->filter(fn ($value) => $value !== null)
                    ->values();
                $availableDailyTargets = $divisionBreakdown
                    ->pluck('daily_target')
                    ->filter(fn ($value) => $value !== null)
                    ->values();

                return [
                    'month' => $month,
                    'label' => Carbon::create()->month($month)->translatedFormat('F'),
                    'monthly_target' => number_format(
                        $availableMonthlyTargets->isNotEmpty()
                            ? round((float) $availableMonthlyTargets->avg(), 2)
                            : self::DEFAULT_MONTHLY_TARGET,
                        2,
                        '.',
                        ''
                    ),
                    'daily_target' => number_format(
                        $availableDailyTargets->isNotEmpty()
                            ? round((float) $availableDailyTargets->avg(), 2)
                            : self::DEFAULT_DAILY_TARGET,
                        2,
                        '.',
                        ''
                    ),
                    'division_breakdown' => $divisionBreakdown->all(),
                ];
            })
            ->values();
    }

    private function buildScopeLabel(string $company, ?string $division, ?string $branch, int $year): string
    {
        $segments = [self::COMPANY_OPTIONS[$company] ?? ucfirst($company)];

        if ($company === 'servanda') {
            $segments[] = $division ?? 'Semua Divisi';
        }

        $segments[] = $branch ?? 'Semua Cabang';
        $segments[] = (string) $year;

        return implode(' > ', $segments);
    }
}
