@extends('layouts.dashboard')

@section('title', 'Daftar Pegawai')
@section('page_title', 'Daftar Pegawai - ' . ($companyName ?? 'Servanda'))
@section('page_subtitle', 'Data master karyawan dan informasi penempatan PT ' . ($companyName ?? 'Servanda'))

@section('content')
    @php
        $isServanda = ($selectedCompany ?? 'servanda') === 'servanda';
        $employeeCollection = collect(method_exists($employees, 'getCollection') ? $employees->getCollection() : $employees);
        $positions = $positions ?? $employeeCollection->pluck('position')->filter()->unique()->sort()->values();
        $areas = $areas ?? $employeeCollection->pluck('area')->filter()->unique()->sort()->values();
        $toolbarAreas = $toolbarAreas ?? $employeeCollection->pluck('area')->filter()->unique()->sort()->values();
        $branches = $branches ?? collect();
        $areaManagers = $areaManagers ?? collect();
        $operationManagers = $operationManagers ?? collect();
        $payFrequencies = $payFrequencies ?? $employeeCollection->pluck('pay_freq')->filter()->unique()->sort()->values();
        $statuses = $statuses ?? $employeeCollection->pluck('status')->filter()->unique()->sort()->values();
        $contractStatuses = $contractStatuses ?? collect(['Aman', 'Perhatian', 'Menjelang Berakhir', 'Expired']);
        $divisionOptions = ['Security', 'Cleaning'];
        $forcedDivision = auth()->user()?->forcedDivision();
        $canEditEmployees = auth()->user()?->isAdministrator() ?? false;
        $selectedBranches = collect(request('branch', []))->filter()->values()->all();
        $selectedPositions = collect(request('position', []))->filter()->values()->all();
        $selectedPayFrequencies = collect(request('pay_freq', []))->filter()->values()->all();
        $selectedContractStatuses = collect(request('contract_status', []))->filter()->values()->all();
        $selectedAreaManager = trim((string) request('area_manager', ''));
        $selectedOperationManager = trim((string) request('operation_manager', ''));
        $selectedToolbarArea = trim((string) request('toolbar_area', ''));
        $selectedPerPage = $selectedPerPage ?? (int) request('per_page', 10);
        $emptyFilterValue = '__EMPTY__';
        $buildFilterActionUrl = function (string $key, array $values = [], array $removeKeys = []) use ($selectedCompany) {
            $query = request()->except(array_merge([$key, 'page'], $removeKeys));
            $query['company'] = $selectedCompany ?? 'servanda';

            if (! empty($values)) {
                $query[$key] = $values;
            }

            return route('employee.index', $query);
        };

        $displayFilterValue = function ($value) use ($emptyFilterValue) {
            return $value === $emptyFilterValue ? 'Tanpa Data' : $value;
        };

        $multiSelectSummary = function (array $selectedItems, string $fallback) use ($displayFilterValue) {
            $selectedItems = array_values(array_filter($selectedItems, fn ($item) => filled($item)));
            $selectedItems = array_map(fn ($item) => $displayFilterValue($item), $selectedItems);

            if (count($selectedItems) === 0) {
                return $fallback;
            }

            if (count($selectedItems) <= 2) {
                return implode(', ', $selectedItems);
            }

            return $selectedItems[0] . ', ' . $selectedItems[1] . ' +' . (count($selectedItems) - 2);
        };

        $statusBadgeClasses = function ($value) {
            return match (strtolower(trim((string) $value))) {
                'aktif', 'active' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
                'tidak aktif', 'non active', 'inactive' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200',
                default => 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200',
            };
        };

        $contractStatusBadgeClasses = function ($value) {
            $normalized = strtolower(trim((string) $value));

            if (str_contains($normalized, 'aman')) {
                return 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200';
            }

            if (str_contains($normalized, 'menjelang')) {
                return 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200';
            }

            if (str_contains($normalized, 'perhatian')) {
                return 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200';
            }

            if (str_contains($normalized, 'expired')) {
                return 'bg-white text-slate-900 ring-1 ring-inset ring-slate-300';
            }

            return 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200';
        };

        $payFrequencyBadgeClasses = function ($value) {
            return match (strtolower(trim((string) $value))) {
                'daily', 'harian' => 'bg-sky-50 text-sky-700 ring-1 ring-inset ring-sky-200',
                'monthly', 'bulanan' => 'bg-indigo-50 text-indigo-700 ring-1 ring-inset ring-indigo-200',
                default => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
            };
        };

        $formatDate = function ($value) {
            if (blank($value)) {
                return '-';
            }

            try {
                return \Illuminate\Support\Carbon::parse($value)->translatedFormat('d M Y');
            } catch (\Throwable $e) {
                return $value;
            }
        };

        $contractStatusData = function ($endDate) {
            if (blank($endDate)) {
                return [
                    'label' => '-',
                    'classes' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
                ];
            }

            try {
                $today = \Illuminate\Support\Carbon::today();
                $end = \Illuminate\Support\Carbon::parse($endDate)->startOfDay();
            } catch (\Throwable $e) {
                return [
                    'label' => '-',
                    'classes' => 'bg-slate-100 text-slate-600 ring-1 ring-inset ring-slate-200',
                ];
            }

            if ($end->lt($today)) {
                return [
                    'label' => 'Expired',
                    'classes' => 'bg-white text-slate-900 ring-1 ring-inset ring-slate-300',
                ];
            }

            $daysRemaining = $today->diffInDays($end);

            if ($daysRemaining < 30) {
                return [
                    'label' => 'Menjelang Berakhir (' . $daysRemaining . ' hari)',
                    'classes' => 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200',
                ];
            }

            $monthsRemaining = max(1, round($daysRemaining / 30, 1));
            $formattedMonthsRemaining = str_replace('.', ',', rtrim(rtrim(number_format($monthsRemaining, 1, '.', ''), '0'), '.'));

            if ($end->gt($today->copy()->addMonths(3))) {
                return [
                    'label' => 'Aman (' . $formattedMonthsRemaining . ' bulan)',
                    'classes' => 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200',
                ];
            }

            return [
                'label' => 'Perhatian (' . $formattedMonthsRemaining . ' bulan)',
                'classes' => 'bg-amber-50 text-amber-700 ring-1 ring-inset ring-amber-200',
            ];
        };
    @endphp

    <div
        x-data="{ detailOpen: false, selectedEmployee: null, editOpen: false, editEmployee: null, importOpen: false, infoOpen: false }"
        @keydown.escape.window="detailOpen = false; editOpen = false; importOpen = false; infoOpen = false"
        class="relative z-20 employee-page"
    >
        <section class="dashboard-card overflow-visible">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="dashboard-eyebrow">Employee Directory</p>
                    <h2 class="text-2xl font-bold tracking-tight text-slate-900">Daftar Pegawai - {{ $companyName ?? 'Servanda' }}</h2>
                </div>

                <div class="employee-total-card rounded-3xl border px-5 py-4 text-right shadow-sm">
                    <p class="employee-total-label text-xs font-semibold uppercase tracking-[0.18em]">Total Data</p>
                    <p class="employee-total-value mt-1 text-3xl font-bold tracking-tight">
                        {{ method_exists($employees, 'total') ? $employees->total() : $employees->count() }}
                    </p>
                </div>
            </div>

            <form method="GET" action="{{ route('employee.index') }}" class="relative mt-6 space-y-6">
                <input type="hidden" name="company" value="{{ $selectedCompany ?? 'servanda' }}">
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="per_page" value="{{ $selectedPerPage }}">
                @if (filled($selectedToolbarArea))
                    <input type="hidden" name="toolbar_area" value="{{ $selectedToolbarArea }}">
                @endif

                    <div class="employee-filter-card rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/60 p-5 shadow-sm">
                    <div class="employee-filter-grid">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Cabang</label>
                            <details class="group relative z-30" x-data="{ search: '' }">
                                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition hover:border-sky-300 group-open:border-sky-400 group-open:ring-4 group-open:ring-sky-100">
                                    <span class="truncate">{{ $multiSelectSummary($selectedBranches, 'Semua') }}</span>
                                    <span class="ml-3 text-sky-600 transition group-open:rotate-180">v</span>
                                </summary>

                                <div class="mt-2 rounded-2xl border border-rose-100 bg-white p-3 shadow-xl shadow-rose-100/70">
                                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                        <span class="text-xs text-slate-500">Pilih satu atau beberapa cabang.</span>
                                        <div class="flex shrink-0 items-center gap-3 text-xs font-semibold">
                                            <a href="{{ $buildFilterActionUrl('branch') }}" class="text-slate-500 hover:text-slate-800">
                                                Reset
                                            </a>
                                            <a href="{{ $buildFilterActionUrl('branch', $branches->values()->all()) }}" class="text-rose-600 hover:text-rose-800">
                                                Pilih Semua
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <input
                                            x-model="search"
                                            type="text"
                                            placeholder="Search cabang"
                                            class="w-full rounded-xl border border-rose-100 bg-rose-50/40 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-rose-300 focus:ring-4 focus:ring-rose-100"
                                        >
                                    </div>

                                    <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                        @foreach ($branches as $branch)
                                            <label x-show="'{{ $branch === $emptyFilterValue ? 'tanpa data' : str($branch)->lower()->replace("'", "\\'") }}'.includes(search.toLowerCase())" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-rose-50/70">
                                                <input
                                                    type="checkbox"
                                                    name="branch[]"
                                                    value="{{ $branch }}"
                                                    @checked(in_array($branch, $selectedBranches, true))
                                                    class="h-4 w-4 rounded border-rose-200 text-rose-600 accent-rose-600 focus:ring-rose-400"
                                                >
                                                <span>{{ $displayFilterValue($branch) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        </div>

                        @if ($isServanda)
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Divisi</label>
                                @if (filled($forcedDivision))
                                    <input type="hidden" name="division" value="{{ $forcedDivision }}">
                                    <div class="flex min-h-[50px] items-center rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 text-sm font-semibold text-slate-700 shadow-sm">
                                        {{ $forcedDivision }}
                                    </div>
                                @else
                                    <select
                                        name="division"
                                        class="w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                                    >
                                        <option value="">Semua</option>
                                        @foreach ($divisionOptions as $division)
                                            <option value="{{ $division }}" @selected(($selectedDivision ?? null) === $division)>{{ $division }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Position</label>
                            <details class="group relative z-30" x-data="{ search: '' }">
                                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition hover:border-sky-300 group-open:border-sky-400 group-open:ring-4 group-open:ring-sky-100">
                                    <span class="truncate">{{ $multiSelectSummary($selectedPositions, 'Semua') }}</span>
                                    <span class="ml-3 text-sky-600 transition group-open:rotate-180">v</span>
                                </summary>

                                <div class="mt-2 rounded-2xl border border-blue-100 bg-white p-3 shadow-xl shadow-blue-100/70">
                                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                        <span class="text-xs text-slate-500">Pilih satu atau beberapa position.</span>
                                        <div class="flex shrink-0 items-center gap-3 text-xs font-semibold">
                                            <a href="{{ $buildFilterActionUrl('position') }}" class="text-slate-500 hover:text-slate-800">
                                                Reset
                                            </a>
                                            <a href="{{ $buildFilterActionUrl('position', $positions->values()->all()) }}" class="text-blue-600 hover:text-blue-800">
                                                Pilih Semua
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <input
                                            x-model="search"
                                            type="text"
                                            placeholder="Search position"
                                            class="w-full rounded-xl border border-blue-100 bg-blue-50/40 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-blue-300 focus:ring-4 focus:ring-blue-100"
                                        >
                                    </div>

                                    <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                        @foreach ($positions as $position)
                                            <label x-show="'{{ $position === $emptyFilterValue ? 'tanpa data' : str($position)->lower()->replace("'", "\\'") }}'.includes(search.toLowerCase())" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-blue-50/70">
                                                <input
                                                    type="checkbox"
                                                    name="position[]"
                                                    value="{{ $position }}"
                                                    @checked(in_array($position, $selectedPositions, true))
                                                    class="h-4 w-4 rounded border-blue-200 text-blue-600 accent-blue-600 focus:ring-blue-400"
                                                >
                                                <span>{{ $displayFilterValue($position) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Area Manager</label>
                            <select
                                name="area_manager"
                                class="w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="">Semua</option>
                                @foreach ($areaManagers as $areaManager)
                                    <option value="{{ $areaManager }}" @selected($selectedAreaManager === $areaManager)>{{ $displayFilterValue($areaManager) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Operation Manager</label>
                            <select
                                name="operation_manager"
                                class="w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="">Semua</option>
                                @foreach ($operationManagers as $operationManager)
                                    <option value="{{ $operationManager }}" @selected($selectedOperationManager === $operationManager)>{{ $displayFilterValue($operationManager) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Gaji</label>
                            <details class="group relative z-30" x-data="{ search: '' }">
                                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition hover:border-sky-300 group-open:border-sky-400 group-open:ring-4 group-open:ring-sky-100">
                                    <span class="truncate">{{ $multiSelectSummary($selectedPayFrequencies, 'Semua') }}</span>
                                    <span class="ml-3 text-sky-600 transition group-open:rotate-180">v</span>
                                </summary>

                                <div class="mt-2 rounded-2xl border border-indigo-100 bg-white p-3 shadow-xl shadow-indigo-100/70">
                                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                        <span class="text-xs text-slate-500">Pilih jenis gaji berdasarkan Pay Freq.</span>
                                        <div class="flex shrink-0 items-center gap-3 text-xs font-semibold">
                                            <a href="{{ $buildFilterActionUrl('pay_freq') }}" class="text-slate-500 hover:text-slate-800">
                                                Reset
                                            </a>
                                            <a href="{{ $buildFilterActionUrl('pay_freq', $payFrequencies->values()->all()) }}" class="text-indigo-600 hover:text-indigo-800">
                                                Pilih Semua
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <input
                                            x-model="search"
                                            type="text"
                                            placeholder="Search gaji"
                                            class="w-full rounded-xl border border-indigo-100 bg-indigo-50/40 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-indigo-300 focus:ring-4 focus:ring-indigo-100"
                                        >
                                    </div>

                                    <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                        @foreach ($payFrequencies as $payFrequency)
                                            <label x-show="'{{ $payFrequency === $emptyFilterValue ? 'tanpa data' : str($payFrequency)->lower()->replace("'", "\\'") }}'.includes(search.toLowerCase())" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-indigo-50/70">
                                                <input
                                                    type="checkbox"
                                                    name="pay_freq[]"
                                                    value="{{ $payFrequency }}"
                                                    @checked(in_array($payFrequency, $selectedPayFrequencies, true))
                                                    class="h-4 w-4 rounded border-indigo-200 text-indigo-600 accent-indigo-600 focus:ring-indigo-400"
                                                >
                                                <span>{{ $displayFilterValue($payFrequency) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                            <select
                                name="status"
                                class="w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100"
                            >
                                <option value="">Semua</option>
                                @foreach ($statuses as $status)
                                    <option value="{{ $status }}" @selected(request('status') == $status)>{{ $displayFilterValue($status) }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Status Kontrak</label>
                            <details class="group relative z-30" x-data="{ search: '' }">
                                <summary class="flex cursor-pointer list-none items-center justify-between rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm text-slate-700 shadow-sm transition hover:border-sky-300 group-open:border-sky-400 group-open:ring-4 group-open:ring-sky-100">
                                    <span class="truncate">{{ $multiSelectSummary($selectedContractStatuses, 'Semua') }}</span>
                                    <span class="ml-3 text-sky-600 transition group-open:rotate-180">v</span>
                                </summary>

                                <div class="mt-2 rounded-2xl border border-cyan-100 bg-white p-3 shadow-xl shadow-cyan-100/70">
                                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                        <span class="text-xs text-slate-500">Pilih satu atau beberapa status kontrak.</span>
                                        <div class="flex shrink-0 items-center gap-3 text-xs font-semibold">
                                            <a href="{{ $buildFilterActionUrl('contract_status') }}" class="text-slate-500 hover:text-slate-800">
                                                Reset
                                            </a>
                                            <a href="{{ $buildFilterActionUrl('contract_status', $contractStatuses->values()->all()) }}" class="text-cyan-600 hover:text-cyan-800">
                                                Pilih Semua
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <input
                                            x-model="search"
                                            type="text"
                                            placeholder="Search status kontrak"
                                            class="w-full rounded-xl border border-cyan-100 bg-cyan-50/40 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-cyan-300 focus:ring-4 focus:ring-cyan-100"
                                        >
                                    </div>

                                    <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                        @foreach ($contractStatuses as $contractStatus)
                                            <label x-show="'{{ $contractStatus === $emptyFilterValue ? 'tanpa data' : str($contractStatus)->lower()->replace("'", "\\'") }}'.includes(search.toLowerCase())" class="flex items-center gap-3 rounded-xl px-3 py-2 text-sm text-slate-700 transition hover:bg-cyan-50/70">
                                                <input
                                                    type="checkbox"
                                                    name="contract_status[]"
                                                    value="{{ $contractStatus }}"
                                                    @checked(in_array($contractStatus, $selectedContractStatuses, true))
                                                    class="h-4 w-4 rounded border-cyan-200 text-cyan-600 accent-cyan-600 focus:ring-cyan-400"
                                                >
                                                <span>{{ $displayFilterValue($contractStatus) }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>
                </div>

                <div class="employee-action-group mt-5">
                    <button type="submit" class="employee-action-button employee-action-primary">
                        Search / Filter
                    </button>

                    <a href="{{ route('employee.index', ['company' => $selectedCompany ?? 'servanda']) }}" class="employee-action-button employee-action-neutral">
                        Reset
                    </a>

                    <a
                        href="{{ route('employee.export', array_merge(['company' => $selectedCompany ?? 'servanda'], request()->only(['search', 'branch', 'division', 'position', 'area', 'area_manager', 'operation_manager', 'pay_freq', 'status', 'contract_status']))) }}"
                        class="employee-action-button employee-action-success"
                    >
                        Export
                    </a>

                    @if ($canEditEmployees)
                        <a
                            href="{{ route('employee.template-export', array_merge(['company' => $selectedCompany ?? 'servanda'], request()->only(['search', 'branch', 'division', 'position', 'area', 'area_manager', 'operation_manager', 'pay_freq', 'status', 'contract_status']))) }}"
                            class="employee-action-button employee-action-info"
                        >
                            Export Template
                        </a>

                        <button
                            type="button"
                            @click="importOpen = true"
                            class="employee-action-button employee-action-warning"
                        >
                            Import
                        </button>
                    @endif
                </div>
            </form>
        </section>

    <section class="dashboard-card relative z-10 mt-6 overflow-hidden p-0">
        <div class="border-b border-slate-200 px-6 py-5">
            <form method="GET" action="{{ route('employee.index') }}">
                <input type="hidden" name="company" value="{{ $selectedCompany ?? 'servanda' }}">
                @foreach ($selectedBranches as $branch)
                    <input type="hidden" name="branch[]" value="{{ $branch }}">
                @endforeach
                @if (filled($selectedDivision ?? null))
                    <input type="hidden" name="division" value="{{ $selectedDivision }}">
                @endif
                @foreach ($selectedPositions as $position)
                    <input type="hidden" name="position[]" value="{{ $position }}">
                @endforeach
                <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">
                @foreach ($selectedPayFrequencies as $payFrequency)
                    <input type="hidden" name="pay_freq[]" value="{{ $payFrequency }}">
                @endforeach
                @foreach ($selectedContractStatuses as $contractStatus)
                    <input type="hidden" name="contract_status[]" value="{{ $contractStatus }}">
                @endforeach
                @if (filled(request('status')))
                    <input type="hidden" name="status" value="{{ request('status') }}">
                @endif
                <div class="employee-table-toolbar">
                    <div class="employee-toolbar-field employee-toolbar-field-search">
                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Cari nama, employee no, position, area"
                            oninput="clearTimeout(this._searchTimer); this._searchTimer = setTimeout(() => this.form.submit(), 300)"
                            class="employee-toolbar-input"
                        >
                    </div>

                    <div class="employee-toolbar-field employee-toolbar-field-area">
                        <select
                            name="toolbar_area"
                            onchange="this.form.submit()"
                            class="employee-toolbar-select"
                            aria-label="Filter area"
                        >
                            <option value="">Semua Area</option>
                            @foreach ($toolbarAreas as $area)
                                <option value="{{ $area }}" @selected($selectedToolbarArea === $area)>
                                    {{ $displayFilterValue($area) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="employee-toolbar-field employee-toolbar-field-show">
                        <select
                            name="per_page"
                            onchange="this.form.submit()"
                            class="employee-toolbar-select"
                            aria-label="Jumlah data per halaman"
                        >
                            @foreach ([10, 50, 100] as $perPageOption)
                                <option value="{{ $perPageOption }}" @selected((int) $selectedPerPage === $perPageOption)>
                                    {{ $perPageOption }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </form>
        </div>

        <div class="table-responsive mt-6">
            <table class="employee-table w-full text-[14px] leading-6">
                <colgroup>
                    <col class="employee-col-employee">
                    <col class="employee-col-position">
                    <col class="employee-col-pay">
                    <col class="employee-col-area">
                    <col class="employee-col-contract">
                    <col class="employee-col-status">
                    <col class="employee-col-action">
                </colgroup>
                <thead class="sticky top-0 z-10 bg-slate-50/95 text-slate-600 backdrop-blur">
                    <tr>
                        <th class="whitespace-nowrap px-6 py-4 text-left text-[12px] font-semibold uppercase tracking-[0.14em]">Pegawai</th>
                        <th class="whitespace-nowrap px-5 py-4 text-left text-[12px] font-semibold uppercase tracking-[0.14em]">Position</th>
                        <th class="whitespace-nowrap px-5 py-4 text-left text-[12px] font-semibold uppercase tracking-[0.14em]">Gaji</th>
                        <th class="whitespace-nowrap px-5 py-4 text-left text-[12px] font-semibold uppercase tracking-[0.14em]">Area Penempatan</th>
                        <th class="whitespace-nowrap px-5 py-4 text-left text-[12px] font-semibold uppercase tracking-[0.14em]">Status Kontrak</th>
                        <th class="whitespace-nowrap px-5 py-4 text-left text-[12px] font-semibold uppercase tracking-[0.14em]">Status</th>
                        <th class="whitespace-nowrap px-5 py-4 text-center text-[12px] font-semibold uppercase tracking-[0.14em]">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @forelse ($employees as $employee)
                        @php
                            $contractStatus = $contractStatusData($employee->end_date);
                            $employeeDetailPayload = [
                                'nama' => $employee->nama ?: '-',
                                'employee_no' => $employee->employee_no ?: '-',
                                'position' => $employee->position ?: '-',
                                'pay_freq' => $employee->pay_freq ?: '-',
                                'jenis_kelamin' => $employee->jenis_kelamin ?: '-',
                                'area' => $employee->area ?: '-',
                                'tanggal_lahir' => $formatDate($employee->tanggal_lahir),
                                'start_date' => $formatDate($employee->start_date),
                                'end_date' => $formatDate($employee->end_date),
                                'termination_date' => $formatDate($employee->termination_date),
                                'site_area_ss' => $employee->site_area_ss ?: '-',
                                'site_area_cfs' => $employee->site_area_cfs ?: '-',
                                'site_area_cs_bpp' => $employee->site_area_cs_bpp ?: '-',
                                'site_area_ss_bpp' => $employee->site_area_ss_bpp ?: '-',
                                'site_area_ss_raw' => $employee->site_area_ss ?? '',
                                'site_area_cfs_raw' => $employee->site_area_cfs ?? '',
                                'site_area_cs_bpp_raw' => $employee->site_area_cs_bpp ?? '',
                                'site_area_ss_bpp_raw' => $employee->site_area_ss_bpp ?? '',
                                'area_raw' => $employee->area ?? '',
                                'position_raw' => $employee->position ?? '',
                                'pay_freq_raw' => $employee->pay_freq ?? '',
                                'status_raw' => $employee->status ?? '',
                                'employee_no_raw' => $employee->employee_no ?? '',
                                'employment_status' => $employee->employment_status ?: ($employee->status ?: '-'),
                                'status' => $employee->status ?: '-',
                                'contract_status' => $contractStatus['label'],
                                'status_class' => $statusBadgeClasses($employee->status ?: '-'),
                                'employment_status_class' => $statusBadgeClasses($employee->employment_status ?: ($employee->status ?: '-')),
                                'contract_status_class' => $contractStatusBadgeClasses($contractStatus['label']),
                            ];
                        @endphp
                        <tr class="transition hover:bg-slate-50/70">
                            <td data-label="Pegawai" class="px-6 py-4 align-top">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $employee->nama }}</p>
                                    <p class="mt-1 text-[13px] text-slate-500">Employee No: {{ $employee->employee_no ?: '-' }}</p>
                                </div>
                            </td>
                            <td data-label="Position" class="px-5 py-4 align-top text-slate-700 employee-cell-wrap">{{ $employee->position ?: '-' }}</td>
                            <td data-label="Gaji" class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $payFrequencyBadgeClasses($employee->pay_freq) }}">
                                    {{ $employee->pay_freq ?: '-' }}
                                </span>
                            </td>
                            <td data-label="Area Penempatan" class="px-5 py-4 align-top text-slate-700 employee-cell-wrap employee-area-cell">{{ $employee->area ?: '-' }}</td>
                            <td data-label="Status Kontrak" class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $contractStatus['classes'] }}">
                                    {{ $contractStatus['label'] }}
                                </span>
                            </td>
                            <td data-label="Status" class="px-5 py-4 align-top">
                                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $statusBadgeClasses($employee->status) }}">
                                    {{ $employee->status ?: '-' }}
                                </span>
                            </td>
                            <td data-label="Action" class="px-5 py-4 text-center align-top">
                                <div class="flex items-center justify-center gap-2">
                                    @if ($canEditEmployees)
                                        <button
                                            type="button"
                                            x-on:click.prevent="editEmployee = JSON.parse($el.dataset.employee); editOpen = true"
                                            data-employee='@json($employeeDetailPayload)'
                                            class="inline-flex items-center justify-center rounded-2xl border border-amber-200 bg-gradient-to-br from-white to-amber-50 p-2.5 text-amber-700 shadow-sm transition hover:-translate-y-0.5 hover:border-amber-300 hover:from-amber-50 hover:to-yellow-100 hover:text-amber-800"
                                            title="Edit pegawai"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.12 2.12 0 1 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                        </button>
                                    @endif
                                    <button
                                        type="button"
                                        x-on:click.prevent="selectedEmployee = JSON.parse($el.dataset.employee); detailOpen = true"
                                        data-employee='@json($employeeDetailPayload)'
                                        class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-gradient-to-br from-white to-sky-50 p-2.5 text-sky-700 shadow-sm transition hover:-translate-y-0.5 hover:border-sky-300 hover:from-sky-50 hover:to-cyan-100 hover:text-sky-800"
                                        title="Lihat detail pegawai"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center text-sm text-slate-500">
                                Data pegawai {{ $companyName ?? 'Servanda' }} belum ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if (method_exists($employees, 'links'))
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $employees->links() }}
            </div>
        @endif
        </section>

        <section class="dashboard-card relative z-10 mt-6 overflow-hidden">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="dashboard-eyebrow">Information</p>
                </div>

                <button
                    type="button"
                    @click="infoOpen = !infoOpen"
                    class="inline-flex items-center justify-center rounded-2xl border border-sky-200 bg-gradient-to-r from-sky-50 to-blue-50 px-4 py-2.5 text-sm font-semibold text-sky-700 shadow-sm transition hover:border-sky-300 hover:text-sky-800"
                >
                    <span x-text="infoOpen ? 'Sembunyikan' : 'Selengkapnya'"></span>
                </button>
            </div>

            <div x-cloak x-show="infoOpen" x-transition.opacity.duration.200ms class="mt-5">
                <p class="mb-5 text-sm leading-6 text-slate-500">
                    Penjelasan ini membantu tim membaca kondisi kontrak pegawai dengan lebih cepat dan konsisten.
                </p>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-3xl border border-emerald-200 bg-gradient-to-br from-emerald-50 via-white to-emerald-100/60 p-5 shadow-sm">
                        <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200">
                            Aman
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-900">Kontrak masih jauh dari masa berakhir.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Digunakan untuk pegawai yang masa kontraknya masih aman dan belum membutuhkan tindak lanjut dalam waktu dekat.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 via-white to-yellow-100/60 p-5 shadow-sm">
                        <span class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700 ring-1 ring-inset ring-amber-200">
                            Perhatian
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-900">Kontrak mulai mendekati periode yang perlu dipantau.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Status ini menandakan kontrak belum kritis, tetapi sudah perlu masuk perhatian tim untuk persiapan tindak lanjut.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-rose-200 bg-gradient-to-br from-rose-50 via-white to-rose-100/60 p-5 shadow-sm">
                        <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700 ring-1 ring-inset ring-rose-200">
                            Menjelang Berakhir
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-900">Kontrak sudah sangat dekat dengan tanggal berakhir.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Kondisi ini perlu segera ditindaklanjuti karena masa kontrak tinggal sedikit dan berisiko mengganggu operasional bila terlambat diproses.
                        </p>
                    </div>

                    <div class="rounded-3xl border border-slate-300 bg-gradient-to-br from-slate-50 via-white to-slate-100/80 p-5 shadow-sm">
                        <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-900 ring-1 ring-inset ring-slate-300">
                            Expired
                        </span>
                        <p class="mt-4 text-sm font-semibold text-slate-900">Kontrak sudah melewati tanggal berakhir.</p>
                        <p class="mt-2 text-sm leading-6 text-slate-600">
                            Status ini menunjukkan kontrak telah habis masa berlakunya dan perlu penanganan segera untuk pembaruan atau keputusan lanjutan.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        @if ($canEditEmployees)
            <div
                x-cloak
                x-show="importOpen"
                x-transition.opacity
                class="fixed inset-0 z-[95] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-20"
            >
                <div class="absolute inset-0" @click="importOpen = false"></div>

                <div
                    x-show="importOpen"
                    x-transition
                    class="relative z-10 w-full max-w-2xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_35%),linear-gradient(135deg,#eff6ff_0%,#ffffff_50%,#f0f9ff_100%)] px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Import Pegawai</p>
                            <h3 class="mt-3 text-xl font-bold tracking-tight text-slate-900">Upload File Import</h3>
                            <p class="mt-2 text-[13px] text-slate-600">
                                Download template dulu, isi datanya, lalu upload kembali untuk update massal berdasarkan employee number.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="importOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                            title="Tutup"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('employee.import') }}" enctype="multipart/form-data" class="space-y-5 px-6 pb-6 pt-6">
                        @csrf
                        <input type="hidden" name="company" value="{{ $selectedCompany ?? 'servanda' }}">

                        <div class="rounded-2xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-sm font-semibold text-slate-900">Format file</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                File yang didukung: <strong>CSV</strong> dan <strong>XLSX</strong>. Gunakan kolom dari tombol <strong>Export Template</strong>.
                            </p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">File Import</label>
                            <input
                                type="file"
                                name="import_file"
                                accept=".csv,.txt,.xlsx"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm"
                                required
                            >
                        </div>

                        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-5">
                            <a
                                href="{{ route('employee.template-export', array_merge(['company' => $selectedCompany ?? 'servanda'], request()->only(['search', 'branch', 'division', 'position', 'area', 'area_manager', 'operation_manager', 'pay_freq', 'status', 'contract_status']))) }}"
                                class="rounded-2xl border border-sky-200 bg-sky-50 px-5 py-3 text-sm font-semibold text-sky-700 transition hover:border-sky-300 hover:bg-sky-100"
                            >
                                Export Template
                            </a>
                            <button
                                type="button"
                                @click="importOpen = false"
                                class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-2xl bg-amber-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-amber-600"
                            >
                                Import Data
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div
                x-cloak
                x-show="editOpen"
                x-transition.opacity
                class="fixed inset-0 z-[95] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-20"
            >
                <div class="absolute inset-0" @click="editOpen = false"></div>

                <div
                    x-show="editOpen"
                    x-transition
                    class="relative z-10 w-full max-w-4xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl"
                >
                    <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.20),_transparent_35%),linear-gradient(135deg,#fff7ed_0%,#ffffff_50%,#fef3c7_100%)] px-6 py-5">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Edit Pegawai</p>
                            <h3 class="mt-3 text-xl font-bold tracking-tight text-slate-900" x-text="editEmployee?.nama || '-'"></h3>
                            <p class="mt-2 text-[13px] text-slate-600">
                                Perbarui data master pegawai langsung dari tabel untuk kebutuhan koreksi cepat.
                            </p>
                        </div>

                        <button
                            type="button"
                            @click="editOpen = false"
                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                            title="Tutup"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>

                    <form
                        method="POST"
                        :action="editEmployee ? '{{ url('/employee/' . ($selectedCompany ?? 'servanda')) }}/' + encodeURIComponent(editEmployee.employee_no_raw || '') : '#'"
                        class="max-h-[75vh] overflow-y-auto px-6 pb-6 pt-6"
                    >
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="redirect_url" value="{{ request()->fullUrl() }}">

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Employee No</p>
                                <p class="mt-2 text-base font-bold text-slate-900" x-text="editEmployee?.employee_no_raw || '-'"></p>
                            </div>
                            <div class="rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-4">
                                <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Nama Pegawai</p>
                                <p class="mt-2 text-base font-bold text-slate-900" x-text="editEmployee?.nama || '-'"></p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Position</label>
                                <input type="text" name="position" :value="editEmployee?.position_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Gaji</label>
                                <select name="pay_freq" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    <option value="">Kosongkan</option>
                                    @foreach (['Harian', 'Bulanan'] as $payFrequencyOption)
                                        <option value="{{ $payFrequencyOption }}" x-bind:selected="(editEmployee?.pay_freq_raw || '') === '{{ $payFrequencyOption }}'">{{ $payFrequencyOption }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                                <input type="text" name="status" :value="editEmployee?.status_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                            </div>
                        </div>

                        @if ($isServanda)
                            <div class="mt-6">
                                <p class="text-sm font-semibold uppercase tracking-[0.14em] text-slate-500">Area Penempatan Servanda</p>
                                <div class="mt-4 grid gap-4 md:grid-cols-2">
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Site Area Security</label>
                                        <input type="text" name="site_area_ss" :value="editEmployee?.site_area_ss_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Site Area Cleaning</label>
                                        <input type="text" name="site_area_cfs" :value="editEmployee?.site_area_cfs_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Site Area CS BPP</label>
                                        <input type="text" name="site_area_cs_bpp" :value="editEmployee?.site_area_cs_bpp_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    </div>
                                    <div>
                                        <label class="mb-2 block text-sm font-medium text-slate-700">Site Area Security BPP</label>
                                        <input type="text" name="site_area_ss_bpp" :value="editEmployee?.site_area_ss_bpp_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="mt-6">
                                <label class="mb-2 block text-sm font-medium text-slate-700">Area Penempatan</label>
                                <input type="text" name="area" :value="editEmployee?.area_raw || ''" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                            </div>
                        @endif

                        <div class="mt-6 flex items-center justify-end gap-3 border-t border-slate-200 pt-5">
                            <button
                                type="button"
                                @click="editOpen = false"
                                class="inline-flex items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="inline-flex items-center justify-center rounded-2xl bg-amber-500 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-amber-600"
                            >
                                Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <div
            x-cloak
            x-show="detailOpen"
            x-transition.opacity
            class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-28"
        >
            <div class="absolute inset-0" @click="detailOpen = false"></div>

            <div
                x-show="detailOpen"
                x-transition
                class="relative z-10 w-full max-w-4xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl"
            >
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.22),_transparent_35%),linear-gradient(135deg,#eff6ff_0%,#ffffff_50%,#ecfeff_100%)] px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Detail Pegawai</p>
                    <h3 class="mt-3 text-xl font-bold tracking-tight text-slate-900" x-text="selectedEmployee?.nama || '-'"></h3>
                    <p class="mt-2 text-[13px] text-slate-600">
                        Ringkasan lengkap data pegawai ditampilkan lebih jelas agar cepat dibaca saat pengecekan operasional.
                    </p>
                    <div class="mt-3 flex flex-wrap gap-2">
                        <span class="inline-flex items-center rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-slate-700 ring-1 ring-inset ring-slate-200">
                            NIK: <span class="ml-1 text-slate-900" x-text="selectedEmployee?.employee_no || '-'"></span>
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="selectedEmployee?.status_class || 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200'"
                        >
                            Status: <span class="ml-1" x-text="selectedEmployee?.status || '-'"></span>
                        </span>
                        <span
                            class="inline-flex items-center rounded-full px-3 py-1 text-xs font-semibold"
                            :class="selectedEmployee?.contract_status_class || 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200'"
                        >
                            Kontrak: <span class="ml-1" x-text="selectedEmployee?.contract_status || '-'"></span>
                        </span>
                    </div>
                </div>

                <button
                    type="button"
                    @click="detailOpen = false"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                    title="Tutup"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

                <div class="max-h-[70vh] overflow-y-auto bg-[linear-gradient(180deg,#ffffff_0%,#f8fbff_100%)] px-6 pb-6 pt-8">
                <div class="space-y-5">
                        <section class="rounded-[24px] border border-sky-100 bg-gradient-to-br from-sky-50 via-white to-cyan-50 p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-sky-500 text-white shadow-lg shadow-sky-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                        <circle cx="12" cy="7" r="4" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Data Utama</p>
                                    <p class="text-xs text-slate-500">Identitas dan informasi dasar pegawai.</p>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Employee No</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.employee_no || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Position</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.position || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Gaji</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.pay_freq || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Gender</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.jenis_kelamin || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm md:col-span-2">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Area Penempatan</p>
                                    <p class="mt-2 text-base font-bold leading-7 text-slate-900" x-text="selectedEmployee?.area || '-'"></p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[24px] border border-violet-100 bg-gradient-to-br from-violet-50 via-white to-indigo-50 p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-violet-500 text-white shadow-lg shadow-violet-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M8 2v4" />
                                        <path d="M16 2v4" />
                                        <rect width="18" height="18" x="3" y="4" rx="2" />
                                        <path d="M3 10h18" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Tanggal Penting</p>
                                    <p class="text-xs text-slate-500">Riwayat tanggal utama yang perlu dipantau.</p>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-3">
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Tanggal Lahir</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.tanggal_lahir || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Mulai Kerja</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.start_date || '-'"></p>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Akhir Kontrak</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.end_date || '-'"></p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[24px] border border-emerald-100 bg-gradient-to-br from-emerald-50 via-white to-teal-50 p-5">
                            <div class="mb-4 flex items-center gap-3">
                                <div class="flex h-11 w-11 items-center justify-center rounded-2xl bg-emerald-500 text-white shadow-lg shadow-emerald-200">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M9 11l3 3L22 4" />
                                        <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
                                    </svg>
                                </div>
                                <div>
                                    <p class="text-sm font-bold text-slate-900">Status Kerja</p>
                                    <p class="text-xs text-slate-500">Kondisi aktif dan informasi kontrak saat ini.</p>
                                </div>
                            </div>

                            <div class="grid gap-4 md:grid-cols-2">
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Status Pegawai</p>
                                    <span
                                        class="mt-2 inline-flex rounded-full px-3 py-1.5 text-sm font-semibold"
                                        :class="selectedEmployee?.status_class || 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200'"
                                        x-text="selectedEmployee?.status || '-'"
                                    ></span>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Status Kontrak</p>
                                    <span
                                        class="mt-2 inline-flex rounded-full px-3 py-1.5 text-sm font-semibold"
                                        :class="selectedEmployee?.contract_status_class || 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200'"
                                        x-text="selectedEmployee?.contract_status || '-'"
                                    ></span>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Employment Status</p>
                                    <span
                                        class="mt-2 inline-flex rounded-full px-3 py-1.5 text-sm font-semibold"
                                        :class="selectedEmployee?.employment_status_class || 'bg-slate-100 text-slate-700 ring-1 ring-inset ring-slate-200'"
                                        x-text="selectedEmployee?.employment_status || '-'"
                                    ></span>
                                </div>
                                <div class="rounded-2xl border border-white/80 bg-white/90 px-4 py-4 shadow-sm">
                                    <p class="text-xs font-semibold uppercase tracking-[0.12em] text-slate-500">Termination Date</p>
                                    <p class="mt-2 text-base font-bold text-slate-900" x-text="selectedEmployee?.termination_date || '-'"></p>
                                </div>
                            </div>
                        </section>

                        <section class="rounded-[24px] border border-amber-100 bg-gradient-to-br from-amber-50 via-white to-orange-50 p-5">
                            <p class="text-sm font-bold text-slate-900">Catatan Cepat</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600">
                                Popup ini menampilkan seluruh informasi penting pegawai dalam format blok agar lebih mudah dibaca cepat saat verifikasi data.
                            </p>
                        </section>
                </div>
            </div>
        </div>
        </div>
    </div>
@endsection
