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
        $divisionOptions = ['Security', 'Cleaning'];
        $forcedDivision = auth()->user()?->forcedDivision();
        $branchOptions = $branchOptions ?? collect();
        $areaManagers = $areaManagers ?? collect();
        $operationManagers = $operationManagers ?? collect();
        $yearOptions = range((int) now()->year - 2, 2030);
        $averageAttendance = (float) ($summaryCards['average_attendance_rate'] ?? 0);
        $averageWorkday = (float) ($summaryCards['avg_workday'] ?? 0);
        $averageShift = (float) ($summaryCards['avg_present'] ?? 0);
        $bestMonthLabel = $bestMonth['full_label'] ?? '-';
        $bestMonthRate = (float) ($bestMonth['attendance_rate'] ?? 0);
        $lowestMonthLabel = $lowestMonth['full_label'] ?? '-';
        $lowestMonthRate = (float) ($lowestMonth['attendance_rate'] ?? 0);

        $positionMax = max((float) ($positionChart->max('attendance_rate') ?? 0), 1);
        $areaMax = max((float) ($areaChart->max('attendance_rate') ?? 0), 1);
        $payFreqMax = max((float) ($payFrequencyChart->max('attendance_rate') ?? 0), 1);

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
        $avgWorkdaySeries = $months
            ->map(fn ($month) => round((float) ($month['avg_workday'] ?? 0), 2))
            ->values();
        $avgShiftSeries = $months
            ->map(fn ($month) => round((float) ($month['avg_present'] ?? 0), 2))
            ->values();
        $targetWorkdaySeries = $months
            ->map(fn ($month) => round((float) ($month['target_workday_weighted'] ?? 0), 2))
            ->values();
    @endphp

    <section class="space-y-6 overflow-x-hidden">
        <div class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_minmax(0,980px)] xl:items-start">
            <div class="min-w-0 max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Attendance Admin Dashboard</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Dashboard Hari Kerja {{ $companyName }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">
                    Dashboard ini dirancang ulang untuk membaca report attendance tahunan secara cepat, ringkas, dan lebih mudah dipantau untuk kebutuhan admin operasional.
                </p>
            </div>

            <form method="GET" action="{{ route('dashboard.workday') }}" class="min-w-0 w-full">
                <div class="rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6 xl:items-end">
                        <div>
                            <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">Nama PT</label>
                            <select name="company" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                                @foreach ($companyOptions as $companyKey => $companyLabel)
                                    <option value="{{ $companyKey }}" @selected($selectedCompany === $companyKey)>{{ $companyLabel }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($selectedCompany === 'servanda')
                            <div>
                                <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">Divisi</label>
                                @if (filled($forcedDivision))
                                    <input type="hidden" name="division" value="{{ $forcedDivision }}">
                                    <div class="flex h-[42px] items-center rounded-xl border border-amber-200 bg-amber-50 px-3.5 text-sm font-semibold text-amber-800">
                                        {{ $forcedDivision }}
                                    </div>
                                @else
                                    <select name="division" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                                        <option value="">Semua</option>
                                        @foreach ($divisionOptions as $division)
                                            <option value="{{ $division }}" @selected(($selectedDivision ?? null) === $division)>{{ $division }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endif

                        <div>
                            <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">Tahun</label>
                            <select name="year" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                                @foreach ($yearOptions as $year)
                                    <option value="{{ $year }}" @selected($selectedYear === $year)>{{ $year }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">Cabang</label>
                            <select name="branch" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($branchOptions as $branch)
                                    <option value="{{ $branch }}" @selected(($selectedBranch ?? '') === $branch)>{{ $branch }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">Area Manager</label>
                            <select name="area_manager" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($areaManagers as $areaManager)
                                    <option value="{{ $areaManager }}" @selected(($selectedAreaManager ?? '') === $areaManager)>{{ $areaManager }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1 block text-[9px] font-semibold uppercase tracking-[0.16em] text-slate-500">Operation Manager</label>
                            <select name="operation_manager" onchange="this.form.submit()" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($operationManagers as $operationManager)
                                    <option value="{{ $operationManager }}" @selected(($selectedOperationManager ?? '') === $operationManager)>{{ $operationManager }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <section class="grid gap-6">
            <div class="dashboard-card min-w-0 overflow-hidden border border-slate-200 bg-white">
                <div class="flex flex-col gap-2 border-b border-slate-200 pb-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Line Chart</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">Rata-rata Attendance % per Bulan</h3>
                        <p class="mt-1 text-sm text-slate-500">Menampilkan tren rata-rata kehadiran karyawan per bulan. Nilai 0% menunjukkan tidak adanya jadwal shift dan absensi pada periode tersebut.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs lg:justify-end">
                        <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-700">
                            <span class="h-2.5 w-2.5 rounded-full bg-rose-500"></span>
                            Avg Attendance %
                        </span>
                        <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 font-medium text-blue-700">
                            <span class="h-0.5 w-4 rounded-full bg-blue-500"></span>
                            Target Attendance
                        </span>
                    </div>
                </div>

                <div class="mt-4 grid gap-3 md:grid-cols-2 xl:grid-cols-5">
                    <article class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-blue-700">Average Attendance</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($averageAttendance, 2) }}%</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-cyan-700">Average Hari Kerja</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($averageWorkday, 2) }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-indigo-700">Average Jadwal Shift</p>
                        <p class="mt-2 text-2xl font-bold tracking-tight text-slate-900">{{ number_format($averageShift, 2) }}</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-emerald-700">Bulan Terbaik</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">{{ $bestMonthLabel }}</p>
                        <p class="mt-1 text-sm font-medium text-emerald-700">{{ number_format($bestMonthRate, 2) }}%</p>
                    </article>
                    <article class="rounded-xl border border-slate-200 bg-white px-4 py-3 shadow-sm">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.16em] text-rose-700">Bulan Terendah</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">{{ $lowestMonthLabel }}</p>
                        <p class="mt-1 text-sm font-medium text-rose-700">{{ number_format($lowestMonthRate, 2) }}%</p>
                    </article>
                </div>

                <div class="mt-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3 text-sm text-slate-600">
                    Titik hijau menunjukkan attendance di atas atau sama dengan target, merah di bawah target, dan abu-abu menunjukkan bulan tanpa aktivitas.
                </div>

                <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 md:p-4">
                    <div class="h-[240px] md:h-[260px] xl:h-[280px] 2xl:h-[300px]">
                        <canvas id="workdayLineChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="dashboard-card min-w-0 overflow-hidden border border-slate-200 bg-white">
                <div class="flex flex-col gap-2 border-b border-slate-200 pb-3 lg:flex-row lg:items-end lg:justify-between">
                    <div class="min-w-0">
                        <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Line Chart</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">Trend Hari Kerja vs Jadwal Shift per Bulan</h3>
                        <p class="mt-1 text-sm text-slate-500">Membandingkan average hari kerja, average jadwal shift, dan target kehadiran berbobot untuk setiap bulan.</p>
                    </div>
                    <div class="flex flex-wrap gap-2 text-xs lg:justify-end">
                        <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-700">
                            <span class="h-0.5 w-4 rounded-full bg-rose-600"></span>
                            Avg Workday
                        </span>
                        <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full border border-rose-200 bg-rose-50 px-3 py-1.5 font-medium text-rose-500">
                            <span class="h-[2px] w-4 rounded-full bg-rose-300"></span>
                            Avg Scheduled Shift
                        </span>
                        <span class="inline-flex items-center gap-2 whitespace-nowrap rounded-full border border-blue-200 bg-blue-50 px-3 py-1.5 font-medium text-blue-700">
                            <span class="h-0.5 w-4 rounded-full bg-blue-500"></span>
                            Target Workday (Weighted)
                        </span>
                    </div>
                </div>

                <div class="mt-3 overflow-hidden rounded-2xl border border-slate-200 bg-white p-3 md:p-4">
                    <div class="h-[240px] md:h-[260px] xl:h-[280px] 2xl:h-[300px]">
                        <canvas id="workdayVsShiftChart"></canvas>
                    </div>
                </div>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-3">
            <article x-data="{ search: '' }" class="dashboard-card min-w-0 border border-slate-200 bg-white">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Bar Chart</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Attendance by Position</h3>
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
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Bar Chart</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Attendance by Area</h3>
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

            <article x-data="{ search: '' }" class="dashboard-card min-w-0 border border-slate-200 bg-white">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Bar Chart</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Attendance by Jenis Gaji</h3>
                </div>

                <div class="mt-4">
                    <input
                        x-model="search"
                        type="text"
                        placeholder="Cari jenis gaji..."
                        class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-300 focus:bg-white"
                    >
                </div>

                <div class="mt-5 space-y-4 max-h-[560px] overflow-y-auto pr-1">
                    @forelse ($payFrequencyChart as $item)
                        <div x-show="@js(strtolower($item['label'])).includes(search.toLowerCase())" x-transition.opacity>
                            <div class="mb-1.5 flex items-center justify-between gap-3 text-sm">
                                <span class="line-clamp-1 font-medium text-slate-700">{{ $item['label'] }}</span>
                                <span class="font-semibold text-slate-900">{{ number_format($item['attendance_rate'], 2) }}%</span>
                            </div>
                            <div class="h-3 overflow-hidden rounded-full bg-slate-100">
                                <div class="h-full rounded-full bg-gradient-to-r from-amber-500 to-orange-400" style="width: {{ min(($item['attendance_rate'] / $payFreqMax) * 100, 100) }}%"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data jenis gaji.</div>
                    @endforelse
                    <div
                        x-show="!Array.from($el.parentElement.querySelectorAll('[x-show]')).some((element) => element.style.display !== 'none')"
                        class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500"
                    >
                        Data jenis gaji tidak ditemukan.
                    </div>
                </div>
            </article>
        </section>

        <section class="grid gap-6 xl:grid-cols-2">
            <article class="dashboard-card border border-slate-200 bg-white">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Top Data</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Top 5 Attendance Tertinggi</h3>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($topAttendance as $index => $item)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $index + 1 }}. {{ $item['employee_name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item['employee_no'] }} | {{ $item['position'] }}</p>
                            </div>
                            <span class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                                {{ number_format($item['attendance_rate'], 2) }}%
                            </span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data employee.</div>
                    @endforelse
                </div>
            </article>

            <article class="dashboard-card border border-slate-200 bg-white">
                <div class="border-b border-slate-200 pb-4">
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Top Data</p>
                    <h3 class="mt-1 text-lg font-semibold text-slate-900">Top 5 Attendance Terendah</h3>
                </div>

                <div class="mt-5 space-y-3">
                    @forelse ($lowestAttendance as $index => $item)
                        <div class="flex items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-slate-50/70 px-4 py-3">
                            <div class="min-w-0">
                                <p class="text-sm font-semibold text-slate-900">{{ $index + 1 }}. {{ $item['employee_name'] }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ $item['employee_no'] }} | {{ $item['position'] }}</p>
                            </div>
                            <span class="rounded-full border border-rose-200 bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                                {{ number_format($item['attendance_rate'], 2) }}%
                            </span>
                        </div>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-8 text-center text-sm text-slate-500">Belum ada data employee.</div>
                    @endforelse
                </div>
            </article>
        </section>

        <section class="dashboard-card border border-slate-200 bg-white">
            <details class="group">
                <summary class="flex cursor-pointer list-none items-center justify-between gap-4">
                    <div>
                        <p class="text-[11px] font-semibold uppercase tracking-[0.22em] text-slate-500">Pusat Information</p>
                        <h3 class="mt-1 text-xl font-semibold text-slate-900">Penjelasan Dashboard Hari Kerja</h3>
                        <p class="mt-1 text-sm text-slate-500">Klik untuk melihat arti setiap data, sumber perolehan, dan contoh pembacaannya.</p>
                    </div>
                    <span class="inline-flex items-center gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-4 py-2 text-sm font-medium text-slate-600">
                        <span>Selengkapnya</span>
                        <span class="inline-flex h-8 w-8 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-500 transition group-open:rotate-180">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6" />
                            </svg>
                        </span>
                    </span>
                </summary>

                <div class="mt-6 border-t border-slate-200 pt-6">
                    <div class="grid gap-5 xl:grid-cols-3">
                        <article class="rounded-2xl border border-slate-200 bg-white p-6">
                            <p class="text-lg font-bold text-slate-900">Line Chart Rata-rata Attendance % per Bulan</p>
                            <p class="mt-3 text-base leading-8 text-slate-700">
                                Bagian ini dipakai untuk membaca <strong>tren attendance tahunan</strong> berdasarkan <strong>data employee</strong> pada bulan yang sedang dihitung.
                            </p>
                            <p class="mt-5 text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Penjelasan</p>
                            <div class="mt-3 space-y-3 text-base leading-8 text-slate-700">
                                <p><strong>1.</strong> Setiap titik mewakili <em>satu bulan</em> dari Januari sampai Desember.</p>
                                <p><strong>2.</strong> Nilai pada setiap titik adalah <strong>attendance rate bulan tersebut</strong>, dihitung dari <em>total jadwal shift seluruh employee</em> dibagi <em>total workday seluruh employee</em>, lalu dikali 100%.</p>
                                <p><strong>3.</strong> Garis biru menunjukkan <strong>arah naik atau turun performa attendance</strong> antar bulan.</p>
                                <p><strong>4.</strong> Garis target attendance dibaca dari <strong>setting target bulanan</strong> pada menu <strong>Target Attendance</strong>, sehingga setiap bulan bisa memiliki target yang sama atau berbeda sesuai pengaturan administrator.</p>
                                <p><strong>5.</strong> Titik <strong class="text-emerald-700">hijau</strong> berarti nilainya <em>sama dengan atau di atas target</em>, titik <strong class="text-rose-700">merah</strong> berarti <em>masih di bawah target</em>, dan titik <strong class="text-slate-600">abu-biru</strong> berarti <em>tidak ada aktivitas attendance</em> yang tercatat pada bulan tersebut.</p>
                                <p><strong>6.</strong> Nilai <strong>0%</strong> tetap ditampilkan sebagai penanda bahwa pada periode tersebut <em>tidak ada jadwal shift</em> dan <em>tidak ada absensi</em> yang masuk ke perhitungan.</p>
                                <p><strong>7.</strong> Kartu <strong>Rata-rata Attendance</strong>, <strong>Bulan Terbaik</strong>, dan <strong>Bulan Terendah</strong> di atas chart diringkas dari <em>data bulanan yang sama</em> agar pembacaan dashboard lebih cepat.</p>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white p-6">
                            <p class="text-lg font-bold text-slate-900">Trend Hari Kerja vs Jadwal Shift per Bulan</p>
                            <p class="mt-3 text-base leading-8 text-slate-700">
                                Card ini dipakai untuk membandingkan <strong>rata-rata hari kerja</strong>, <strong>rata-rata jadwal shift</strong>, dan <strong>target workday berbobot</strong> pada setiap bulan berdasarkan filter yang aktif.
                            </p>
                            <div class="mt-5 space-y-3 text-base leading-8 text-slate-700">
                                <p><strong>1.</strong> Garis <strong>Avg Workday</strong> menunjukkan rata-rata total hari kerja employee pada bulan tersebut.</p>
                                <p><strong>2.</strong> Garis <strong>Avg Scheduled Shift</strong> menunjukkan rata-rata jumlah jadwal shift atau kehadiran yang tercatat pada bulan tersebut.</p>
                                <p><strong>3.</strong> Garis <strong>Target Workday (Weighted)</strong> bukan angka target mentah, tetapi rata-rata target employee berdasarkan komposisi jenis gaji.</p>
                                <p><strong>4.</strong> Employee <strong>Bulanan</strong> memakai <strong>Target Bulanan</strong>, sedangkan employee <strong>Harian</strong> memakai <strong>Target Harian</strong>.</p>
                                <p><strong>5.</strong> Rumus yang dipakai adalah <strong>((Jumlah Employee Bulanan x Target Bulanan) + (Jumlah Employee Harian x Target Harian)) / Total Employee</strong>.</p>
                                <p><strong>6.</strong> Contoh: jika target Cleaning di-setting <strong>Bulanan = 21</strong> dan <strong>Harian = 25</strong>, lalu ada <strong>14 employee Bulanan</strong> dan <strong>3 employee Harian</strong>, maka hasil weighted target adalah <strong>((14 x 21) + (3 x 25)) / 17 = 21.71</strong>.</p>
                                <p><strong>7.</strong> Karena itu, nilai seperti <strong>21.64</strong> berarti data bulan tersebut terdiri dari campuran employee <strong>Bulanan</strong> dan <strong>Harian</strong>.</p>
                            </div>
                        </article>

                        <article class="rounded-2xl border border-slate-200 bg-white p-6">
                            <p class="text-lg font-bold text-slate-900">Bar Chart dan Top Data</p>
                            <p class="mt-3 text-base leading-8 text-slate-700">
                                Bagian ini dipakai untuk melihat kelompok atau employee mana yang paling menonjol, baik yang performanya tinggi maupun yang perlu perhatian lebih lanjut.
                            </p>
                            <div class="mt-5 space-y-5 text-base leading-8 text-slate-700">
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Bar Chart</p>
                                    <p class="mt-2"><strong>Attendance by Position, Area, dan Jenis Gaji</strong> dipakai untuk membandingkan kelompok mana yang memiliki <em>attendance lebih tinggi</em> atau <em>lebih rendah</em>.</p>
                                    <p class="mt-2">Data dikelompokkan berdasarkan <strong>position</strong>, <strong>area</strong>, dan <strong>pay frequency</strong>, lalu dihitung total <em>jadwal shift</em>, total <em>tidak absen</em>, dan attendance rate per grup.</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Top Attendance</p>
                                    <p class="mt-2"><strong>Top 5 Attendance Tertinggi</strong> menampilkan employee dengan attendance rate paling tinggi pada data tahunan yang sedang dibaca dashboard.</p>
                                    <p class="mt-2"><strong>Top 5 Attendance Terendah</strong> menampilkan employee dengan attendance rate paling rendah agar area yang perlu perhatian bisa terlihat lebih cepat.</p>
                                </div>
                                <div>
                                    <p class="text-sm font-semibold uppercase tracking-[0.16em] text-slate-500">Sumber Data</p>
                                    <p class="mt-2">Seluruh isi dashboard ini dibentuk dari <strong>data attendance summary per employee</strong> yang digabungkan dengan <strong>master employee</strong> untuk mengambil informasi seperti <em>position</em>, <em>area</em>, <em>status</em>, <em>pay frequency</em>, dan khusus Servanda juga <em>divisi</em>.</p>
                                    <p class="mt-2">Ketika filter <strong>Nama PT</strong>, <strong>Tahun</strong>, <strong>Divisi</strong>, <strong>Area Manager</strong>, atau <strong>Operation Manager</strong> diubah, maka chart dan daftar pada dashboard ini akan <em>ikut dihitung ulang</em> sesuai filter tersebut.</p>
                                </div>
                            </div>
                        </article>
                    </div>
                </div>
            </details>
        </section>

    </section>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        (() => {
            if (typeof Chart === 'undefined') {
                return;
            }

            const canvas = document.getElementById('workdayLineChart');
            const workdayVsShiftCanvas = document.getElementById('workdayVsShiftChart');
            const labels = @json($chartLabels);
            const attendanceAverageSeries = @json($attendanceAverageSeries);
            const attendanceTargetSeries = @json($attendanceTargetSeries);
            const avgWorkdaySeries = @json($avgWorkdaySeries);
            const avgShiftSeries = @json($avgShiftSeries);
            const targetWorkdaySeries = @json($targetWorkdaySeries);
            const averageAttendanceTarget = {{ $averageAttendanceTarget }};
            const noActivityColor = '#94a3b8';
            const aboveTargetColor = '#16a34a';
            const belowTargetColor = '#dc2626';
            const neutralLineColor = '#f43f5e';
            const noDataWorkdayColor = '#94a3b8';
            const workdayPrimaryLineColor = '#e11d48';
            const workdaySecondaryLineColor = '#fda4af';
            const targetLineColor = '#3b82f6';

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
                                label: 'Avg Attendance %',
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
                                label: 'Target',
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
                                borderWidth: 1.5,
                                borderDash: [6, 6],
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
                                        const value = Number(context.parsed.y).toFixed(2).replace('.', ',');

                                        return `${context.dataset.label}: ${value}%`;
                                    },
                                    afterLabel: function(context) {
                                        if (context.dataset.label !== 'Avg Attendance %') {
                                            return 'Benchmark target attendance bulanan';
                                        }

                                        const value = Number(context.parsed.y);
                                        const targetValue = Number(attendanceTargetSeries[context.dataIndex] ?? averageAttendanceTarget);

                                        if (value === 0) {
                                            return 'Status: No activity';
                                        }

                                        return value >= targetValue
                                            ? 'Status: Above target'
                                            : 'Status: Below target';
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
                                max: 100,
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

            if (workdayVsShiftCanvas) {
                const workdayChartContext = workdayVsShiftCanvas.getContext('2d');
                const workdayAreaGradient = workdayChartContext.createLinearGradient(0, 0, 0, 320);
                workdayAreaGradient.addColorStop(0, 'rgba(59, 130, 246, 0.18)');
                workdayAreaGradient.addColorStop(1, 'rgba(59, 130, 246, 0.02)');

                new Chart(workdayVsShiftCanvas, {
                    type: 'line',
                    data: {
                        labels,
                        datasets: [
                            {
                                label: 'Avg Workday',
                                data: avgWorkdaySeries,
                                borderColor: workdayPrimaryLineColor,
                                backgroundColor: workdayAreaGradient,
                                fill: true,
                                tension: 0.15,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor(context) {
                                    const value = Number(context.raw ?? 0);
                                    const targetValue = Number(targetWorkdaySeries[context.dataIndex] ?? 0);

                                    if (value === 0) {
                                        return noDataWorkdayColor;
                                    }

                                    return value >= targetValue
                                        ? aboveTargetColor
                                        : belowTargetColor;
                                },
                                pointBorderColor(context) {
                                    const value = Number(context.raw ?? 0);
                                    const targetValue = Number(targetWorkdaySeries[context.dataIndex] ?? 0);

                                    if (value === 0) {
                                        return noDataWorkdayColor;
                                    }

                                    return value >= targetValue
                                        ? aboveTargetColor
                                        : belowTargetColor;
                                },
                                pointBorderWidth: 2,
                                borderWidth: 3,
                                segment: {
                                    borderColor(context) {
                                        const startValue = Number(context.p0.parsed.y ?? 0);
                                        const endValue = Number(context.p1.parsed.y ?? 0);
                                        const endTarget = Number(targetWorkdaySeries[context.p1DataIndex] ?? 0);

                                        if (startValue === 0 || endValue === 0) {
                                            return noDataWorkdayColor;
                                        }

                                        return endValue >= endTarget
                                            ? aboveTargetColor
                                            : belowTargetColor;
                                    }
                                }
                            },
                            {
                                label: 'Avg Scheduled Shift',
                                data: avgShiftSeries,
                                borderColor: workdaySecondaryLineColor,
                                backgroundColor: workdaySecondaryLineColor,
                                fill: false,
                                tension: 0.15,
                                pointRadius: 4,
                                pointHoverRadius: 6,
                                pointBackgroundColor(context) {
                                    const value = Number(context.raw ?? 0);
                                    const targetValue = Number(targetWorkdaySeries[context.dataIndex] ?? 0);

                                    if (value === 0) {
                                        return noDataWorkdayColor;
                                    }

                                    return value >= targetValue
                                        ? aboveTargetColor
                                        : belowTargetColor;
                                },
                                pointBorderColor(context) {
                                    const value = Number(context.raw ?? 0);
                                    const targetValue = Number(targetWorkdaySeries[context.dataIndex] ?? 0);

                                    if (value === 0) {
                                        return noDataWorkdayColor;
                                    }

                                    return value >= targetValue
                                        ? aboveTargetColor
                                        : belowTargetColor;
                                },
                                pointBorderWidth: 2,
                                borderWidth: 1.75,
                                segment: {
                                    borderColor(context) {
                                        const startValue = Number(context.p0.parsed.y ?? 0);
                                        const endValue = Number(context.p1.parsed.y ?? 0);
                                        const endTarget = Number(targetWorkdaySeries[context.p1DataIndex] ?? 0);

                                        if (startValue === 0 || endValue === 0) {
                                            return noDataWorkdayColor;
                                        }

                                        return endValue >= endTarget
                                            ? aboveTargetColor
                                            : belowTargetColor;
                                    }
                                }
                            },
                            {
                                label: 'Target Workday (Weighted)',
                                data: targetWorkdaySeries,
                                borderColor: targetLineColor,
                                backgroundColor: targetLineColor,
                                fill: false,
                                tension: 0,
                                pointRadius: 4,
                                pointHoverRadius: 5,
                                pointBackgroundColor: targetLineColor,
                                pointBorderColor: '#ffffff',
                                pointBorderWidth: 2,
                                borderWidth: 1.5,
                                borderDash: [6, 6],
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
                                        const value = Number(context.parsed.y ?? 0).toFixed(2).replace('.', ',');

                                        if (context.dataset.label === 'Avg Workday') {
                                            return `Avg Hari Kerja: ${value}`;
                                        }

                                        if (context.dataset.label === 'Avg Scheduled Shift') {
                                            return `Avg Jadwal Shift: ${value}`;
                                        }

                                        return `Target: ${value}`;
                                    }
                                }
                            }
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
                                min: 0,
                                max: 30,
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
                    }
                });
            }
        })();
    </script>
@endsection
