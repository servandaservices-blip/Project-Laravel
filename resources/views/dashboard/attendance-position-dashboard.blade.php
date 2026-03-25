@extends('layouts.dashboard')

@section('title', 'Attendance Position')
@section('page_title', 'Attendance Position')
@section('page_subtitle', 'Ringkasan attendance per position')

@section('content')
    @php
        $companyOptions = [
            'servanda' => 'Servanda',
            'gabe' => 'Gabe',
            'salus' => 'Salus',
        ];
        $divisionOptions = collect($divisionOptions ?? [])->values();
        $forcedDivision = auth()->user()?->forcedDivision();
        $areaManagers = $areaManagers ?? collect();
        $operationManagers = $operationManagers ?? collect();
        $yearOptions = range((int) now()->year - 2, 2030);
        $dashboardFilterLabelClass = 'mb-2 block text-sm font-medium text-slate-700';
        $dashboardFilterFieldClass = 'w-full rounded-2xl border border-slate-200 bg-gradient-to-br from-slate-50 to-sky-50 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm outline-none transition focus:border-sky-400 focus:ring-4 focus:ring-sky-100';
    @endphp

    <section x-data="{ positionModalOpen: false, selectedPosition: null, positionSearch: '' }" @keydown.escape.window="positionModalOpen = false" class="space-y-6">
        <section class="dashboard-card overflow-visible">
            <div class="max-w-3xl">
                <p class="dashboard-eyebrow">Attendance Position Dashboard</p>
                <h2 class="text-2xl font-bold tracking-tight text-slate-900">Attendance Position {{ $companyName }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">
                    Halaman ini difokuskan untuk membaca ringkasan attendance per position dan menelusuri employee yang berada pada position terkait melalui popup detail.
                </p>
            </div>

            <form method="GET" action="{{ route('dashboard.attendance-position') }}" class="relative mt-6">
                <div class="employee-filter-card rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/60 p-5 shadow-sm">
                    <div
                        @class([
                            'grid gap-4 md:grid-cols-2 xl:grid-cols-4',
                            '2xl:grid-cols-5' => $selectedCompany === 'servanda',
                        ])
                    >
                        <x-dashboard.filter-select
                            label="Nama PT"
                            name="company"
                            :options="$companyOptions"
                            :selected="$selectedCompany"
                            placeholder="Semua"
                            :field-class="$dashboardFilterFieldClass"
                            :label-class="$dashboardFilterLabelClass"
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
                                :field-class="$dashboardFilterFieldClass"
                                :label-class="$dashboardFilterLabelClass"
                            />
                        @endif

                        <x-dashboard.filter-select
                            label="Tahun"
                            name="year"
                            :options="$yearOptions"
                            :selected="$selectedYear"
                            placeholder="Semua"
                            :field-class="$dashboardFilterFieldClass"
                            :label-class="$dashboardFilterLabelClass"
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
                            :field-class="$dashboardFilterFieldClass"
                            :label-class="$dashboardFilterLabelClass"
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
                            :field-class="$dashboardFilterFieldClass"
                            :label-class="$dashboardFilterLabelClass"
                        />
                    </div>
                </div>
            </form>
        </section>

        <section class="dashboard-card border border-slate-200 bg-white">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Position Attendance</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-900">Tabel Attendance Position</h3>
                    <p class="mt-1 text-sm text-slate-500">Ringkasan attendance per position beserta akses cepat untuk melihat daftar employee pada position terkait.</p>
                </div>

                <form method="GET" action="{{ route('dashboard.attendance-position') }}" class="flex flex-wrap items-center gap-2">
                    <input type="hidden" name="company" value="{{ $selectedCompany }}">
                    <input type="hidden" name="year" value="{{ $selectedYear }}">
                    <input type="hidden" name="area_manager" value="{{ $selectedAreaManager ?? '' }}">
                    <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager ?? '' }}">
                    @if ($selectedCompany === 'servanda' && ! empty($selectedDivision))
                        <input type="hidden" name="division" value="{{ $selectedDivision }}">
                    @endif
                    <label class="text-sm text-slate-500">Bulan</label>
                    <select name="position_month" onchange="this.form.submit()" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm">
                        <option value="">Semua Bulan</option>
                        @foreach (range(1, 12) as $monthNumber)
                            <option value="{{ $monthNumber }}" @selected(($selectedPositionMonth ?? null) === $monthNumber)>
                                {{ \Illuminate\Support\Carbon::create()->month($monthNumber)->translatedFormat('F') }}
                            </option>
                        @endforeach
                    </select>
                    <label class="text-sm text-slate-500">Show</label>
                    <select name="position_per_page" onchange="this.form.submit()" class="rounded-2xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-700 shadow-sm">
                        @foreach ([10, 50, 100] as $size)
                            <option value="{{ $size }}" @selected(($selectedPositionPerPage ?? 10) === $size)>{{ $size }}</option>
                        @endforeach
                    </select>
                </form>
            </div>

            <div class="mt-4">
                <label class="mb-2 block text-sm font-medium text-slate-700">Search</label>
                <input
                    x-model="positionSearch"
                    type="text"
                    placeholder="Cari nama position..."
                    class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-300 focus:bg-white"
                >
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Nama Position</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Average Workday</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">AVG Jadwal Shift</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">AVG Tidak Masuk</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Average Attendance %</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positionAttendanceTable as $positionItem)
                            @php
                                $positionPayload = [
                                    'label' => $positionItem['label'],
                                    'employee_count' => $positionItem['employee_count'],
                                    'avg_workday' => number_format($positionItem['avg_workday'], 2),
                                    'avg_present' => number_format($positionItem['avg_present'], 2),
                                    'avg_absent' => number_format($positionItem['avg_absent'] ?? 0, 2),
                                    'attendance_rate' => number_format($positionItem['attendance_rate'], 2),
                                    'employees' => $positionItem['employees']->map(function ($employee) {
                                        return [
                                            'employee_no' => $employee['employee_no'],
                                            'employee_name' => $employee['employee_name'],
                                            'position' => $employee['position'],
                                            'area' => $employee['area'],
                                            'pay_freq' => $employee['pay_freq'],
                                            'workday_total' => number_format($employee['workday_total']),
                                            'present_total' => number_format($employee['present_total']),
                                            'absent_total' => number_format($employee['absent_total'] ?? 0),
                                            'attendance_rate' => number_format($employee['attendance_rate'], 2),
                                        ];
                                    })->values(),
                                ];
                            @endphp
                            <tr x-show="@js(strtolower($positionItem['label'])).includes(positionSearch.toLowerCase())" x-transition.opacity class="rounded-2xl bg-slate-50/80 shadow-sm">
                                <td class="rounded-l-2xl px-4 py-4 align-top">
                                    <p class="font-semibold text-slate-900">{{ $positionItem['label'] }}</p>
                                    <p class="mt-1 text-xs text-slate-500">{{ $positionItem['employee_count'] }} employee</p>
                                </td>
                                <td class="px-4 py-4 text-center text-sm font-medium text-slate-800">{{ number_format($positionItem['avg_workday'], 2) }}</td>
                                <td class="px-4 py-4 text-center text-sm font-medium text-sky-700">{{ number_format($positionItem['avg_present'], 2) }}</td>
                                <td class="px-4 py-4 text-center text-sm font-medium text-slate-800">{{ number_format($positionItem['avg_absent'] ?? 0, 2) }}</td>
                                <td class="px-4 py-4 text-center text-sm font-semibold {{ $positionItem['attendance_rate'] >= 90 ? 'text-emerald-700' : ($positionItem['attendance_rate'] >= 80 ? 'text-amber-700' : 'text-rose-700') }}">
                                    {{ number_format($positionItem['attendance_rate'], 2) }}%
                                </td>
                                <td class="rounded-r-2xl px-4 py-4 text-center">
                                    <button
                                        type="button"
                                        x-on:click.prevent="selectedPosition = @js($positionPayload); positionModalOpen = true"
                                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 text-sky-700 transition hover:border-sky-300 hover:bg-sky-100"
                                        title="Lihat Employee"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7S1 12 1 12z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Belum ada data attendance position.
                                </td>
                            </tr>
                        @endforelse
                        @if ($positionAttendanceTable->count() > 0)
                            <tr
                                x-show="!Array.from($el.parentElement.querySelectorAll('tr[x-show]')).some((element) => element.style.display !== 'none')"
                            >
                                <td colspan="6" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Data position tidak ditemukan.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex flex-col gap-3 border-t border-slate-200 pt-4 md:flex-row md:items-center md:justify-between">
                <p class="text-sm text-slate-500">
                    Menampilkan {{ $positionAttendanceTable->firstItem() ?? 0 }} - {{ $positionAttendanceTable->lastItem() ?? 0 }} dari {{ $positionAttendanceTable->total() }} position.
                    @if (! empty($selectedPositionMonth))
                        Data bulan {{ \Illuminate\Support\Carbon::create()->month($selectedPositionMonth)->translatedFormat('F') }}.
                    @else
                        Data rata-rata tahun {{ $selectedYear }}.
                    @endif
                </p>
                <div>
                    {{ $positionAttendanceTable->onEachSide(1)->links() }}
                </div>
            </div>
        </section>

        <div
            x-cloak
            x-show="positionModalOpen"
            x-transition.opacity
            class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-24"
        >
            <div class="absolute inset-0" @click="positionModalOpen = false"></div>

            <div
                x-show="positionModalOpen"
                x-transition
                class="relative z-10 w-full max-w-5xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl"
            >
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.20),_transparent_32%),linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#ecfeff_100%)] px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Detail Position</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900" x-text="selectedPosition?.label || '-'"></h3>
                        <p class="mt-2 text-sm text-slate-600">
                            Detail employee yang masuk dalam position ini berdasarkan data attendance tahunan.
                        </p>
                    </div>

                    <button
                        type="button"
                        @click="positionModalOpen = false"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                        title="Tutup"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">
                    <div class="grid gap-3 md:grid-cols-5">
                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Employee</p>
                            <p class="mt-1 font-bold text-slate-900" x-text="selectedPosition?.employee_count || '0'"></p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Avg Workday</p>
                            <p class="mt-1 font-bold text-slate-900" x-text="selectedPosition?.avg_workday || '0.00'"></p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">AVG Jadwal Shift</p>
                            <p class="mt-1 font-bold text-slate-900" x-text="selectedPosition?.avg_present || '0.00'"></p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Tidak Absen</p>
                            <p class="mt-1 font-bold text-slate-900" x-text="selectedPosition?.avg_absent || '0.00'"></p>
                        </div>
                        <div class="rounded-2xl bg-white px-4 py-3 shadow-sm">
                            <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-500">Attendance</p>
                            <p class="mt-1 font-bold text-slate-900" x-text="(selectedPosition?.attendance_rate || '0.00') + '%'"></p>
                        </div>
                    </div>
                </div>

                <div class="max-h-[65vh] overflow-y-auto px-6 py-6">
                    <div class="overflow-x-auto">
                        <table class="min-w-full border-separate border-spacing-y-2">
                            <thead>
                                <tr>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Employee</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Position</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Area</th>
                                    <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Gaji</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Workday</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Jadwal Shift</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Tidak Absen</th>
                                    <th class="px-4 py-3 text-right text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Attendance %</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-if="!selectedPosition || !selectedPosition.employees || selectedPosition.employees.length === 0">
                                    <tr>
                                        <td colspan="8" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                            Belum ada employee pada position ini.
                                        </td>
                                    </tr>
                                </template>
                                <template x-for="employee in (selectedPosition?.employees || [])" :key="employee.employee_no + employee.employee_name">
                                    <tr class="rounded-2xl bg-slate-50/80 shadow-sm">
                                        <td class="rounded-l-2xl px-4 py-4 align-top">
                                            <p class="font-semibold text-slate-900" x-text="employee.employee_name"></p>
                                            <p class="mt-1 text-xs text-slate-500" x-text="employee.employee_no"></p>
                                        </td>
                                        <td class="px-4 py-4 text-sm text-slate-700" x-text="employee.position"></td>
                                        <td class="px-4 py-4 text-sm text-slate-700" x-text="employee.area"></td>
                                        <td class="px-4 py-4 text-sm text-slate-700" x-text="employee.pay_freq"></td>
                                        <td class="px-4 py-4 text-right text-sm font-medium text-slate-800" x-text="employee.workday_total"></td>
                                        <td class="px-4 py-4 text-right text-sm font-medium text-sky-700" x-text="employee.present_total"></td>
                                        <td class="px-4 py-4 text-right text-sm font-medium text-slate-800" x-text="employee.absent_total"></td>
                                        <td class="rounded-r-2xl px-4 py-4 text-right text-sm font-semibold text-slate-900" x-text="employee.attendance_rate + '%'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
