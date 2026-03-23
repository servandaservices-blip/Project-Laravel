<?php

namespace App\Http\Controllers;

use App\Services\AttendanceTargetStore;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AttendanceTargetController extends Controller
{
    private const MASTER_SITE_AREA_TABLE = 'faizal.master_site_areas';

    private const COMPANY_OPTIONS = [
        'servanda' => 'Servanda',
        'gabe' => 'Gabe',
        'salus' => 'Salus',
    ];

    public function index(Request $request, AttendanceTargetStore $attendanceTargetStore)
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
            ? $attendanceTargetStore->summaryTargets($selectedCompany, $selectedYear, $selectedBranch)
            : $attendanceTargetStore->editableTargets($selectedCompany, $selectedYear, $selectedDivision, $selectedBranch);

        return view('dashboard.attendance-targets', [
            'companyOptions' => self::COMPANY_OPTIONS,
            'selectedCompany' => $selectedCompany,
            'selectedDivision' => $selectedDivision,
            'selectedBranch' => $selectedBranch,
            'selectedYear' => $selectedYear,
            'yearOptions' => range((int) now()->year - 1, 2030),
            'divisionOptions' => AttendanceTargetStore::servandaDivisions(),
            'branchOptions' => $this->branchOptions($selectedCompany, $selectedDivision),
            'targets' => $targets,
            'canEditTargets' => $canEditTargets,
            'isServandaSummaryMode' => $isServandaSummaryMode,
            'scopeLabel' => $this->buildScopeLabel($selectedCompany, $selectedDivision, $selectedBranch, $selectedYear),
        ]);
    }

    public function store(Request $request, AttendanceTargetStore $attendanceTargetStore)
    {
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $selectedDivision = $selectedCompany === 'servanda'
            ? $this->normalizeNullableString($request->input('division'))
            : null;
        $selectedBranch = $this->normalizeNullableString($request->input('branch'));

        if ($selectedCompany === 'servanda' && $selectedDivision === null) {
            return redirect()
                ->route('settings.attendance-targets.index', [
                    'company' => $selectedCompany,
                    'branch' => $selectedBranch,
                    'year' => (int) $request->input('year', now()->year),
                ])
                ->withErrors(['division' => 'Pilih divisi terlebih dahulu. Mode Semua hanya menampilkan ringkasan otomatis dan tidak bisa diedit.']);
        }

        $validated = $request->validate([
            'company' => ['required', 'string'],
            'division' => $selectedCompany === 'servanda'
                ? ['required', 'string', 'in:' . implode(',', AttendanceTargetStore::servandaDivisions())]
                : ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'year' => ['required', 'integer', 'min:2024', 'max:2035'],
            'attendance_target' => ['required', 'array', 'size:12'],
            'attendance_target.*' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $attendanceTargetStore->save(
            $selectedCompany,
            (int) $validated['year'],
            $selectedDivision,
            $selectedBranch,
            $validated['attendance_target']
        );

        return redirect()
            ->route('settings.attendance-targets.index', [
                'company' => $selectedCompany,
                'division' => $selectedDivision,
                'branch' => $selectedBranch,
                'year' => (int) $validated['year'],
            ])
            ->with('success', 'Target Attendance berhasil diperbarui.');
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
