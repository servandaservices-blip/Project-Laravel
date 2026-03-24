@extends('layouts.dashboard')

@section('title', 'Attendance Summary')
@section('page_title', 'Attendance Summary')
@section('page_subtitle', 'Report attendance per periode')

@section('content')
    @php
        $emptyFilterValue = '__EMPTY__';
        $selectedPositionFilters = collect($selectedPositionFilters ?? [])->filter()->values()->all();
        $selectedAreaFilters = collect($selectedAreaFilters ?? [])->filter()->values()->all();
        $selectedPositionCollection = collect($selectedPositionFilters);
        $selectedAreaCollection = collect($selectedAreaFilters);
        $selectedAreaManager = $selectedAreaManager ?? '';
        $selectedOperationManager = $selectedOperationManager ?? '';
        $divisionOptions = [$emptyFilterValue, 'Security', 'Cleaning'];
        $forcedDivision = auth()->user()?->forcedDivision();
        $multiSelectSummary = function (array $selected, string $defaultLabel) use ($emptyFilterValue): string {
            if (count($selected) === 0) {
                return $defaultLabel;
            }

            $displayValue = fn ($value) => $value === $emptyFilterValue ? 'Tanpa Data' : $value;

            if (count($selected) === 1) {
                return $displayValue($selected[0]);
            }

            return $displayValue($selected[0]) . ' +' . (count($selected) - 1);
        };
        $buildFilterActionUrl = function (string $key, array $values = []) {
            $query = request()->query();
            unset($query[$key]);

            if ($values !== []) {
                $query[$key] = $values;
            }

            return route('attendance.summary', $query);
        };
        $attendanceFilterLabelClass = 'mb-2 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500';
        $attendanceFilterFieldClass = 'w-full rounded-2xl border border-sky-200 bg-sky-50/40 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm transition placeholder:text-slate-400 focus:border-sky-400 focus:outline-none focus:ring-4 focus:ring-sky-100';
        $attendanceResetUrl = route('attendance.summary', array_filter([
            'company' => $selectedCompany ?? 'servanda',
            'month' => $selectedMonth,
            'year' => $selectedYear,
            'per_page' => $selectedPerPage ?? 10,
            'division' => (($selectedCompany ?? 'servanda') === 'servanda' && filled($forcedDivision)) ? $forcedDivision : null,
        ], fn ($value) => $value !== null && $value !== ''));
    @endphp
    <section class="dashboard-card overflow-visible">
        @if (session('success'))
            <div class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="rounded-[2rem] border border-slate-200 bg-white p-6 shadow-sm">
            <div class="max-w-4xl">
                <p class="dashboard-eyebrow text-sky-700">Attendance Report</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Attendance Summary - {{ $companyName ?? 'Servanda' }}</h2>
                <p class="mt-2 text-sm text-slate-500">Ringkasan attendance per periode untuk memantau kehadiran, hari kerja, dan penempatan karyawan.</p>
                <div class="mt-5 flex flex-wrap gap-3">
                    <span class="inline-flex items-center rounded-full border border-sky-200 bg-sky-50 px-3 py-1.5 text-xs font-semibold tracking-[0.12em] text-sky-700 shadow-sm">
                        Periode {{ $period['period_label'] }}
                    </span>
                    <span class="inline-flex items-center rounded-full border border-cyan-200 bg-cyan-50 px-3 py-1.5 text-xs font-semibold tracking-[0.08em] text-cyan-700 shadow-sm">
                        {{ $period['period_start'] }} s/d {{ $period['period_end'] }}
                    </span>
                </div>
            </div>

            <div class="mt-6">
                <form method="POST" action="{{ route('attendance.summary.sync') }}" class="attendance-summary-sync-grid">
                    @csrf
                    <input type="hidden" name="company" value="{{ $selectedCompany ?? 'servanda' }}">
                    @if (($selectedCompany ?? 'servanda') === 'servanda' && ! empty($selectedDivision))
                        <input type="hidden" name="division" value="{{ $selectedDivision }}">
                    @endif
                    <input type="hidden" name="per_page" value="{{ $selectedPerPage ?? 10 }}">
                    <input type="hidden" name="search" value="{{ $selectedSearch ?? '' }}">
                    <input type="hidden" name="attendance_rate_filter" value="{{ $selectedAttendanceRateFilter ?? '' }}">
                    @foreach ($selectedPositionCollection as $position)
                        <input type="hidden" name="position[]" value="{{ $position }}">
                    @endforeach
                    <input type="hidden" name="pay_freq" value="{{ $selectedPayFrequencyFilter ?? '' }}">
                    @foreach ($selectedAreaCollection as $area)
                        <input type="hidden" name="area[]" value="{{ $area }}">
                    @endforeach
                    <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                    <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">

                    <div>
                        <label class="{{ $attendanceFilterLabelClass }}">Bulan</label>
                        <select name="month" class="{{ $attendanceFilterFieldClass }}">
                            @for ($m = 1; $m <= 12; $m++)
                                <option value="{{ $m }}" @selected($selectedMonth == $m)>
                                    {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <div>
                        <label class="{{ $attendanceFilterLabelClass }}">Tahun</label>
                        <select name="year" class="{{ $attendanceFilterFieldClass }}">
                            @for ($y = 2024; $y <= 2030; $y++)
                                <option value="{{ $y }}" @selected($selectedYear == $y)>
                                    {{ $y }}
                                </option>
                            @endfor
                        </select>
                    </div>

                    <button type="submit" class="inline-flex h-[52px] w-full items-center justify-center rounded-2xl bg-slate-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        Sinkronisasi
                    </button>
                </form>
            </div>
        </div>
    </section>

    <section class="dashboard-card mt-6 border border-slate-200 bg-white">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance Table</p>
                <h3 class="text-xl font-semibold text-slate-900">Tabel Attendance Summary</h3>
                <p class="mt-1 text-sm text-slate-500">Data attendance operasional per employee sesuai periode, filter penempatan, dan jenis gaji yang dipilih.</p>
            </div>
            <div class="inline-flex items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-600 shadow-sm">
                Total {{ number_format($summaryStats['employee_count'] ?? $reports->total()) }} employee
            </div>
        </div>

        <form
            id="attendance-filter-form"
            method="GET"
            action="{{ route('attendance.summary') }}"
            class="mt-5 space-y-5"
        >
            <input type="hidden" name="company" value="{{ $selectedCompany ?? 'servanda' }}">
            <input type="hidden" name="month" value="{{ $selectedMonth }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">

            <div class="attendance-summary-toolbar">
                <div>
                    <label class="{{ $attendanceFilterLabelClass }}">Search Employee / Employee No / Area</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ $selectedSearch ?? '' }}"
                        placeholder="Cari nama karyawan, employee no, atau area"
                        class="{{ $attendanceFilterFieldClass }}"
                    >
                </div>

                <div class="attendance-summary-toolbar-side">
                    <div class="attendance-summary-show-field">
                        <label class="{{ $attendanceFilterLabelClass }}">Show</label>
                        <select id="per_page" name="per_page" onchange="this.form.submit()" class="{{ $attendanceFilterFieldClass }}">
                            @foreach ([10, 50, 100] as $pageSize)
                                <option value="{{ $pageSize }}" @selected(($selectedPerPage ?? 10) == $pageSize)>{{ $pageSize }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            <div class="attendance-summary-filter-card">
                <div class="attendance-summary-filter-grid">
                    @if (($selectedCompany ?? 'servanda') === 'servanda')
                        <div class="attendance-summary-filter-panel">
                            <label class="{{ $attendanceFilterLabelClass }}">Divisi</label>
                            @if (filled($forcedDivision))
                                <input type="hidden" name="division" value="{{ $forcedDivision }}">
                                <div class="flex min-h-[52px] items-center rounded-2xl border border-sky-200 bg-sky-50/60 px-4 text-sm font-semibold text-sky-800">
                                    {{ $forcedDivision }}
                                </div>
                            @else
                                <select name="division" class="{{ $attendanceFilterFieldClass }}">
                                    <option value="">Semua</option>
                                    @foreach ($divisionOptions as $division)
                                        <option value="{{ $division }}" @selected(($selectedDivision ?? null) === $division)>{{ $division === $emptyFilterValue ? 'Tanpa Data' : $division }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    @endif

                    <div class="attendance-summary-filter-panel">
                        <label class="{{ $attendanceFilterLabelClass }}">Filter Attendance %</label>
                        <select name="attendance_rate_filter" class="{{ $attendanceFilterFieldClass }}">
                            <option value="" @selected(($selectedAttendanceRateFilter ?? '') === '')>Semua</option>
                            <option value="gte_90" @selected(($selectedAttendanceRateFilter ?? '') === 'gte_90')>Baik</option>
                            <option value="lt_90" @selected(($selectedAttendanceRateFilter ?? '') === 'lt_90')>Perlu Perhatian</option>
                        </select>

                        <div class="mt-4">
                            <label class="{{ $attendanceFilterLabelClass }}">Gaji</label>
                            <select name="pay_freq" class="{{ $attendanceFilterFieldClass }}">
                                <option value="">Semua</option>
                                @foreach ($payFrequencies as $payFrequency)
                                    <option value="{{ $payFrequency }}" @selected(($selectedPayFrequencyFilter ?? '') === $payFrequency)>
                                        {{ $payFrequency }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="attendance-summary-filter-panel">
                        <label class="{{ $attendanceFilterLabelClass }}">Position</label>
                        <details class="group relative z-30" x-data="{ search: '' }">
                            <summary class="attendance-summary-select-trigger">
                                <span class="truncate">{{ $multiSelectSummary($selectedPositionFilters, 'Semua') }}</span>
                                <span class="ml-3 text-sky-500 transition group-open:rotate-180">v</span>
                            </summary>

                            <div class="attendance-summary-dropdown">
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
                                        class="attendance-summary-search-input"
                                    >
                                </div>

                                <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                    @foreach ($positions as $position)
                                        <label x-show="'{{ str($position)->lower()->replace("'", "\\'") }}'.includes(search.toLowerCase())" class="attendance-summary-option">
                                            <input
                                                type="checkbox"
                                                name="position[]"
                                                value="{{ $position }}"
                                                @checked(in_array($position, $selectedPositionFilters, true))
                                                class="attendance-summary-checkbox"
                                            >
                                            <span>{{ $position === $emptyFilterValue ? 'Tanpa Data' : $position }}</span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        </details>

                        <div class="mt-4">
                            <label class="{{ $attendanceFilterLabelClass }}">Area Penempatan</label>
                            <details class="group relative z-30" x-data="{ search: '' }">
                                <summary class="attendance-summary-select-trigger">
                                    <span class="truncate">{{ $multiSelectSummary($selectedAreaFilters, 'Semua') }}</span>
                                    <span class="ml-3 text-sky-500 transition group-open:rotate-180">v</span>
                                </summary>

                                <div class="attendance-summary-dropdown">
                                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-slate-100 pb-3">
                                        <span class="text-xs text-slate-500">Pilih satu atau beberapa area penempatan.</span>
                                        <div class="flex shrink-0 items-center gap-3 text-xs font-semibold">
                                            <a href="{{ $buildFilterActionUrl('area') }}" class="text-slate-500 hover:text-slate-800">
                                                Reset
                                            </a>
                                            <a href="{{ $buildFilterActionUrl('area', $areas->values()->all()) }}" class="text-emerald-600 hover:text-emerald-800">
                                                Pilih Semua
                                            </a>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <input
                                            x-model="search"
                                            type="text"
                                            placeholder="Search area penempatan"
                                            class="attendance-summary-search-input"
                                        >
                                    </div>

                                    <div class="max-h-56 space-y-2 overflow-y-auto pr-1">
                                        @foreach ($areas as $area)
                                            <label x-show="'{{ str($area)->lower()->replace("'", "\\'") }}'.includes(search.toLowerCase())" class="attendance-summary-option">
                                                <input
                                                    type="checkbox"
                                                    name="area[]"
                                                    value="{{ $area }}"
                                                    @checked(in_array($area, $selectedAreaFilters, true))
                                                    class="attendance-summary-checkbox"
                                                >
                                                <span>{{ $area === $emptyFilterValue ? 'Tanpa Data' : $area }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </div>
                            </details>
                        </div>
                    </div>

                    <div class="attendance-summary-filter-panel">
                        <label class="{{ $attendanceFilterLabelClass }}">Area Manager</label>
                        <select name="area_manager" class="{{ $attendanceFilterFieldClass }}">
                            <option value="">Semua</option>
                            @foreach ($areaManagers as $areaManager)
                                <option value="{{ $areaManager }}" @selected($selectedAreaManager === $areaManager)>
                                    {{ $areaManager === $emptyFilterValue ? 'Tanpa Data' : $areaManager }}
                                </option>
                            @endforeach
                        </select>

                        <div class="mt-4">
                            <label class="{{ $attendanceFilterLabelClass }}">Operation Manager</label>
                            <select name="operation_manager" class="{{ $attendanceFilterFieldClass }}">
                                <option value="">Semua</option>
                                @foreach ($operationManagers as $operationManager)
                                    <option value="{{ $operationManager }}" @selected($selectedOperationManager === $operationManager)>
                                        {{ $operationManager === $emptyFilterValue ? 'Tanpa Data' : $operationManager }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="attendance-summary-filter-actions">
                    <a
                        href="{{ $attendanceResetUrl }}"
                        class="inline-flex h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                    >
                        Reset Filter
                    </a>
                    <button
                        type="submit"
                        class="inline-flex h-[48px] items-center justify-center rounded-2xl bg-slate-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>

        <div class="mt-6 overflow-x-auto">
            <table class="attendance-summary-table min-w-full text-sm">
                <thead class="text-slate-600">
                    <tr>
                        <th class="px-6 py-4 text-left font-semibold uppercase tracking-[0.12em]">Employee</th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em]">Position</th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em]">Gaji</th>
                        <th class="px-4 py-4 text-left font-semibold uppercase tracking-[0.12em]">Area Penempatan</th>
                        <th class="px-4 py-4 text-center font-semibold uppercase tracking-[0.12em]">Workday</th>
                        <th class="px-4 py-4 text-center font-semibold uppercase tracking-[0.12em]">Jadwal Shift</th>
                        <th class="px-4 py-4 text-center font-semibold uppercase tracking-[0.12em]">Tidak Absen</th>
                        <th class="px-4 py-4 text-center font-semibold uppercase tracking-[0.12em]">Attendance %</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($reports as $report)
                        <tr>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-slate-900">{{ $report->employee_name }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $report->employee_no }}</p>
                                </div>
                            </td>
                            <td class="px-4 py-4 text-slate-700">{{ $report->position ?: '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $report->pay_freq ?: '-' }}</td>
                            <td class="px-4 py-4 text-slate-700">{{ $report->area ?: '-' }}</td>
                            <td class="px-4 py-4 text-center font-medium text-slate-700">{{ number_format($report->workday_count) }}</td>
                            <td class="px-4 py-4 text-center font-medium text-slate-700">{{ number_format($report->presence_count) }}</td>
                            <td class="px-4 py-4 text-center font-medium text-slate-700">{{ number_format($report->absent_count) }}</td>
                            <td class="px-4 py-4 text-center font-semibold {{ $report->attendance_rate < 90 ? 'text-rose-600' : 'text-emerald-700' }}">
                                {{ number_format($report->attendance_rate, 2) }}%
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-10 text-center text-sm text-slate-500">
                                Belum ada data attendance summary untuk periode ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($reports->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $reports->links() }}
            </div>
        @endif
    </section>

    <section class="dashboard-card mt-6" x-data="{ showInformation: false }">
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-5 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 class="text-xl font-semibold text-slate-900">Information</h3>
                <p class="mt-1 text-sm text-slate-500">
                    Penjelasan singkat agar isi tabel attendance summary lebih mudah dipahami saat dipakai operasional.
                </p>
            </div>

            <button
                type="button"
                @click="showInformation = !showInformation"
                class="inline-flex items-center gap-2 self-start rounded-2xl border border-sky-200 bg-sky-50 px-4 py-2.5 text-sm font-semibold text-sky-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-100"
            >
                <span x-text="showInformation ? 'Sembunyikan' : 'Selengkapnya'"></span>
                <svg x-bind:class="showInformation ? 'rotate-180' : ''" class="h-4 w-4 transition" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.51a.75.75 0 01-1.08 0l-4.25-4.51a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </button>
        </div>

        <div x-show="showInformation" x-collapse class="mt-6">
            <div class="rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-sky-50/60 p-6 shadow-sm">
                <h4 class="text-lg font-semibold text-slate-900">1. Tabel Attendance Summary</h4>
                <div class="mt-4 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Employee</p>
                        <p class="mt-2 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-600">Identitas</p>
                        <p class="mt-3 text-sm leading-6 text-slate-500">
                            Menampilkan nama karyawan beserta nomor induk atau employee number.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-fuchsia-200 bg-fuchsia-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Position</p>
                        <p class="mt-2 inline-flex rounded-full bg-fuchsia-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-fuchsia-700">Jabatan</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Diambil dari master employee dengan mencocokkan NIK atau employee number yang sama.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Gaji</p>
                        <p class="mt-2 inline-flex rounded-full bg-cyan-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-700">Pay Freq</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Jenis gaji yang ditampilkan mengikuti field <span class="font-semibold">pay_freq</span> dari data master employee.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-violet-200 bg-violet-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Area Penempatan</p>
                        <p class="mt-2 inline-flex rounded-full bg-violet-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-violet-700">Placement</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Area penempatan diambil dari area master employee, termasuk site area khusus untuk Servanda.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-sky-200 bg-sky-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Workday</p>
                        <p class="mt-2 inline-flex rounded-full bg-sky-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-sky-700">Hari Kerja</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Jumlah total hari kerja pada periode yang dipilih berdasarkan data summary SmartPresence.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-indigo-200 bg-indigo-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Jadwal Shift</p>
                        <p class="mt-2 inline-flex rounded-full bg-indigo-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-indigo-700">Kehadiran</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Total kehadiran karyawan pada hari kerja atau jadwal shift selama periode berjalan.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Tidak Absen</p>
                        <p class="mt-2 inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-amber-700">Ketidakhadiran</p>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Selisih antara Workday dan Jadwal Shift, sehingga terlihat berapa hari karyawan tidak hadir.
                        </p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/70 p-4 shadow-sm">
                        <p class="text-sm font-semibold text-slate-900">Attendance %</p>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">&gt;= 90% Baik</span>
                            <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-700">&lt; 90% Perlu perhatian</span>
                        </div>
                        <p class="mt-3 text-sm leading-6 text-slate-600">
                            Persentase kehadiran dari total Workday. Semakin tinggi nilainya, semakin baik tingkat kehadirannya.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
