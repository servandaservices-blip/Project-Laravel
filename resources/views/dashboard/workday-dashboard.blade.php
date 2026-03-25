@extends('layouts.dashboard')

@section('title', 'Dashboard Hari Kerja')
@section('page_title', 'Dashboard Hari Kerja')
@section('page_subtitle', 'Admin dashboard attendance karyawan')

@section('content')
    @php
        $companyOptions = [
            'servanda' => 'Servanda',
            'gabe' => 'Gabe',
            'salus' => 'Salus',
        ];
        $divisionOptions = collect($divisionOptions ?? [])->values();
        $forcedDivision = auth()->user()?->forcedDivision();
        $branchOptions = $branchOptions ?? collect();
        $areaManagers = $areaManagers ?? collect();
        $operationManagers = $operationManagers ?? collect();
        $yearOptions = range((int) now()->year - 2, 2030);
        $formatMonthLabelId = fn ($monthNumber) => filled($monthNumber)
            ? \Illuminate\Support\Carbon::create()->locale('id')->month((int) $monthNumber)->translatedFormat('F')
            : '-';
        $currentMonthSnapshot = $months->where('employee_count', '>', 0)->last();
        $currentMonthLabel = $formatMonthLabelId($currentMonthSnapshot['month'] ?? null);
        $currentMonthAttendance = (float) ($currentMonthSnapshot['attendance_rate'] ?? 0);
        $currentMonthWorkday = (float) ($currentMonthSnapshot['avg_workday'] ?? 0);
        $currentMonthShift = (float) ($currentMonthSnapshot['avg_present'] ?? 0);
        $currentMonthWorkdayTarget = (float) data_get(
            collect($workdayTargetSeries ?? [])->get((int) ($currentMonthSnapshot['month'] ?? 0), []),
            'monthly_target',
            0
        );
        $bestMonthLabel = $formatMonthLabelId($bestMonth['month'] ?? null);
        $bestMonthRate = (float) ($bestMonth['attendance_rate'] ?? 0);
        $lowestMonthLabel = $formatMonthLabelId($lowestMonth['month'] ?? null);
        $lowestMonthRate = (float) ($lowestMonth['attendance_rate'] ?? 0);
        $workdayFilterLabelClass = 'mb-2 block text-sm font-medium text-slate-700';
        $workdayFilterFieldClass = 'w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100';

        $positionMax = max((float) ($positionChart->max('attendance_rate') ?? 0), 1);
        $areaMax = max((float) ($areaChart->max('attendance_rate') ?? 0), 1);
        $payFreqMax = max((float) ($payFrequencyChart->max('attendance_rate') ?? 0), 1);
        $hasMissingPayFrequency = collect($missingPayFrequencyDetails ?? [])->isNotEmpty();

        $chartLabels = $months->pluck('label')->values();
        $attendanceAverageSeries = $months
            ->map(fn ($month) => round((float) ($month['attendance_rate'] ?? 0), 2))
            ->values();
        $attendanceTargetSeries = $months
            ->map(fn ($month) => round((float) ($month['target_attendance'] ?? 90), 2))
            ->values();
        $averageAttendanceTarget = $attendanceTargetSeries->isNotEmpty()
            ? round((float) $attendanceTargetSeries->avg(), 2)
            : 90;
        $monthlyWorkdayActualSeries = collect($monthlyPayFrequencyWorkdayChart ?? [])
            ->map(fn ($month) => round((float) ($month['actual_workday'] ?? 0), 2))
            ->values();
        $monthlyWorkdayTargetSeries = collect($monthlyPayFrequencyWorkdayChart ?? [])
            ->map(fn ($month) => round((float) ($month['target_workday'] ?? 0), 2))
            ->values();
        $dailyWorkdayActualSeries = collect($dailyPayFrequencyWorkdayChart ?? [])
            ->map(fn ($month) => round((float) ($month['actual_workday'] ?? 0), 2))
            ->values();
        $dailyWorkdayTargetSeries = collect($dailyPayFrequencyWorkdayChart ?? [])
            ->map(fn ($month) => round((float) ($month['target_workday'] ?? 0), 2))
            ->values();
        $monthlyWorkdaySnapshot = collect($monthlyPayFrequencyWorkdayChart ?? [])
            ->filter(fn ($month) => ((int) ($month['employee_count'] ?? 0)) > 0 || ((float) ($month['actual_workday'] ?? 0)) > 0)
            ->last();
        $monthlyWorkdayLabel = $formatMonthLabelId(data_get($monthlyWorkdaySnapshot, 'month'));
        $monthlyWorkdayActual = (float) data_get($monthlyWorkdaySnapshot, 'actual_workday', 0);
        $monthlyWorkdayTarget = (float) data_get($monthlyWorkdaySnapshot, 'target_workday', 0);
        $monthlyWorkdayEmployeeCount = (int) data_get($monthlyWorkdaySnapshot, 'employee_count', 0);
        $dailyWorkdaySnapshot = collect($dailyPayFrequencyWorkdayChart ?? [])
            ->filter(fn ($month) => ((int) ($month['employee_count'] ?? 0)) > 0 || ((float) ($month['actual_workday'] ?? 0)) > 0)
            ->last();
        $dailyWorkdayLabel = $formatMonthLabelId(data_get($dailyWorkdaySnapshot, 'month'));
        $dailyWorkdayActual = (float) data_get($dailyWorkdaySnapshot, 'actual_workday', 0);
        $dailyWorkdayTarget = (float) data_get($dailyWorkdaySnapshot, 'target_workday', 0);
        $dailyWorkdayEmployeeCount = (int) data_get($dailyWorkdaySnapshot, 'employee_count', 0);
    @endphp

    <section class="space-y-6 overflow-x-hidden">
        <section class="dashboard-card overflow-visible">
            <div>
                <p class="dashboard-eyebrow">Attendance Admin Dashboard</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Dashboard Hari Kerja - {{ $companyName }}</h2>
            </div>

            <form method="GET" action="{{ route('dashboard.workday') }}" class="relative mt-6">
                <div class="employee-filter-card rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/60 p-5 shadow-sm">
                    <div
                        @class([
                            'grid gap-4 md:grid-cols-2 xl:grid-cols-4',
                            '2xl:grid-cols-6' => $selectedCompany === 'servanda',
                            '2xl:grid-cols-5' => $selectedCompany !== 'servanda',
                        ])
                    >
                        <x-dashboard.filter-select
                            label="Nama PT"
                            name="company"
                            :options="$companyOptions"
                            :selected="$selectedCompany"
                            placeholder="Semua"
                            :field-class="$workdayFilterFieldClass"
                            :label-class="$workdayFilterLabelClass"
                        />

                        @if ($selectedCompany === 'servanda')
                            <x-dashboard.filter-select
                                label="Divisi"
                                name="division"
                                :options="$divisionOptions"
                                :selected="$selectedDivision"
                                placeholder="Semua"
                                :locked="filled($forcedDivision)"
                                :locked-value="$forcedDivision"
                                :locked-label="$forcedDivision"
                                :field-class="$workdayFilterFieldClass"
                                :label-class="$workdayFilterLabelClass"
                            />
                        @endif

                        <x-dashboard.filter-select
                            label="Tahun"
                            name="year"
                            :options="$yearOptions"
                            :selected="$selectedYear"
                            placeholder="Semua"
                            :field-class="$workdayFilterFieldClass"
                            :label-class="$workdayFilterLabelClass"
                        />

                        <x-dashboard.filter-select
                            label="Cabang"
                            name="branch"
                            :options="$branchOptions"
                            :selected="$selectedBranch"
                            placeholder="Semua"
                            :field-class="$workdayFilterFieldClass"
                            :label-class="$workdayFilterLabelClass"
                        />

                        <x-dashboard.filter-select
                            label="Area Manager"
                            name="area_manager"
                            :options="$areaManagers"
                            :selected="$selectedAreaManager"
                            placeholder="Semua"
                            :locked="filled($forcedAreaManager)"
                            :locked-value="$forcedAreaManager"
                            :locked-label="$forcedAreaManager"
                            :field-class="$workdayFilterFieldClass"
                            :label-class="$workdayFilterLabelClass"
                        />

                        <x-dashboard.filter-select
                            label="Operation Manager"
                            name="operation_manager"
                            :options="$operationManagers"
                            :selected="$selectedOperationManager"
                            placeholder="Semua"
                            :locked="filled($forcedOperationManager)"
                            :locked-value="$forcedOperationManager"
                            :locked-label="$forcedOperationManager"
                            :field-class="$workdayFilterFieldClass"
                            :label-class="$workdayFilterLabelClass"
                        />
                    </div>
                </div>
            </form>
        </section>

        <section class="grid gap-6">
            {{-- <div class="dashboard-card min-w-0 overflow-hidden border border-slate-200 bg-white"> --}}
                {{-- <div class="workday-summary-grid"> --}}
                    {{-- <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Rata-rata</span>
                            <span class="block">Kehadiran</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($currentMonthAttendance, 2) }}%</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $currentMonthLabel }}
                            </span>
                        </div>
                    </article> --}}

                    {{-- <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Target</span>
                            <span class="block">Hari Kerja</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($currentMonthWorkdayTarget, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $currentMonthLabel }}
                            </span>
                        </div>
                    </article> --}}

                    {{-- <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Rata-rata</span>
                            <span class="block">Hari Kerja</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($currentMonthWorkday, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $currentMonthLabel }}
                            </span>
                        </div>
                    </article> --}}

                    {{-- <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Rata-rata</span>
                            <span class="block">Jadwal Shift</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($currentMonthShift, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $currentMonthLabel }}
                            </span>
                        </div>
                    </article> --}}

                    {{-- <article class="workday-summary-card border border-emerald-100 bg-gradient-to-br from-white via-emerald-50/70 to-emerald-100/80 shadow-sm shadow-emerald-100/70">
                        <div class="workday-summary-top">
                            <h3 class="workday-summary-title text-emerald-700">
                                <span class="block">Bulan</span>
                                <span class="block">Terbaik</span>
                            </h3>
                            <p class="workday-summary-rate text-emerald-700">{{ number_format($bestMonthRate, 2) }}%</p>
                        </div>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ $bestMonthLabel }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-emerald-200 bg-white/90 text-emerald-700">
                                Tertinggi
                            </span>
                        </div>
                    </article> --}}

                    {{-- <article class="workday-summary-card border border-rose-100 bg-gradient-to-br from-white via-rose-50/70 to-rose-100/80 shadow-sm shadow-rose-100/70">
                        <div class="workday-summary-top">
                            <h3 class="workday-summary-title text-rose-700">
                                <span class="block">Bulan</span>
                                <span class="block">Terendah</span>
                            </h3>
                            <p class="workday-summary-rate text-rose-700">{{ number_format($lowestMonthRate, 2) }}%</p>
                        </div>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ $lowestMonthLabel }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-rose-200 bg-white/90 text-rose-700">
                                Terendah
                            </span>
                        </div>
                    </article> --}}
                {{-- </div>

                <div class="mt-6 border-t border-slate-100 pt-5">
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900">Tren Rata-rata Kehadiran per Bulan</h3>
                </div>

                <div class="mt-4 overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-3 md:p-4">
                    <div class="h-[240px] md:h-[260px] xl:h-[280px] 2xl:h-[300px]">
                        <canvas id="workdayLineChart"></canvas>
                    </div>
                </div>
            </div> --}}

        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article class="dashboard-card min-w-0 overflow-hidden border border-slate-200 bg-white">
                <div class="workday-detail-summary-grid mb-6">
                    <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Realisasi</span>
                            <span class="block">Hari Kerja Bulanan</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($monthlyWorkdayActual, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $monthlyWorkdayLabel }}
                            </span>
                        </div>
                    </article>

                    <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Target</span>
                            <span class="block">Hari Kerja Bulanan</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($monthlyWorkdayTarget, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $monthlyWorkdayLabel }}
                            </span>
                        </div>
                    </article>

                    <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Total Pegawai</span>
                            <span class="block">Bulanan</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($monthlyWorkdayEmployeeCount) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $monthlyWorkdayLabel }}
                            </span>
                        </div>
                    </article>
                </div>

                <div class="border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900">Tren Hari Kerja Pegawai Bulanan</h3>
                </div>

                <div class="mt-4 overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-3 md:p-4">
                    <div class="h-[240px] md:h-[260px] xl:h-[280px]">
                        <canvas id="monthlyWorkdayLineChart"></canvas>
                    </div>
                </div>
            </article>

            <article class="dashboard-card min-w-0 overflow-hidden border border-slate-200 bg-white">
                <div class="workday-detail-summary-grid mb-6">
                    <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Target</span>
                            <span class="block">Hari Kerja Harian</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($dailyWorkdayTarget, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $dailyWorkdayLabel }}
                            </span>
                        </div>
                    </article>

                    <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Realisasi</span>
                            <span class="block">Hari Kerja Harian</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($dailyWorkdayActual, 2) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $dailyWorkdayLabel }}
                            </span>
                        </div>
                    </article>

                    <article class="workday-summary-card border border-sky-100 bg-gradient-to-br from-white via-slate-50 to-sky-50 shadow-sm shadow-sky-100/70">
                        <h3 class="workday-summary-title text-sky-700">
                            <span class="block">Total Pegawai</span>
                            <span class="block">Harian</span>
                        </h3>
                        <div class="workday-summary-value-wrap">
                            <p class="workday-summary-value">{{ number_format($dailyWorkdayEmployeeCount) }}</p>
                        </div>
                        <div class="workday-summary-footer">
                            <span class="workday-summary-badge border border-sky-200 bg-white/90 text-sky-700">
                                {{ $dailyWorkdayLabel }}
                            </span>
                        </div>
                    </article>
                </div>

                <div class="border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold tracking-tight text-slate-900">Tren Hari Kerja Pegawai Harian</h3>
                </div>

                <div class="mt-4 overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white p-3 md:p-4">
                    <div class="h-[240px] md:h-[260px] xl:h-[280px]">
                        <canvas id="dailyWorkdayLineChart"></canvas>
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-6 lg:grid-cols-2">
            <article x-data="{ search: '' }" class="dashboard-card min-w-0 border border-slate-200 bg-white">
                <div class="border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Attendance by Position</h3>
                </div>

                <div class="mt-4">
                    <input
                        x-model="search"
                        type="text"
                        placeholder="Cari position..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-300 focus:bg-white"
                    >
                </div>

                <div class="mt-5 space-y-4 max-h-[560px] overflow-y-auto pr-1">
                    @forelse ($positionChart as $item)
                        <div x-show="@js(strtolower($item['label'])).includes(search.toLowerCase())" x-transition.opacity>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="line-clamp-1 font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ number_format($item['attendance_rate'], 2) }}%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-blue-600 to-sky-400" style="width: {{ min(($item['attendance_rate'] / $positionMax) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data position.</div>
                    @endforelse
                    <div
                        x-show="!Array.from($el.parentElement.querySelectorAll('[x-show]')).some((element) => element.style.display !== 'none')"
                        class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                    >
                        Data position tidak ditemukan.
                    </div>
                </div>
            </article>

            <article x-data="{ search: '' }" class="dashboard-card min-w-0 border border-slate-200 bg-white">
                <div class="border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Attendance by Area</h3>
                </div>

                <div class="mt-4">
                    <input
                        x-model="search"
                        type="text"
                        placeholder="Cari area..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-300 focus:bg-white"
                    >
                </div>

                <div class="mt-5 space-y-4 max-h-[560px] overflow-y-auto pr-1">
                    @forelse ($areaChart as $item)
                        <div x-show="@js(strtolower($item['label'])).includes(search.toLowerCase())" x-transition.opacity>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="line-clamp-1 font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ number_format($item['attendance_rate'], 2) }}%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-emerald-600 to-teal-400" style="width: {{ min(($item['attendance_rate'] / $areaMax) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data area.</div>
                    @endforelse
                    <div
                        x-show="!Array.from($el.parentElement.querySelectorAll('[x-show]')).some((element) => element.style.display !== 'none')"
                        class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                    >
                        Data area tidak ditemukan.
                    </div>
                </div>
            </article>

            <article x-data="{ showMissingPayFrequencyModal: false }" @keydown.escape.window="showMissingPayFrequencyModal = false" class="dashboard-card min-w-0 border border-slate-200 bg-white lg:col-span-2">
                <div class="border-b border-slate-200 pb-4">
                    <h3 class="text-lg font-semibold text-slate-900">Attendance by Jenis Gaji</h3>
                </div>

                <div class="mt-5 space-y-4 max-h-[560px] overflow-y-auto pr-1">
                    @forelse ($payFrequencyChart as $item)
                        @php
                            $isMissingPayFrequency = ($item['label'] ?? '') === 'Tanpa Data' && $hasMissingPayFrequency;
                        @endphp
                        <div>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="line-clamp-1 font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ number_format($item['attendance_rate'], 2) }}%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400" style="width: {{ min(($item['attendance_rate'] / $payFreqMax) * 100, 100) }}%"></div>
                            </div>
                            @if ($isMissingPayFrequency)
                                <div class="mt-2">
                                    <button
                                        type="button"
                                        @click="showMissingPayFrequencyModal = true"
                                        class="inline-flex items-center rounded-xl border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 shadow-sm transition hover:border-amber-300 hover:bg-amber-100"
                                    >
                                        Tampilkan
                                    </button>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data jenis gaji.</div>
                    @endforelse
                </div>

                @if ($hasMissingPayFrequency)
                    <div
                        x-show="showMissingPayFrequencyModal"
                        style="display: none;"
                        class="fixed inset-0 z-[80] flex items-center justify-center p-4"
                    >
                        <div class="absolute inset-0 bg-slate-950/45 backdrop-blur-sm" @click="showMissingPayFrequencyModal = false"></div>

                        <div
                            x-show="showMissingPayFrequencyModal"
                            x-transition.opacity.scale.95
                            class="relative z-10 flex max-h-[85vh] w-full max-w-6xl flex-col overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-2xl shadow-slate-900/15"
                        >
                            <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
                                <div>
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Detail Jenis Gaji</p>
                                    <h4 class="mt-1 text-xl font-semibold text-slate-900">Daftar Pegawai Tanpa Data Jenis Gaji</h4>
                                    <p class="mt-2 text-sm text-slate-500">{{ number_format(collect($missingPayFrequencyDetails)->count()) }} pegawai perlu dilengkapi data jenis gajinya.</p>
                                </div>

                                <div class="flex items-center gap-3">
                                    <button
                                        type="button"
                                        @click="window.exportMissingPayFrequencyExcel('missingPayFrequencyTable', 'jenis-gaji-tanpa-data-{{ $selectedCompany }}-{{ now()->format('Y-m-d_H-i-s') }}.xls')"
                                        class="inline-flex items-center rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100"
                                    >
                                        Export Excel
                                    </button>

                                    <button
                                        type="button"
                                        @click="showMissingPayFrequencyModal = false"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-slate-50 text-slate-500 transition hover:border-slate-300 hover:bg-slate-100 hover:text-slate-700"
                                        aria-label="Tutup modal"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M18 6 6 18" />
                                            <path d="m6 6 12 12" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <div class="overflow-auto px-6 py-5">
                                <div class="table-responsive">
                                    <table id="missingPayFrequencyTable" class="min-w-full divide-y divide-slate-200">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Nama Pegawai</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">NIK / Employee ID</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Divisi</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Area</th>
                                                <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-[0.18em] text-slate-500">Keterangan</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach ($missingPayFrequencyDetails as $detail)
                                                <tr>
                                                    <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $detail['employee_name'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $detail['employee_no'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $detail['division'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $detail['branch'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $detail['area'] }}</td>
                                                    <td class="px-4 py-3 text-sm text-slate-600">{{ $detail['status_note'] }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </article>
        </section>

    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            window.exportMissingPayFrequencyExcel = (tableId, fileName) => {
                const table = document.getElementById(tableId);

                if (!table) {
                    return;
                }

                const workbookHtml = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
                        <head>
                            <meta charset="UTF-8">
                            <style>
                                table { border-collapse: collapse; width: 100%; }
                                th, td { border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; }
                                th { background: #f8fafc; font-weight: 700; }
                            </style>
                        </head>
                        <body>${table.outerHTML}</body>
                    </html>
                `;

                const blob = new Blob(["\ufeff", workbookHtml], {
                    type: 'application/vnd.ms-excel;charset=utf-8;',
                });
                const downloadUrl = URL.createObjectURL(blob);
                const link = document.createElement('a');

                link.href = downloadUrl;
                link.download = fileName;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(downloadUrl);
            };

            if (typeof Chart === 'undefined') {
                return;
            }

            const canvas = document.getElementById('workdayLineChart');
            const labels = @json($chartLabels);
            const attendanceAverageSeries = @json($attendanceAverageSeries);
            const attendanceTargetSeries = @json($attendanceTargetSeries);
            const averageAttendanceTarget = {{ $averageAttendanceTarget }};
            const monthlyWorkdayActualSeries = @json($monthlyWorkdayActualSeries);
            const monthlyWorkdayTargetSeries = @json($monthlyWorkdayTargetSeries);
            const dailyWorkdayActualSeries = @json($dailyWorkdayActualSeries);
            const dailyWorkdayTargetSeries = @json($dailyWorkdayTargetSeries);
            const noActivityColor = '#94a3b8';
            const aboveTargetColor = '#16a34a';
            const belowTargetColor = '#dc2626';
            const neutralLineColor = '#f43f5e';
            const targetLineColor = '#3b82f6';
            const buildWorkdayComparisonChart = (canvasId, actualSeries, targetSeries, targetLabel) => {
                const element = document.getElementById(canvasId);

                if (!element) {
                    return;
                }

                const chartContext = element.getContext('2d');
                const lineGradient = chartContext.createLinearGradient(0, 0, 0, 320);
                lineGradient.addColorStop(0, 'rgba(59, 130, 246, 0.22)');
                lineGradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');

                const maxValue = Math.max(
                    ...actualSeries.map((value) => Number(value ?? 0)),
                    ...targetSeries.map((value) => Number(value ?? 0)),
                    0
                );

                new Chart(element, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Realisasi Hari Kerja',
                                data: actualSeries,
                                borderColor: belowTargetColor,
                                backgroundColor: lineGradient,
                                fill: true,
                                tension: 0.15,
                                pointRadius: 4.5,
                                pointHoverRadius: 6,
                                pointBackgroundColor(context) {
                                    const value = Number(context.raw ?? 0);
                                    const targetValue = Number(targetSeries[context.dataIndex] ?? 0);

                                    if (value === 0 && targetValue === 0) {
                                        return noActivityColor;
                                    }

                                    return value >= targetValue ? aboveTargetColor : belowTargetColor;
                                },
                                pointBorderColor(context) {
                                    const value = Number(context.raw ?? 0);
                                    const targetValue = Number(targetSeries[context.dataIndex] ?? 0);

                                    if (value === 0 && targetValue === 0) {
                                        return noActivityColor;
                                    }

                                    return value >= targetValue ? aboveTargetColor : belowTargetColor;
                                },
                                pointBorderWidth: 2,
                                borderWidth: 2,
                                segment: {
                                    borderColor(context) {
                                        const startValue = Number(context.p0.parsed.y ?? 0);
                                        const endValue = Number(context.p1.parsed.y ?? 0);
                                        const endTarget = Number(targetSeries[context.p1DataIndex] ?? 0);

                                        if (startValue === 0 || endValue === 0) {
                                            return noActivityColor;
                                        }

                                        return endValue >= endTarget ? aboveTargetColor : belowTargetColor;
                                    }
                                }
                            },
                            {
                                label: targetLabel,
                                data: targetSeries,
                                borderColor: targetLineColor,
                                backgroundColor: targetLineColor,
                                fill: false,
                                tension: 0,
                                pointRadius: 3.5,
                                pointHoverRadius: 5,
                                pointBackgroundColor: targetLineColor,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                borderWidth: 2,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 12,
                                right: 8,
                                bottom: 4,
                                left: 8,
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    padding: 16,
                                    color: '#475569',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#ffffff',
                                bodyColor: '#e2e8f0',
                                padding: 12,
                                displayColors: true,
                                callbacks: {
                                    title(context) {
                                        return context[0]?.label ?? '';
                                    },
                                    label(context) {
                                        const value = Number(context.parsed.y ?? 0).toLocaleString('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        });

                                        return `${context.dataset.label}: ${value}`;
                                    }
                                }
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: '#64748b',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                suggestedMax: maxValue > 0 ? Math.ceil(maxValue + 2) : 5,
                                grid: {
                                    display: false,
                                    drawBorder: false,
                                },
                                ticks: {
                                    display: false
                                },
                                border: {
                                    display: false
                                }
                            }
                        }
                    },
                });
            };

            if (canvas) {
                const chartContext = canvas.getContext('2d');
                const lineGradient = chartContext.createLinearGradient(0, 0, 0, 320);
                lineGradient.addColorStop(0, 'rgba(59, 130, 246, 0.22)');
                lineGradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');

                new Chart(canvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Rata-rata Kehadiran %',
                                data: attendanceAverageSeries,
                                borderColor: neutralLineColor,
                                backgroundColor: lineGradient,
                                fill: true,
                                tension: 0.15,
                                pointRadius: 4.5,
                                pointHoverRadius: 6,
                                pointBackgroundColor(context) {
                                    const value = Number(context.raw ?? 0);

                                    if (value === 0) {
                                        return noActivityColor;
                                    }

                                    return value >= Number(attendanceTargetSeries[context.dataIndex] ?? averageAttendanceTarget)
                                        ? aboveTargetColor
                                        : belowTargetColor;
                                },
                                pointBorderColor(context) {
                                    const value = Number(context.raw ?? 0);

                                    if (value === 0) {
                                        return noActivityColor;
                                    }

                                    return value >= Number(attendanceTargetSeries[context.dataIndex] ?? averageAttendanceTarget)
                                        ? aboveTargetColor
                                        : belowTargetColor;
                                },
                                pointBorderWidth: 2,
                                borderWidth: 2,
                                segment: {
                                    borderColor(context) {
                                        const startValue = Number(context.p0.parsed.y ?? 0);
                                        const endValue = Number(context.p1.parsed.y ?? 0);

                                        if (startValue === 0 || endValue === 0) {
                                            return noActivityColor;
                                        }
                                        const endTarget = Number(attendanceTargetSeries[context.p1DataIndex] ?? averageAttendanceTarget);

                                        return endValue >= endTarget
                                            ? aboveTargetColor
                                            : belowTargetColor;
                                    }
                                }
                            },
                            {
                                label: 'Target Kehadiran',
                                data: attendanceTargetSeries,
                                borderColor: targetLineColor,
                                backgroundColor: targetLineColor,
                                fill: false,
                                tension: 0,
                                pointRadius: 4,
                                pointHoverRadius: 5,
                                pointBackgroundColor: targetLineColor,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                borderWidth: 2,
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        layout: {
                            padding: {
                                top: 24,
                                right: 12,
                                bottom: 8,
                                left: 12,
                            }
                        },
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                                align: 'end',
                                labels: {
                                    usePointStyle: true,
                                    boxWidth: 10,
                                    boxHeight: 10,
                                    padding: 18,
                                    color: '#475569',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                    }
                                }
                            },
                            tooltip: {
                                backgroundColor: '#0f172a',
                                titleColor: '#ffffff',
                                bodyColor: '#e2e8f0',
                                padding: 12,
                                displayColors: true,
                                callbacks: {
                                    title: function(context) {
                                        return context[0]?.label ?? '';
                                    },
                                    label: function(context) {
                                        const value = Number(context.parsed.y).toLocaleString('id-ID', {
                                            minimumFractionDigits: 2,
                                            maximumFractionDigits: 2,
                                        });

                                        return `${context.dataset.label}: ${value}%`;
                                    },
                                    afterLabel: function(context) {
                                        if (context.datasetIndex !== 0) {
                                            return 'Acuan target kehadiran bulanan';
                                        }

                                        const value = Number(context.parsed.y);
                                        const targetValue = Number(attendanceTargetSeries[context.dataIndex] ?? averageAttendanceTarget);

                                        if (value === 0) {
                                            return 'Status: Tidak ada aktivitas';
                                        }

                                        return value >= targetValue
                                            ? 'Status: Di atas target'
                                            : 'Status: Di bawah target';
                                    }
                                }
                            },
                        },
                        scales: {
                            x: {
                                grid: {
                                    display: false,
                                    drawBorder: false,
                                },
                                ticks: {
                                    color: '#64748b',
                                    font: {
                                        size: 11,
                                        weight: '600',
                                    }
                                }
                            },
                            y: {
                                beginAtZero: true,
                                max: 105,
                                grid: {
                                    display: false,
                                    drawBorder: false,
                                },
                                ticks: {
                                    display: false
                                },
                                border: {
                                    display: false
                                }
                            }
                        }
                    },
                });
            }

            buildWorkdayComparisonChart(
                'monthlyWorkdayLineChart',
                monthlyWorkdayActualSeries,
                monthlyWorkdayTargetSeries,
                'Target Hari Kerja Bulanan'
            );

            buildWorkdayComparisonChart(
                'dailyWorkdayLineChart',
                dailyWorkdayActualSeries,
                dailyWorkdayTargetSeries,
                'Target Hari Kerja Harian'
            );

        })();
    </script>
@endsection
