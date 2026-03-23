<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\InteractsWithUserAccess;
use App\Models\User;
use App\Services\SiteAreaSyncService;
use Illuminate\Http\UploadedFile;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    use InteractsWithUserAccess;

    private const MASTER_SITE_AREA_TABLE = 'faizal.master_site_areas';
    private const EMPTY_FILTER_VALUE = '__EMPTY__';

    private const COMPANY_OPTIONS = [
        'servanda' => [
            'label' => 'Servanda',
            'company_id' => 1,
            'table' => 'employee_servanda',
        ],
        'gabe' => [
            'label' => 'Gabe',
            'company_id' => 4,
            'table' => 'employee_gabe',
        ],
        'salus' => [
            'label' => 'Salus',
            'company_id' => 3,
            'table' => 'employee_salus',
        ],
    ];

    public function index(Request $request)
    {
        if (! $request->has('status')) {
            $query = $request->query();
            $query['status'] = 'Aktif';

            return redirect()->route('employee.index', $query);
        }

        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->query('division'));
        $companyName = self::COMPANY_OPTIONS[$selectedCompany]['label'];
        $query = $this->newEmployeeQuery($selectedCompany, $request);

        $this->applyFilters($query, $request, $selectedCompany, $selectedDivision);

        $employees = $query->orderBy('nama')->get();
        $employees = $this->filterByContractStatus($employees, $request->input('contract_status', []));
        $employees = $this->paginateEmployees($employees, $request);

        $positions = $this->appendEmptyFilterOption(
            $this->newEmployeeQuery($selectedCompany, $request)
            ->whereNotNull('position')
            ->where('position', '!=', '')
            ->orderBy('position')
            ->pluck('position')
            ->map(fn ($position) => trim((string) $position))
            ->filter(fn ($position) => filled($position))
            ->unique(fn ($position) => strtolower($position))
            ->values()
        );

        $areas = $this->appendEmptyFilterOption($selectedCompany === 'servanda'
            ? $this->getSiteAreaOptions($selectedCompany, $selectedDivision)
            : $this->getAreaOptions($selectedCompany));
        $branches = $this->appendEmptyFilterOption($this->getBranchOptions($selectedCompany, $selectedDivision));
        $areaManagers = $this->appendEmptyFilterOption($this->getSiteAreaManagerOptions($selectedCompany, $selectedDivision, 'area_manager'));
        $operationManagers = $this->appendEmptyFilterOption($this->getSiteAreaManagerOptions($selectedCompany, $selectedDivision, 'operation_manager'));

        $payFrequencies = collect([self::EMPTY_FILTER_VALUE, 'Harian', 'Bulanan']);

        $statuses = collect([self::EMPTY_FILTER_VALUE, 'Aktif', 'Tidak Aktif']);

        $contractStatuses = collect([
            self::EMPTY_FILTER_VALUE,
            'Aman',
            'Perhatian',
            'Menjelang Berakhir',
            'Expired',
        ]);

        return view('dashboard.employee', compact(
            'employees',
            'positions',
            'areas',
            'branches',
            'areaManagers',
            'operationManagers',
            'payFrequencies',
            'statuses',
            'contractStatuses',
            'selectedCompany',
            'companyName',
            'selectedDivision'
        ));
    }

    public function workRealization(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $selectedDivision = $this->resolveWorkRealizationDivisionFilter($selectedCompany, $request->query('division'));
        $siteAreaDivision = $selectedDivision !== self::EMPTY_FILTER_VALUE ? $selectedDivision : null;
        $selectedBranch = trim((string) $request->query('branch', ''));
        $selectedPayFrequency = trim((string) $request->query('pay_freq', ''));
        $selectedPosition = trim((string) $request->query('position', ''));
        $selectedArea = trim((string) $request->query('area', ''));
        $selectedAreaManager = trim((string) $request->query('area_manager', ''));
        $selectedOperationManager = trim((string) $request->query('operation_manager', ''));
        $companyName = self::COMPANY_OPTIONS[$selectedCompany]['label'];
        $companyOptions = self::COMPANY_OPTIONS;
        $branches = $this->appendEmptyFilterOption($this->getBranchOptions($selectedCompany, $siteAreaDivision));
        $payFrequencies = collect(['Harian', 'Bulanan', 'Tanpa Data']);

        $optionQuery = DB::query()
            ->fromSub($this->newEmployeeQuery($selectedCompany, $request), 'employees');

        $this->applyWorkRealizationOptionFilters(
            $optionQuery,
            $selectedCompany,
            $selectedDivision,
            $selectedBranch,
            $selectedPayFrequency,
            $selectedPosition,
            $selectedArea,
            $selectedAreaManager,
            $selectedOperationManager
        );

        $positions = $this->appendEmptyFilterOption(
            (clone $optionQuery)
                ->whereNotNull('position')
                ->where('position', '!=', '')
                ->orderBy('position')
                ->pluck('position')
                ->map(fn ($position) => trim((string) $position))
                ->filter(fn ($position) => filled($position))
                ->unique(fn ($position) => strtolower($position))
                ->values()
        );
        $areas = $this->appendEmptyFilterOption(
            (clone $optionQuery)
                ->whereNotNull('area')
                ->where('area', '!=', '')
                ->orderBy('area')
                ->pluck('area')
                ->map(fn ($area) => trim((string) $area))
                ->filter(fn ($area) => filled($area))
                ->unique(fn ($area) => strtolower($area))
                ->values()
        );
        $areaManagers = $this->appendEmptyFilterOption(
            $this->getSiteAreaManagerOptions(
                $selectedCompany,
                $siteAreaDivision,
                'area_manager'
            )
        );
        $operationManagers = $this->appendEmptyFilterOption(
            $this->getSiteAreaManagerOptions(
                $selectedCompany,
                $siteAreaDivision,
                'operation_manager'
            )
        );
        $divisionOptions = collect([self::EMPTY_FILTER_VALUE, 'Cleaning', 'Security']);

        return view('dashboard.employee-work-realization', compact(
            'selectedCompany',
            'selectedDivision',
            'selectedBranch',
            'selectedPayFrequency',
            'selectedPosition',
            'selectedArea',
            'selectedAreaManager',
            'selectedOperationManager',
            'companyName',
            'companyOptions',
            'branches',
            'payFrequencies',
            'positions',
            'areas',
            'areaManagers',
            'operationManagers',
            'divisionOptions'
        ));
    }

    public function updateEmployee(Request $request, string $company, string $employeeNo)
    {
        $selectedCompany = $this->resolveCompanyKey($company);
        $table = self::COMPANY_OPTIONS[$selectedCompany]['table'];

        $employee = DB::table($table)
            ->where('employee_no', $employeeNo)
            ->first();

        if (! $employee) {
            return redirect()
                ->to($request->input('redirect_url', route('employee.index', ['company' => $selectedCompany])))
                ->with('error', 'Data pegawai tidak ditemukan.');
        }

        $rules = [
            'position' => ['nullable', 'string', 'max:150'],
            'pay_freq' => ['nullable', 'string', 'max:50'],
            'status' => ['nullable', 'string', 'max:100'],
            'redirect_url' => ['nullable', 'string'],
        ];

        if ($selectedCompany === 'servanda') {
            $rules = array_merge($rules, [
                'site_area_ss' => ['nullable', 'string', 'max:150'],
                'site_area_cfs' => ['nullable', 'string', 'max:150'],
                'site_area_cs_bpp' => ['nullable', 'string', 'max:150'],
                'site_area_ss_bpp' => ['nullable', 'string', 'max:150'],
            ]);
        } else {
            $rules['area'] = ['nullable', 'string', 'max:150'];
        }

        $validated = $request->validate($rules);

        $payload = [
            'position' => $this->nullableTrimmed($validated['position'] ?? null),
            'pay_freq' => $this->nullableTrimmed($validated['pay_freq'] ?? null),
            'status' => $this->nullableTrimmed($validated['status'] ?? null),
        ];

        if ($selectedCompany === 'servanda') {
            $payload = array_merge($payload, [
                'site_area_ss' => $this->nullableTrimmed($validated['site_area_ss'] ?? null),
                'site_area_cfs' => $this->nullableTrimmed($validated['site_area_cfs'] ?? null),
                'site_area_cs_bpp' => $this->nullableTrimmed($validated['site_area_cs_bpp'] ?? null),
                'site_area_ss_bpp' => $this->nullableTrimmed($validated['site_area_ss_bpp'] ?? null),
            ]);
        } else {
            $payload[$this->companyAreaColumn($selectedCompany)] = $this->nullableTrimmed($validated['area'] ?? null);
        }

        DB::table($table)
            ->where('employee_no', $employeeNo)
            ->update($payload);

        $redirectUrl = trim((string) $request->input('redirect_url', ''));

        return redirect()
            ->to($redirectUrl !== '' ? $redirectUrl : route('employee.index', ['company' => $selectedCompany]))
            ->with('success', 'Data pegawai berhasil diperbarui.');
    }

    public function siteArea(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $companyName = self::COMPANY_OPTIONS[$selectedCompany]['label'];
        $search = trim((string) $request->query('search', ''));
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->query('division'));
        $selectedBranch = trim((string) $request->query('branch', ''));
        $selectedAreaManager = trim((string) $request->query('area_manager', ''));
        $selectedOperationManager = trim((string) $request->query('operation_manager', ''));
        $selectedStatus = trim((string) $request->query('status', ''));
        $perPage = (int) $request->query('per_page', 10);

        if (! in_array($perPage, [10, 50, 100], true)) {
            $perPage = 10;
        }

        $siteAreaQuery = $this->siteAreaMasterQuery($selectedCompany, $selectedDivision)
            ->when($selectedBranch !== '', fn ($query) => $query->where('branch', $selectedBranch))
            ->when($selectedAreaManager !== '', fn ($query) => $query->where('area_manager', $selectedAreaManager))
            ->when($selectedOperationManager !== '', fn ($query) => $query->where('operation_manager', $selectedOperationManager))
            ->when($selectedStatus !== '', fn ($query) => $query->where('status', $selectedStatus))
            ->orderBy('area_name');

        $siteAreas = $siteAreaQuery
            ->paginate($perPage)
            ->withQueryString();

        return view('dashboard.site-area', [
            'selectedCompany' => $selectedCompany,
            'companyName' => $companyName,
            'companyOptions' => self::COMPANY_OPTIONS,
            'siteAreas' => $siteAreas,
            'search' => $search,
            'selectedDivision' => $selectedDivision,
            'selectedBranch' => $selectedBranch,
            'selectedAreaManager' => $selectedAreaManager,
            'selectedOperationManager' => $selectedOperationManager,
            'selectedStatus' => $selectedStatus,
            'selectedPerPage' => $perPage,
            'branchOptions' => $this->cachedBranchOptions($selectedCompany, $selectedDivision),
            'areaManagerOptions' => $this->getUserOptionsByPositions([
                'Area Manager (AM)',
                'Area Commander (AC)',
            ]),
            'operationManagerOptions' => $this->getUserOptionsByPositions([
                'Operation Commander (OC)',
                'Operation Manager (OM)',
                'Senior Operation Commander (SOC)',
                'Senior Operation Manager (SOM)',
            ]),
            'totalAreas' => $siteAreas->total(),
        ]);
    }

    public function storeSiteArea(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $selectedDivision = $this->resolveStoredSiteAreaDivision($selectedCompany, $request->input('division'));

        $validated = $request->validate([
            'company' => ['required'],
            'area_name' => ['required', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:150'],
            'area_manager' => ['nullable', 'string', 'max:150'],
            'operation_manager' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:Aktif,Tidak Aktif'],
        ]);

        if ($selectedCompany === 'servanda' && $selectedDivision === null) {
            return redirect()
                ->route('site-area.index', ['company' => $selectedCompany])
                ->with('error', 'Pilih divisi terlebih dahulu sebelum menambahkan Site Area Servanda.');
        }

        DB::table(self::MASTER_SITE_AREA_TABLE)->insert([
            'company' => $selectedCompany,
            'area_name' => trim((string) $validated['area_name']),
            'division' => $selectedDivision ?? 'General',
            'branch' => $selectedCompany === 'servanda'
                ? $this->nullableTrimmed($request->input('branch'))
                : $this->nullableTrimmed($validated['branch'] ?? null),
            'area_manager' => $this->nullableTrimmed($validated['area_manager'] ?? null),
            'operation_manager' => $this->nullableTrimmed($validated['operation_manager'] ?? null),
            'status' => $validated['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('site-area.index', array_filter([
                'company' => $selectedCompany,
                'division' => $selectedCompany === 'servanda' ? $selectedDivision : null,
            ]))
            ->with('success', 'Site Area berhasil ditambahkan.');
    }

    public function updateSiteArea(Request $request, int $id)
    {
        $siteArea = DB::table(self::MASTER_SITE_AREA_TABLE)->where('id', $id)->first();

        if (! $siteArea) {
            return redirect()->route('site-area.index')->with('error', 'Data Site Area tidak ditemukan.');
        }

        $selectedCompany = $this->resolveCompanyKey($request->input('company', $siteArea->company));
        $selectedDivision = $this->resolveStoredSiteAreaDivision($selectedCompany, $request->input('division', $siteArea->division));

        $validated = $request->validate([
            'company' => ['required'],
            'area_name' => ['required', 'string', 'max:150'],
            'branch' => ['nullable', 'string', 'max:150'],
            'area_manager' => ['nullable', 'string', 'max:150'],
            'operation_manager' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'in:Aktif,Tidak Aktif'],
        ]);

        DB::table(self::MASTER_SITE_AREA_TABLE)
            ->where('id', $id)
            ->update([
                'company' => $selectedCompany,
                'area_name' => trim((string) $validated['area_name']),
                'division' => $selectedDivision ?? 'General',
                'branch' => $selectedCompany === 'servanda'
                    ? $this->nullableTrimmed($request->input('branch', $siteArea->branch))
                    : $this->nullableTrimmed($validated['branch'] ?? null),
                'area_manager' => $this->nullableTrimmed($validated['area_manager'] ?? null),
                'operation_manager' => $this->nullableTrimmed($validated['operation_manager'] ?? null),
                'status' => $validated['status'],
                'updated_at' => now(),
            ]);

        return redirect()
            ->route('site-area.index', array_filter([
                'company' => $selectedCompany,
                'division' => $selectedCompany === 'servanda' ? $selectedDivision : null,
            ]))
            ->with('success', 'Site Area berhasil diperbarui.');
    }

    public function destroySiteArea(Request $request, int $id)
    {
        $siteArea = DB::table(self::MASTER_SITE_AREA_TABLE)->where('id', $id)->first();

        if (! $siteArea) {
            return redirect()->route('site-area.index')->with('error', 'Data Site Area tidak ditemukan.');
        }

        DB::table(self::MASTER_SITE_AREA_TABLE)->where('id', $id)->delete();

        return redirect()
            ->route('site-area.index', array_filter([
                'company' => $this->resolveCompanyKey($request->input('company', $siteArea->company)),
                'division' => $request->input('division'),
            ]))
            ->with('success', 'Site Area berhasil dihapus.');
    }

    public function syncSiteArea(Request $request, SiteAreaSyncService $siteAreaSyncService)
    {
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->input('division'));

        $siteAreaSyncService->sync($selectedCompany);

        return redirect()
            ->route('site-area.index', array_filter([
                'company' => $selectedCompany,
                'division' => $selectedCompany === 'servanda' ? $selectedDivision : null,
            ]))
            ->with('success', 'Sync Site Area berhasil. Nama area dan division otomatis diperbarui dari data master employee.');
    }

    public function exportSiteArea(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $companyName = self::COMPANY_OPTIONS[$selectedCompany]['label'];
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->query('division'));
        $selectedBranch = trim((string) $request->query('branch', ''));
        $selectedAreaManager = trim((string) $request->query('area_manager', ''));
        $selectedOperationManager = trim((string) $request->query('operation_manager', ''));
        $selectedStatus = trim((string) $request->query('status', ''));

        $siteAreas = $this->siteAreaMasterQuery($selectedCompany, $selectedDivision)
            ->when($selectedBranch !== '', fn ($query) => $query->where('branch', $selectedBranch))
            ->when($selectedAreaManager !== '', fn ($query) => $query->where('area_manager', $selectedAreaManager))
            ->when($selectedOperationManager !== '', fn ($query) => $query->where('operation_manager', $selectedOperationManager))
            ->when($selectedStatus !== '', fn ($query) => $query->where('status', $selectedStatus))
            ->orderBy('area_name')
            ->get();

        $fileName = 'site-area-export-' . $selectedCompany . '-' . now()->format('Y-m-d_H-i-s') . '.xls';
        $html = view('dashboard.site-area-export', compact(
            'siteAreas',
            'selectedCompany',
            'companyName',
            'selectedDivision',
            'selectedBranch'
        ))->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportSiteAreaTemplate(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->query('division'));
        $selectedBranch = trim((string) $request->query('branch', ''));
        $selectedAreaManager = trim((string) $request->query('area_manager', ''));
        $selectedOperationManager = trim((string) $request->query('operation_manager', ''));
        $selectedStatus = trim((string) $request->query('status', ''));

        $siteAreas = $this->siteAreaMasterQuery($selectedCompany, $selectedDivision)
            ->when($selectedBranch !== '', fn ($query) => $query->where('branch', $selectedBranch))
            ->when($selectedAreaManager !== '', fn ($query) => $query->where('area_manager', $selectedAreaManager))
            ->when($selectedOperationManager !== '', fn ($query) => $query->where('operation_manager', $selectedOperationManager))
            ->when($selectedStatus !== '', fn ($query) => $query->where('status', $selectedStatus))
            ->orderBy('area_name')
            ->get([
                'id',
                'company',
                'area_name',
                'division',
                'branch',
                'area_manager',
                'operation_manager',
                'status',
            ]);

        $rows = $siteAreas->map(fn ($siteArea) => [
            'id' => $siteArea->id,
            'company' => $siteArea->company,
            'area_name' => $siteArea->area_name,
            'division' => $siteArea->division,
            'branch' => $siteArea->branch,
            'area_manager' => $siteArea->area_manager,
            'operation_manager' => $siteArea->operation_manager,
            'status' => $siteArea->status,
        ])->all();

        $fileName = 'site-area-template-' . $selectedCompany . '-' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');

            fprintf($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($handle, ['id', 'company', 'area_name', 'division', 'branch', 'area_manager', 'operation_manager', 'status']);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function importSiteArea(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->input('division'));
        $selectedBranch = trim((string) $request->input('branch', ''));
        $selectedAreaManager = trim((string) $request->input('area_manager', ''));
        $selectedOperationManager = trim((string) $request->input('operation_manager', ''));
        $selectedStatus = trim((string) $request->input('status', ''));

        $validated = $request->validate([
            'company' => ['required'],
            'division' => ['nullable', 'string'],
            'branch' => ['nullable', 'string'],
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        $rows = $this->parseSiteAreaImportFile($validated['import_file']);

        if ($rows === []) {
            return redirect()
                ->route('site-area.index', array_filter([
                'company' => $selectedCompany,
                'division' => $selectedCompany === 'servanda' ? $selectedDivision : null,
                'branch' => $selectedBranch ?: null,
                'area_manager' => $selectedAreaManager ?: null,
                'operation_manager' => $selectedOperationManager ?: null,
                'status' => $selectedStatus ?: null,
            ]))
            ->with('error', 'File import kosong atau format tidak terbaca.');
        }

        $successCount = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2;

            $id = (int) ($row['id'] ?? 0);
            $company = strtolower(trim((string) ($row['company'] ?? '')));
            $areaName = trim((string) ($row['area_name'] ?? ''));
            $division = $this->nullableTrimmed($row['division'] ?? null);
            $branch = $this->nullableTrimmed($row['branch'] ?? null);
            $areaManager = $this->nullableTrimmed($row['area_manager'] ?? null);
            $operationManager = $this->nullableTrimmed($row['operation_manager'] ?? null);
            $status = trim((string) ($row['status'] ?? ''));

            if ($id <= 0) {
                $errors[] = "Baris {$rowNumber}: kolom id wajib diisi.";
                continue;
            }

            if ($company === '' || ! array_key_exists($company, self::COMPANY_OPTIONS)) {
                $errors[] = "Baris {$rowNumber}: company tidak valid.";
                continue;
            }

            if ($areaName === '') {
                $errors[] = "Baris {$rowNumber}: area_name wajib diisi.";
                continue;
            }

            if (! in_array($status, ['Aktif', 'Tidak Aktif'], true)) {
                $errors[] = "Baris {$rowNumber}: status harus Aktif atau Tidak Aktif.";
                continue;
            }

            if ($company === 'servanda' && $division !== null && ! in_array($division, ['Cleaning', 'Security'], true)) {
                $errors[] = "Baris {$rowNumber}: division Servanda harus Cleaning atau Security.";
                continue;
            }

            $siteArea = DB::table(self::MASTER_SITE_AREA_TABLE)
                ->where('id', $id)
                ->where('company', $company)
                ->first();

            if (! $siteArea) {
                $errors[] = "Baris {$rowNumber}: data site area tidak ditemukan.";
                continue;
            }

            DB::table(self::MASTER_SITE_AREA_TABLE)
                ->where('id', $id)
                ->update([
                    'area_name' => $areaName,
                    'division' => $company === 'servanda' ? ($division ?? $siteArea->division ?? 'General') : 'General',
                    'branch' => $branch,
                    'area_manager' => $areaManager,
                    'operation_manager' => $operationManager,
                    'status' => $status,
                    'updated_at' => now(),
                ]);

            $successCount++;
        }

        $messages = [];

        if ($successCount > 0) {
            $messages[] = $successCount . ' data berhasil diupdate';
        }

        if ($errors !== []) {
            $messages[] = count($errors) . ' data gagal';
            $messages[] = implode(' ', array_slice($errors, 0, 3));
        }

        return redirect()
            ->route('site-area.index', array_filter([
                'company' => $selectedCompany,
                'division' => $selectedCompany === 'servanda' ? $selectedDivision : null,
                'branch' => $selectedBranch ?: null,
                'area_manager' => $selectedAreaManager ?: null,
                'operation_manager' => $selectedOperationManager ?: null,
                'status' => $selectedStatus ?: null,
            ]))
            ->with($errors === [] ? 'success' : 'error', implode('. ', $messages));
    }

    public function export(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $companyName = self::COMPANY_OPTIONS[$selectedCompany]['label'];
        $query = $this->newEmployeeQuery($selectedCompany, $request);

        $this->applyFilters($query, $request, $selectedCompany);

        $employees = $query->orderBy('nama')->get();
        $employees = $this->filterByContractStatus($employees, $request->input('contract_status', []));

        $fileName = 'employee-export-' . now()->format('Y-m-d_H-i-s') . '.xls';
        $html = view('dashboard.employee-export', compact('employees', 'selectedCompany', 'companyName'))->render();

        return response($html, 200, [
            'Content-Type' => 'application/vnd.ms-excel; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function exportTemplate(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->query('company'));
        $fileName = 'employee-template-' . $selectedCompany . '-' . now()->format('Y-m-d_H-i-s') . '.csv';
        $handle = fopen('php://temp', 'r+');
        $selectedDivision = $this->resolveServandaDivisionFilter($selectedCompany, $request->query('division'));
        $query = $this->newEmployeeQuery($selectedCompany, $request);

        $this->applyFilters($query, $request, $selectedCompany, $selectedDivision);

        $employees = $query->orderBy('nama')->get();
        $employees = $this->filterByContractStatus($employees, $request->input('contract_status', []));
        $headers = $this->employeeImportHeaders($selectedCompany);

        fputcsv($handle, $headers);

        foreach ($employees as $employee) {
            $row = [
                $employee->employee_no ?? '',
                $employee->nama ?? '',
                $employee->position ?? '',
                $employee->pay_freq ?? '',
                $employee->status ?? '',
            ];

            if ($selectedCompany === 'servanda') {
                $row = array_merge($row, [
                    $employee->site_area_ss ?? '',
                    $employee->site_area_cfs ?? '',
                    $employee->site_area_cs_bpp ?? '',
                    $employee->site_area_ss_bpp ?? '',
                ]);
            } else {
                $row[] = $employee->area ?? '';
            }

            fputcsv($handle, $row);
        }

        rewind($handle);
        $content = stream_get_contents($handle) ?: '';
        fclose($handle);

        return response($content, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    public function import(Request $request)
    {
        $selectedCompany = $this->resolveCompanyKey($request->input('company'));
        $validated = $request->validate([
            'company' => ['required', 'string'],
            'import_file' => ['required', 'file', 'mimes:csv,txt,xlsx'],
        ]);

        /** @var UploadedFile $file */
        $file = $validated['import_file'];
        $rows = $this->parseEmployeeImportFile($file);

        if ($rows === []) {
            return redirect()
                ->route('employee.index', ['company' => $selectedCompany])
                ->with('error', 'File import kosong atau formatnya tidak valid.');
        }

        $table = self::COMPANY_OPTIONS[$selectedCompany]['table'];
        $allowedHeaders = collect($this->employeeImportHeaders($selectedCompany));
        $successCount = 0;
        $errorMessages = [];

        foreach ($rows as $index => $row) {
            $normalizedRow = collect($row)
                ->mapWithKeys(fn ($value, $key) => [trim((string) $key) => $value])
                ->only($allowedHeaders)
                ->map(fn ($value) => is_string($value) ? trim($value) : $value)
                ->all();

            $employeeNo = trim((string) ($normalizedRow['employee_no'] ?? ''));

            if ($employeeNo === '') {
                $errorMessages[] = 'Baris ' . ($index + 2) . ': employee_no wajib diisi.';
                continue;
            }

            $existingEmployee = DB::table($table)
                ->where('employee_no', $employeeNo)
                ->first();

            if (! $existingEmployee) {
                $errorMessages[] = 'Baris ' . ($index + 2) . ': employee_no ' . $employeeNo . ' tidak ditemukan.';
                continue;
            }

            $payload = [
                'nama' => $this->nullableImportValue($normalizedRow['nama'] ?? null),
                'position' => $this->nullableImportValue($normalizedRow['position'] ?? null),
                'pay_freq' => $this->nullableImportValue($normalizedRow['pay_freq'] ?? null),
                'status' => $this->nullableImportValue($normalizedRow['status'] ?? null),
            ];

            if ($selectedCompany === 'servanda') {
                $payload = array_merge($payload, [
                    'site_area_ss' => $this->nullableImportValue($normalizedRow['site_area_ss'] ?? null),
                    'site_area_cfs' => $this->nullableImportValue($normalizedRow['site_area_cfs'] ?? null),
                    'site_area_cs_bpp' => $this->nullableImportValue($normalizedRow['site_area_cs_bpp'] ?? null),
                    'site_area_ss_bpp' => $this->nullableImportValue($normalizedRow['site_area_ss_bpp'] ?? null),
                ]);
            } else {
                $payload[$this->companyAreaColumn($selectedCompany)] = $this->nullableImportValue($normalizedRow['area'] ?? null);
            }

            DB::table($table)
                ->where('employee_no', $employeeNo)
                ->update($payload);

            $successCount++;
        }

        $messages = [];

        if ($successCount > 0) {
            $messages[] = $successCount . ' data pegawai berhasil diimport.';
        }

        if ($errorMessages !== []) {
            $messages[] = count($errorMessages) . ' baris gagal.';
            $messages[] = implode(' ', array_slice($errorMessages, 0, 3));
        }

        return redirect()
            ->route('employee.index', ['company' => $selectedCompany])
            ->with($errorMessages === [] ? 'success' : ($successCount > 0 ? 'success' : 'error'), implode(' ', $messages));
    }

    private function applyFilters($query, Request $request, string $selectedCompany, ?string $selectedDivision = null): void
    {
        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search, $selectedCompany) {
                $q->where('nama', 'like', "%{$search}%")
                    ->orWhere('employee_no', 'like', "%{$search}%")
                    ->orWhere('position', 'like', "%{$search}%");

                if ($selectedCompany === 'servanda') {
                    foreach ($this->servandaAreaColumns() as $column) {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                } else {
                    $q->orWhereRaw($this->companyAreaExpression($selectedCompany) . ' like ?', ["%{$search}%"]);
                }
            });
        }

        if ($selectedCompany === 'servanda' && $selectedDivision !== null) {
            $query->where(function ($divisionQuery) use ($selectedDivision) {
                if ($selectedDivision === 'Security') {
                    $divisionQuery
                        ->whereNotNull('site_area_ss')
                        ->where('site_area_ss', '!=', '')
                        ->orWhere(function ($securityBppQuery) {
                            $securityBppQuery
                                ->whereNotNull('site_area_ss_bpp')
                                ->where('site_area_ss_bpp', '!=', '');
                        });
                }

                if ($selectedDivision === 'Cleaning') {
                    $divisionQuery
                        ->whereNotNull('site_area_cfs')
                        ->where('site_area_cfs', '!=', '')
                        ->orWhere(function ($cleaningBppQuery) {
                            $cleaningBppQuery
                                ->whereNotNull('site_area_cs_bpp')
                                ->where('site_area_cs_bpp', '!=', '');
                        });
                }
            });
        }

        $positions = collect($request->input('position', []))
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($positions->isNotEmpty()) {
            $this->applyEqualityOrEmptyFilter($query, 'position', $positions->all());
        }

        $areas = collect($request->input('area', []))
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($areas->isNotEmpty()) {
            if ($selectedCompany === 'servanda') {
                $this->applyServandaAreaFilter($query, $areas->all());
            } else {
                $this->applyRawExpressionOrEmptyFilter($query, $this->companyAreaExpression($selectedCompany), $areas->all());
            }
        }

        $branches = collect($request->input('branch', []))
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($branches->isNotEmpty()) {
            $areaExpression = $selectedCompany === 'servanda'
                ? $this->servandaSelectedAreaExpression($request)
                : $this->companyAreaExpression($selectedCompany);

            $this->applySiteAreaRelationFilter(
                $query,
                $selectedCompany,
                $selectedDivision,
                $areaExpression,
                'branch',
                $branches->all()
            );
        }

        $selectedAreaManager = trim((string) $request->input('area_manager', ''));

        if ($selectedAreaManager !== '') {
            $areaExpression = $selectedCompany === 'servanda'
                ? $this->servandaSelectedAreaExpression($request)
                : $this->companyAreaExpression($selectedCompany);

            $this->applySiteAreaRelationFilter(
                $query,
                $selectedCompany,
                $selectedDivision,
                $areaExpression,
                'area_manager',
                [$selectedAreaManager]
            );
        }

        $selectedOperationManager = trim((string) $request->input('operation_manager', ''));

        if ($selectedOperationManager !== '') {
            $areaExpression = $selectedCompany === 'servanda'
                ? $this->servandaSelectedAreaExpression($request)
                : $this->companyAreaExpression($selectedCompany);

            $this->applySiteAreaRelationFilter(
                $query,
                $selectedCompany,
                $selectedDivision,
                $areaExpression,
                'operation_manager',
                [$selectedOperationManager]
            );
        }

        $payFrequencies = collect($request->input('pay_freq', []))
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($payFrequencies->isNotEmpty()) {
            $query->where(function ($payQuery) use ($payFrequencies) {
                foreach ($payFrequencies as $frequency) {
                    $normalized = strtolower(trim((string) $frequency));

                    if ($normalized === 'harian') {
                        $payQuery->orWhereRaw('LOWER(pay_freq) IN (?, ?)', ['harian', 'daily']);
                    }

                    if ($normalized === 'bulanan') {
                        $payQuery->orWhereRaw('LOWER(pay_freq) IN (?, ?)', ['bulanan', 'monthly']);
                    }

                    if ($normalized === 'tanpa data') {
                        $payQuery->orWhereNull('pay_freq')
                            ->orWhereRaw("TRIM(COALESCE(pay_freq, '')) = ''")
                            ->orWhereRaw("LOWER(TRIM(COALESCE(pay_freq, ''))) = '-'");
                    }
                }
            });
        }

        if ($request->filled('status')) {
            $this->applyStatusFilter($query, (string) $request->status);
        }
    }

    private function filterByContractStatus(Collection $employees, string|array|null $contractStatuses): Collection
    {
        $selectedStatuses = collect(is_array($contractStatuses) ? $contractStatuses : [$contractStatuses])
            ->filter(fn ($value) => filled($value))
            ->values();

        if ($selectedStatuses->isEmpty()) {
            return $employees;
        }

        return $employees->filter(function ($employee) use ($selectedStatuses) {
            $resolvedStatus = $this->resolveContractStatus($employee->end_date);

            if ($resolvedStatus === null && $selectedStatuses->contains(self::EMPTY_FILTER_VALUE)) {
                return true;
            }

            return $selectedStatuses->contains($resolvedStatus);
        })->values();
    }

    private function applyEqualityOrEmptyFilter($query, string $column, array $values): void
    {
        $selectedValues = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        if ($selectedValues->isEmpty()) {
            return;
        }

        $hasEmptyFilter = $selectedValues->contains(self::EMPTY_FILTER_VALUE);
        $normalValues = $selectedValues
            ->reject(fn ($value) => $value === self::EMPTY_FILTER_VALUE)
            ->values();

        $query->where(function ($filterQuery) use ($column, $normalValues, $hasEmptyFilter) {
            if ($normalValues->isNotEmpty()) {
                $filterQuery->whereIn($column, $normalValues->all());
            }

            if ($hasEmptyFilter) {
                $method = $normalValues->isNotEmpty() ? 'orWhere' : 'where';

                $filterQuery->{$method}(function ($emptyQuery) use ($column) {
                    $emptyQuery
                        ->whereNull($column)
                        ->orWhereRaw("TRIM(COALESCE({$column}, '')) = ''")
                        ->orWhereRaw("LOWER(TRIM(COALESCE({$column}, ''))) = '-'");
                });
            }
        });
    }

    private function applyRawExpressionOrEmptyFilter($query, string $expression, array $values): void
    {
        $selectedValues = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        if ($selectedValues->isEmpty()) {
            return;
        }

        $hasEmptyFilter = $selectedValues->contains(self::EMPTY_FILTER_VALUE);
        $normalValues = $selectedValues
            ->reject(fn ($value) => $value === self::EMPTY_FILTER_VALUE)
            ->values();

        $query->where(function ($filterQuery) use ($expression, $normalValues, $hasEmptyFilter) {
            if ($normalValues->isNotEmpty()) {
                $placeholders = implode(', ', array_fill(0, $normalValues->count(), '?'));
                $filterQuery->whereRaw("{$expression} IN ({$placeholders})", $normalValues->all());
            }

            if ($hasEmptyFilter) {
                $method = $normalValues->isNotEmpty() ? 'orWhere' : 'where';

                $filterQuery->{$method}(function ($emptyQuery) use ($expression) {
                    $emptyQuery
                        ->whereRaw("{$expression} IS NULL")
                        ->orWhereRaw("TRIM(COALESCE({$expression}, '')) = ''")
                        ->orWhereRaw("LOWER(TRIM(COALESCE({$expression}, ''))) = '-'");
                });
            }
        });
    }

    private function applyServandaAreaFilter($query, array $values): void
    {
        $selectedValues = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        if ($selectedValues->isEmpty()) {
            return;
        }

        $hasEmptyFilter = $selectedValues->contains(self::EMPTY_FILTER_VALUE);
        $normalValues = $selectedValues
            ->reject(fn ($value) => $value === self::EMPTY_FILTER_VALUE)
            ->values();

        $query->where(function ($areaQuery) use ($normalValues, $hasEmptyFilter) {
            if ($normalValues->isNotEmpty()) {
                $areaQuery->where(function ($filledAreaQuery) use ($normalValues) {
                    foreach ($this->servandaAreaColumns() as $index => $column) {
                        $method = $index === 0 ? 'whereIn' : 'orWhereIn';
                        $filledAreaQuery->{$method}($column, $normalValues->all());
                    }
                });
            }

            if ($hasEmptyFilter) {
                $method = $normalValues->isNotEmpty() ? 'orWhere' : 'where';

                $areaQuery->{$method}(function ($emptyAreaQuery) {
                    foreach ($this->servandaAreaColumns() as $index => $column) {
                        $whereMethod = $index === 0 ? 'where' : 'orWhere';
                        $emptyAreaQuery->{$whereMethod}(function ($columnQuery) use ($column) {
                            $columnQuery
                                ->whereNull($column)
                                ->orWhereRaw("TRIM(COALESCE({$column}, '')) = ''")
                                ->orWhereRaw("LOWER(TRIM(COALESCE({$column}, ''))) = '-'");
                        });
                    }
                });
            }
        });
    }

    private function applySiteAreaRelationFilter(
        $query,
        string $selectedCompany,
        ?string $selectedDivision,
        string $areaExpression,
        string $column,
        array $values
    ): void {
        $selectedValues = collect($values)
            ->map(fn ($value) => trim((string) $value))
            ->filter(fn ($value) => $value !== '')
            ->values();

        if ($selectedValues->isEmpty()) {
            return;
        }

        $hasEmptyFilter = $selectedValues->contains(self::EMPTY_FILTER_VALUE);
        $normalValues = $selectedValues
            ->reject(fn ($value) => $value === self::EMPTY_FILTER_VALUE)
            ->values();

        $query->where(function ($outerQuery) use ($selectedCompany, $selectedDivision, $areaExpression, $column, $normalValues, $hasEmptyFilter) {
            if ($normalValues->isNotEmpty()) {
                $outerQuery->whereExists(function ($relationQuery) use ($selectedCompany, $selectedDivision, $areaExpression, $column, $normalValues) {
                    $relationQuery
                        ->select(DB::raw(1))
                        ->from(self::MASTER_SITE_AREA_TABLE . ' as msa')
                        ->where('msa.company', $selectedCompany)
                        ->whereRaw("msa.area_name = {$areaExpression}")
                        ->when(
                            $selectedCompany === 'servanda' && $selectedDivision !== null,
                            fn ($innerQuery) => $innerQuery->where('msa.division', $selectedDivision)
                        )
                        ->whereIn("msa.{$column}", $normalValues->all());
                });
            }

            if ($hasEmptyFilter) {
                $method = $normalValues->isNotEmpty() ? 'orWhere' : 'where';

                $outerQuery->{$method}(function ($emptyQuery) use ($selectedCompany, $selectedDivision, $areaExpression, $column) {
                    $emptyQuery
                        ->whereRaw("{$areaExpression} IS NULL")
                        ->orWhereRaw("TRIM(COALESCE({$areaExpression}, '')) = ''")
                        ->orWhereRaw("LOWER(TRIM(COALESCE({$areaExpression}, ''))) = '-'")
                        ->orWhereExists(function ($relationQuery) use ($selectedCompany, $selectedDivision, $areaExpression, $column) {
                            $relationQuery
                                ->select(DB::raw(1))
                                ->from(self::MASTER_SITE_AREA_TABLE . ' as msa')
                                ->where('msa.company', $selectedCompany)
                                ->whereRaw("msa.area_name = {$areaExpression}")
                                ->when(
                                    $selectedCompany === 'servanda' && $selectedDivision !== null,
                                    fn ($innerQuery) => $innerQuery->where('msa.division', $selectedDivision)
                                )
                                ->where(function ($nullQuery) use ($column) {
                                    $nullQuery
                                        ->whereNull("msa.{$column}")
                                        ->orWhereRaw("TRIM(COALESCE(msa.{$column}, '')) = ''")
                                        ->orWhereRaw("LOWER(TRIM(COALESCE(msa.{$column}, ''))) = '-'");
                                });
                        });
                });
            }
        });
    }

    private function resolveContractStatus($endDate): ?string
    {
        if (blank($endDate)) {
            return null;
        }

        try {
            $today = Carbon::today();
            $end = Carbon::parse($endDate)->startOfDay();
        } catch (\Throwable $e) {
            return null;
        }

        if ($end->lt($today)) {
            return 'Expired';
        }

        $daysRemaining = $today->diffInDays($end);

        if ($daysRemaining < 30) {
            return 'Menjelang Berakhir';
        }

        if ($end->gt($today->copy()->addMonths(3))) {
            return 'Aman';
        }

        return 'Perhatian';
    }

    private function paginateEmployees(Collection $employees, Request $request): LengthAwarePaginator
    {
        $perPage = 15;
        $page = LengthAwarePaginator::resolveCurrentPage();
        $items = $employees->slice(($page - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $items,
            $employees->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolveCompanyKey(?string $company): string
    {
        return $this->resolveAccessibleCompany($company, array_keys(self::COMPANY_OPTIONS));
    }

    private function newEmployeeQuery(string $selectedCompany, ?Request $request = null)
    {
        if ($selectedCompany === 'servanda') {
            $areaExpression = $this->servandaSelectedAreaExpression($request);

            return DB::table(self::COMPANY_OPTIONS[$selectedCompany]['table'])->select([
                'nama',
                'employee_no',
                'position',
                'jenis_kelamin',
                'start_date',
                'end_date',
                'termination_date',
                'tanggal_lahir',
                'site_area_ss',
                'site_area_cfs',
                'site_area_cs_bpp',
                'site_area_ss_bpp',
                'status',
                'pay_freq',
                DB::raw($areaExpression . ' as area'),
                DB::raw('NULL as employment_status'),
            ]);
        }

        $table = self::COMPANY_OPTIONS[$selectedCompany]['table'];
        $areaColumn = $this->companyAreaColumn($selectedCompany);

        return DB::table($table . ' as employees')->select([
            'employees.nama',
            'employees.employee_no',
            'employees.position',
            'employees.pay_freq',
            'employees.start_date',
            'employees.end_date',
            'employees.termination_date',
            'employees.status',
            'employees.jenis_kelamin',
            'employees.tanggal_lahir',
            DB::raw("NULLIF(employees.{$areaColumn}, '') as area"),
            DB::raw('NULL as employment_status'),
            DB::raw('NULL as site_area_ss'),
            DB::raw('NULL as site_area_cfs'),
            DB::raw('NULL as site_area_cs_bpp'),
            DB::raw('NULL as site_area_ss_bpp'),
        ]);
    }

    private function getAreaOptions(string $selectedCompany): Collection
    {
        if ($selectedCompany !== 'servanda') {
            return DB::query()
                ->fromSub($this->newEmployeeQuery($selectedCompany), 'employees')
                ->whereNotNull('area')
                ->where('area', '!=', '')
                ->distinct()
                ->orderBy('area')
                ->pluck('area');
        }

        return DB::table(self::COMPANY_OPTIONS[$selectedCompany]['table'])
            ->select([
                'site_area_ss',
                'site_area_cfs',
                'site_area_cs_bpp',
                'site_area_ss_bpp',
            ])
            ->get()
            ->flatMap(function ($row) {
                return collect([
                    $row->site_area_ss,
                    $row->site_area_cfs,
                    $row->site_area_cs_bpp,
                    $row->site_area_ss_bpp,
                ]);
            })
            ->filter(fn ($value) => filled($value))
            ->unique()
            ->sort()
            ->values();
    }

    private function getSiteAreaOptions(string $selectedCompany, ?string $division = null): Collection
    {
        if ($selectedCompany !== 'servanda') {
            return $this->getAreaOptions($selectedCompany);
        }

        $columns = match ($division) {
            'Security' => ['site_area_ss', 'site_area_ss_bpp'],
            'Cleaning' => ['site_area_cfs', 'site_area_cs_bpp'],
            default => ['site_area_ss', 'site_area_cfs', 'site_area_cs_bpp', 'site_area_ss_bpp'],
        };

        return DB::table(self::COMPANY_OPTIONS[$selectedCompany]['table'])
            ->select($columns)
            ->get()
            ->flatMap(function ($row) use ($columns) {
                return collect($columns)->map(fn ($column) => data_get($row, $column));
            })
            ->filter(fn ($value) => filled($value))
            ->values();
    }

    private function siteAreaMasterQuery(string $selectedCompany, ?string $division = null)
    {
        return DB::table(self::MASTER_SITE_AREA_TABLE)
            ->where('company', $selectedCompany)
            ->when($selectedCompany === 'servanda' && $division !== null, fn ($query) => $query->where('division', $division));
    }

    private function getBranchOptions(string $selectedCompany, ?string $division = null): Collection
    {
        return $this->siteAreaMasterQuery($selectedCompany, $division)
            ->whereNotNull('branch')
            ->where('branch', '!=', '')
            ->distinct()
            ->orderBy('branch')
            ->pluck('branch');
    }

    private function getSiteAreaManagerOptions(string $selectedCompany, ?string $division = null, string $column = 'area_manager'): Collection
    {
        return $this->siteAreaMasterQuery($selectedCompany, $division)
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->distinct()
            ->orderBy($column)
            ->pluck($column);
    }

    private function cachedBranchOptions(string $selectedCompany, ?string $division = null): Collection
    {
        $cacheKey = 'site-area:branches:' . $selectedCompany . ':' . ($division ?: 'all');

        try {
            return Cache::remember($cacheKey, now()->addMinutes(10), fn () => $this->getBranchOptions($selectedCompany, $division));
        } catch (\Throwable $exception) {
            return $this->getBranchOptions($selectedCompany, $division);
        }
    }

    private function getServandaSiteAreaOptions(string $selectedCompany): array
    {
        $table = self::COMPANY_OPTIONS[$selectedCompany]['table'];
        $labels = [
            'site_area_ss' => 'Site Area Security',
            'site_area_cfs' => 'Site Area Cleaning',
            'site_area_cs_bpp' => 'Site Area CS BPP',
            'site_area_ss_bpp' => 'Site Area Security BPP',
        ];

        $options = [];

        foreach ($labels as $column => $label) {
            $options[$column] = [
                'label' => $label,
                'items' => DB::table($table)
                    ->whereNotNull($column)
                    ->where($column, '!=', '')
                    ->distinct()
                    ->orderBy($column)
                    ->pluck($column),
            ];
        }

        return $options;
    }

    private function areaExpression(): string
    {
        return "COALESCE(NULLIF(site_area_ss, ''), NULLIF(site_area_cfs, ''), NULLIF(site_area_cs_bpp, ''), NULLIF(site_area_ss_bpp, ''))";
    }

    private function servandaAreaColumns(): array
    {
        return ['site_area_ss', 'site_area_cfs', 'site_area_cs_bpp', 'site_area_ss_bpp'];
    }

    private function servandaSelectedAreaExpression(?Request $request = null): string
    {
        if (! $request) {
            return $this->areaExpression();
        }

        $activeColumns = collect($this->servandaAreaColumns())
            ->filter(function ($column) use ($request) {
                return collect($request->input($column, []))
                    ->filter(fn ($value) => filled($value))
                    ->isNotEmpty();
            })
            ->values();

        if ($activeColumns->count() === 1) {
            $column = $activeColumns->first();

            return "NULLIF({$column}, '')";
        }

        return $this->areaExpression();
    }

    private function companyAreaColumn(string $selectedCompany): string
    {
        return match ($selectedCompany) {
            'gabe' => 'site_area_gabe',
            'salus' => 'site_area_salus',
            default => 'area',
        };
    }

    private function resolveServandaDivisionFilter(string $selectedCompany, ?string $division): ?string
    {
        return $this->resolveRoleBasedDivision($division, $selectedCompany);
    }

    private function resolveStoredSiteAreaDivision(string $selectedCompany, ?string $division): ?string
    {
        if ($selectedCompany !== 'servanda') {
            return 'General';
        }

        return $this->resolveServandaDivisionFilter($selectedCompany, $division);
    }

    private function nullableTrimmed(?string $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function resolveWorkRealizationDivisionFilter(string $selectedCompany, mixed $division): ?string
    {
        $normalized = trim((string) $division);

        if ($normalized === self::EMPTY_FILTER_VALUE && $this->forcedDivisionByRole() === null) {
            return self::EMPTY_FILTER_VALUE;
        }

        return $this->resolveServandaDivisionFilter($selectedCompany, $normalized);
    }

    private function appendEmptyFilterOption(Collection $options): Collection
    {
        $normalizedOptions = $options
            ->map(fn ($option) => trim((string) $option))
            ->filter(fn ($option) => filled($option))
            ->unique(fn ($option) => strtolower($option))
            ->values();

        return collect([self::EMPTY_FILTER_VALUE])
            ->merge($normalizedOptions->reject(fn ($option) => $option === self::EMPTY_FILTER_VALUE))
            ->values();
    }

    private function applyWorkRealizationOptionFilters(
        $query,
        string $selectedCompany,
        ?string $selectedDivision,
        string $selectedBranch,
        string $selectedPayFrequency,
        string $selectedPosition,
        string $selectedArea,
        string $selectedAreaManager,
        string $selectedOperationManager
    ): void {
        if ($selectedDivision === self::EMPTY_FILTER_VALUE) {
            $query->whereNull('employees.area');
        }

        if ($selectedDivision !== null && $selectedDivision !== self::EMPTY_FILTER_VALUE && $selectedCompany === 'servanda') {
            $divisionColumns = match ($selectedDivision) {
                'Security' => ['site_area_ss', 'site_area_ss_bpp'],
                'Cleaning' => ['site_area_cfs', 'site_area_cs_bpp'],
                default => [],
            };

            if ($divisionColumns !== []) {
                $query->where(function ($divisionQuery) use ($divisionColumns) {
                    foreach ($divisionColumns as $index => $column) {
                        $method = $index === 0 ? 'where' : 'orWhere';
                        $divisionQuery->{$method}(function ($columnQuery) use ($column) {
                            $columnQuery
                                ->whereNotNull("employees.{$column}")
                                ->where("employees.{$column}", '!=', '');
                        });
                    }
                });
            }
        }

        $this->applyWorkRealizationEqualityOrEmptyFilter($query, 'employees.position', $selectedPosition);
        $this->applyWorkRealizationEqualityOrEmptyFilter($query, 'employees.area', $selectedArea);
        $this->applyWorkRealizationPayFrequencyFilter($query, $selectedPayFrequency);

        if ($selectedBranch !== '') {
            $this->applyWorkRealizationSiteAreaFilter($query, $selectedCompany, $selectedDivision, 'branch', $selectedBranch);
        }

        if ($selectedAreaManager !== '') {
            $this->applyWorkRealizationSiteAreaFilter($query, $selectedCompany, $selectedDivision, 'area_manager', $selectedAreaManager);
        }

        if ($selectedOperationManager !== '') {
            $this->applyWorkRealizationSiteAreaFilter($query, $selectedCompany, $selectedDivision, 'operation_manager', $selectedOperationManager);
        }
    }

    private function applyWorkRealizationEqualityOrEmptyFilter($query, string $column, string $value): void
    {
        if ($value === '') {
            return;
        }

        if ($value === self::EMPTY_FILTER_VALUE) {
            $query->where(function ($emptyQuery) use ($column) {
                $emptyQuery
                    ->whereNull($column)
                    ->orWhereRaw("TRIM(COALESCE({$column}, '')) = ''")
                    ->orWhereRaw("LOWER(TRIM(COALESCE({$column}, ''))) = '-'");
            });

            return;
        }

        $query->where($column, $value);
    }

    private function applyWorkRealizationPayFrequencyFilter($query, string $selectedPayFrequency): void
    {
        if ($selectedPayFrequency === '') {
            return;
        }

        $query->where(function ($payQuery) use ($selectedPayFrequency) {
            $normalized = strtolower(trim($selectedPayFrequency));

            if ($normalized === 'harian') {
                $payQuery->whereRaw('LOWER(TRIM(COALESCE(employees.pay_freq, \'\'))) IN (?, ?)', ['harian', 'daily']);
                return;
            }

            if ($normalized === 'bulanan') {
                $payQuery->whereRaw('LOWER(TRIM(COALESCE(employees.pay_freq, \'\'))) IN (?, ?)', ['bulanan', 'monthly']);
                return;
            }

            if ($normalized === 'tanpa data') {
                $payQuery
                    ->whereNull('employees.pay_freq')
                    ->orWhereRaw("TRIM(COALESCE(employees.pay_freq, '')) = ''")
                    ->orWhereRaw("LOWER(TRIM(COALESCE(employees.pay_freq, ''))) = '-'");
            }
        });
    }

    private function applyWorkRealizationSiteAreaFilter(
        $query,
        string $selectedCompany,
        ?string $selectedDivision,
        string $column,
        string $value
    ): void {
        $siteAreaDivision = $selectedDivision !== self::EMPTY_FILTER_VALUE ? $selectedDivision : null;

        $query->where(function ($outerQuery) use ($selectedCompany, $siteAreaDivision, $column, $value) {
            if ($value === self::EMPTY_FILTER_VALUE) {
                $outerQuery->where(function ($emptyQuery) use ($selectedCompany, $siteAreaDivision, $column) {
                    $emptyQuery
                        ->whereNull('employees.area')
                        ->orWhereRaw("TRIM(COALESCE(employees.area, '')) = ''")
                        ->orWhereRaw("LOWER(TRIM(COALESCE(employees.area, ''))) = '-'")
                        ->orWhereExists(function ($managerQuery) use ($selectedCompany, $siteAreaDivision, $column) {
                            $managerQuery
                                ->select(DB::raw(1))
                                ->from(self::MASTER_SITE_AREA_TABLE . ' as msa')
                                ->where('msa.company', $selectedCompany)
                                ->whereColumn('msa.area_name', 'employees.area')
                                ->when(
                                    $selectedCompany === 'servanda' && $siteAreaDivision !== null,
                                    fn ($innerQuery) => $innerQuery->where('msa.division', $siteAreaDivision)
                                )
                                ->where(function ($nullColumnQuery) use ($column) {
                                    $nullColumnQuery
                                        ->whereNull("msa.{$column}")
                                        ->orWhereRaw("TRIM(COALESCE(msa.{$column}, '')) = ''")
                                        ->orWhereRaw("LOWER(TRIM(COALESCE(msa.{$column}, ''))) = '-'");
                                });
                        });
                });

                return;
            }

            $outerQuery->whereExists(function ($managerQuery) use ($selectedCompany, $siteAreaDivision, $column, $value) {
                $managerQuery
                    ->select(DB::raw(1))
                    ->from(self::MASTER_SITE_AREA_TABLE . ' as msa')
                    ->where('msa.company', $selectedCompany)
                    ->whereColumn('msa.area_name', 'employees.area')
                    ->when(
                        $selectedCompany === 'servanda' && $siteAreaDivision !== null,
                        fn ($innerQuery) => $innerQuery->where('msa.division', $siteAreaDivision)
                    )
                    ->where("msa.{$column}", $value);
            });
        });
    }

    private function parseSiteAreaImportFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvSiteAreaFile($file),
            'xlsx' => $this->parseXlsxSiteAreaFile($file),
            default => [],
        };
    }

    private function parseCsvSiteAreaFile(UploadedFile $file): array
    {
        $handle = fopen($file->getRealPath(), 'r');

        if ($handle === false) {
            return [];
        }

        $headers = fgetcsv($handle) ?: [];
        $headers = $this->normalizeImportHeaders($headers);
        $rows = [];

        while (($data = fgetcsv($handle)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $row = [];

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $data[$index] ?? null;
            }

            if (collect($row)->filter(fn ($value) => filled($value))->isNotEmpty()) {
                $rows[] = $row;
            }
        }

        fclose($handle);

        return $rows;
    }

    private function parseXlsxSiteAreaFile(UploadedFile $file): array
    {
        if (! class_exists(\ZipArchive::class)) {
            return [];
        }

        $zip = new \ZipArchive();

        if ($zip->open($file->getRealPath()) !== true) {
            return [];
        }

        $sharedStrings = [];
        $sharedStringsXml = $zip->getFromName('xl/sharedStrings.xml');

        if ($sharedStringsXml !== false) {
            $sharedStringsDom = simplexml_load_string($sharedStringsXml);

            if ($sharedStringsDom !== false && isset($sharedStringsDom->si)) {
                foreach ($sharedStringsDom->si as $stringItem) {
                    $text = '';

                    if (isset($stringItem->t)) {
                        $text = (string) $stringItem->t;
                    } elseif (isset($stringItem->r)) {
                        foreach ($stringItem->r as $run) {
                            $text .= (string) $run->t;
                        }
                    }

                    $sharedStrings[] = $text;
                }
            }
        }

        $sheetXml = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($sheetXml === false) {
            return [];
        }

        $sheet = simplexml_load_string($sheetXml);

        if ($sheet === false || ! isset($sheet->sheetData->row)) {
            return [];
        }

        $rows = [];
        $headers = [];

        foreach ($sheet->sheetData->row as $rowIndex => $row) {
            $values = [];

            foreach ($row->c as $cell) {
                $cellRef = (string) $cell['r'];
                $columnLetters = preg_replace('/\d+/', '', $cellRef);
                $columnIndex = $this->columnLettersToIndex($columnLetters);
                $cellType = (string) $cell['t'];
                $value = '';

                if (isset($cell->v)) {
                    $rawValue = (string) $cell->v;

                    $value = $cellType === 's'
                        ? ($sharedStrings[(int) $rawValue] ?? '')
                        : $rawValue;
                } elseif (isset($cell->is->t)) {
                    $value = (string) $cell->is->t;
                }

                $values[$columnIndex] = $value;
            }

            if ((int) $rowIndex === 0) {
                ksort($values);
                $headers = $this->normalizeImportHeaders(array_values($values));
                continue;
            }

            if ($headers === []) {
                continue;
            }

            $mapped = [];
            $orderedValues = [];

            for ($i = 0; $i < count($headers); $i++) {
                $orderedValues[$i] = $values[$i] ?? null;
            }

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $mapped[$header] = $orderedValues[$index] ?? null;
            }

            if (collect($mapped)->filter(fn ($value) => filled($value))->isNotEmpty()) {
                $rows[] = $mapped;
            }
        }

        return $rows;
    }

    private function normalizeImportHeaders(array $headers): array
    {
        return collect($headers)
            ->map(function ($header) {
                $header = strtolower(trim((string) $header));
                $header = preg_replace('/[^a-z0-9]+/', '_', $header);

                return trim((string) $header, '_');
            })
            ->values()
            ->all();
    }

    private function employeeImportHeaders(string $selectedCompany): array
    {
        $baseHeaders = ['employee_no', 'nama', 'position', 'pay_freq', 'status'];

        if ($selectedCompany === 'servanda') {
            return array_merge($baseHeaders, ['site_area_ss', 'site_area_cfs', 'site_area_cs_bpp', 'site_area_ss_bpp']);
        }

        return array_merge($baseHeaders, ['area']);
    }

    private function parseEmployeeImportFile(UploadedFile $file): array
    {
        $extension = strtolower($file->getClientOriginalExtension());

        return match ($extension) {
            'csv', 'txt' => $this->parseCsvSiteAreaFile($file),
            'xlsx' => $this->parseXlsxSiteAreaFile($file),
            default => [],
        };
    }

    private function nullableImportValue(mixed $value): ?string
    {
        $normalized = trim((string) $value);

        if ($normalized === '' || $normalized === '-') {
            return null;
        }

        return $normalized;
    }

    private function columnLettersToIndex(string $letters): int
    {
        $letters = strtoupper($letters);
        $index = 0;

        for ($i = 0; $i < strlen($letters); $i++) {
            $index = ($index * 26) + (ord($letters[$i]) - 64);
        }

        return max(0, $index - 1);
    }

    private function getUserOptionsByPositions(array $keywords): array
    {
        $normalizedKeywords = collect($keywords)
            ->map(fn ($keyword) => trim((string) $keyword))
            ->filter()
            ->values();

        $cacheKey = 'site-area:user-options:' . md5($normalizedKeywords->implode('|'));

        try {
            return Cache::remember($cacheKey, now()->addMinutes(10), function () use ($normalizedKeywords) {
                return User::query()
                    ->select('name')
                    ->where('status', 'Aktif')
                    ->whereNotNull('name')
                    ->where('name', '!=', '')
                    ->where(function ($query) use ($normalizedKeywords) {
                        foreach ($normalizedKeywords as $keyword) {
                            $query->orWhereJsonContains('position', $keyword);
                        }
                    })
                    ->orderBy('name')
                    ->pluck('name')
                    ->unique()
                    ->values()
                    ->all();
            });
        } catch (\Throwable $exception) {
            return User::query()
                ->select('name')
                ->where('status', 'Aktif')
                ->whereNotNull('name')
                ->where('name', '!=', '')
                ->where(function ($query) use ($normalizedKeywords) {
                    foreach ($normalizedKeywords as $keyword) {
                        $query->orWhereJsonContains('position', $keyword);
                    }
                })
                ->orderBy('name')
                ->pluck('name')
                ->unique()
                ->values()
                ->all();
        }
    }

    private function companyAreaExpression(string $selectedCompany): string
    {
        $column = $this->companyAreaColumn($selectedCompany);

        return "NULLIF(employees.{$column}, '')";
    }

    private function applyStatusFilter($query, string $status): void
    {
        $normalized = strtolower(trim($status));

        if ($status === self::EMPTY_FILTER_VALUE) {
            $query->where(function ($statusQuery) {
                $statusQuery
                    ->whereNull('status')
                    ->orWhereRaw("TRIM(COALESCE(status, '')) = ''")
                    ->orWhereRaw("LOWER(TRIM(COALESCE(status, ''))) = '-'");
            });

            return;
        }

        if ($normalized === 'aktif') {
            $query->where(function ($statusQuery) {
                $statusQuery
                    ->whereRaw('LOWER(TRIM(status)) IN (?, ?, ?, ?)', ['aktif', 'active', 'actived', 'enable'])
                    ->orWhere(function ($activeQuery) {
                        $activeQuery
                            ->whereRaw('LOWER(TRIM(status)) like ?', ['%aktif%'])
                            ->whereRaw('LOWER(TRIM(status)) NOT like ?', ['%tidak aktif%'])
                            ->whereRaw('LOWER(TRIM(status)) NOT like ?', ['%non aktif%']);
                    })
                    ->orWhere(function ($activeQuery) {
                        $activeQuery
                            ->whereRaw('LOWER(TRIM(status)) like ?', ['%active%'])
                            ->whereRaw('LOWER(TRIM(status)) NOT like ?', ['%inactive%']);
                    });
            });
        }

        if ($normalized === 'tidak aktif') {
            $query->where(function ($statusQuery) {
                $statusQuery
                    ->whereRaw('LOWER(TRIM(status)) IN (?, ?, ?, ?, ?)', ['tidak aktif', 'non aktif', 'inactive', 'disable', 'disabled'])
                    ->orWhereRaw('LOWER(TRIM(status)) like ?', ['%tidak aktif%'])
                    ->orWhereRaw('LOWER(TRIM(status)) like ?', ['%non aktif%'])
                    ->orWhereRaw('LOWER(TRIM(status)) like ?', ['%inactive%']);
            });
        }
    }
}
