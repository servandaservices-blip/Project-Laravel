@extends('layouts.dashboard')

@section('title', 'Target Attendance')
@section('page_title', 'Target Attendance')
@section('page_subtitle', 'Setting target attendance bulanan per PT, divisi, dan cabang')

@section('content')
    <section class="space-y-6">
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if ($errors->any())
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ $errors->first() }}
            </div>
        @endif

        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Settings Target</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Target Attendance Bulanan</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">
                    Halaman ini dipakai untuk mengatur target attendance bulanan per PT, divisi, serta cabang. Untuk Servanda, pilihan <strong>Semua Divisi</strong> hanya menjadi ringkasan otomatis agar dashboard membaca target dari sumber yang jelas.
                </p>
            </div>
        </div>

        <form method="GET" action="{{ route('settings.attendance-targets.index') }}" class="dashboard-card border border-slate-200 bg-white">
            <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Nama PT</label>
                    <select name="company" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                        @foreach ($companyOptions as $companyKey => $companyLabel)
                            <option value="{{ $companyKey }}" @selected($selectedCompany === $companyKey)>{{ $companyLabel }}</option>
                        @endforeach
                    </select>
                </div>

                @if ($selectedCompany === 'servanda')
                    <div>
                        <label class="mb-2 block text-sm font-medium text-slate-700">Divisi</label>
                        <select name="division" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                            <option value="">Semua</option>
                            @foreach ($divisionOptions as $division)
                                <option value="{{ $division }}" @selected(($selectedDivision ?? '') === $division)>{{ $division }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Cabang</label>
                    <select name="branch" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                        <option value="">Semua</option>
                        @foreach ($branchOptions as $branch)
                            <option value="{{ $branch }}" @selected(($selectedBranch ?? '') === $branch)>{{ $branch }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-slate-700">Tahun</label>
                    <select name="year" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                        @foreach ($yearOptions as $year)
                            <option value="{{ $year }}" @selected((int) $selectedYear === (int) $year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-5 flex flex-wrap items-center justify-end gap-3 border-t border-slate-200 pt-4">
                <a
                    href="{{ route('settings.attendance-targets.index', ['company' => $selectedCompany]) }}"
                    class="inline-flex h-[44px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-4 text-sm font-semibold text-slate-600 shadow-sm transition hover:border-slate-300 hover:text-slate-900"
                >
                    Reset Filter
                </a>
                <button type="submit" class="inline-flex h-[44px] items-center justify-center rounded-2xl border border-slate-200 bg-slate-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                    Tampilkan Target
                </button>
            </div>
        </form>

        <form method="POST" action="{{ route('settings.attendance-targets.store') }}" class="dashboard-card border border-slate-200 bg-white">
            @csrf
            <input type="hidden" name="company" value="{{ $selectedCompany }}">
            <input type="hidden" name="division" value="{{ $selectedDivision }}">
            <input type="hidden" name="branch" value="{{ $selectedBranch }}">
            <input type="hidden" name="year" value="{{ $selectedYear }}">

            <div class="flex items-center justify-between gap-4 border-b border-slate-200 pb-4">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Target Table</p>
                    <h3 class="mt-1 text-xl font-semibold text-slate-900">{{ $canEditTargets ? 'Input Target Jan - Des' : 'Ringkasan Target Jan - Des' }}</h3>
                    <p class="mt-2 text-sm text-slate-500">Scope aktif: <span class="font-semibold text-slate-700">{{ $scopeLabel }}</span></p>
                </div>

                @if ($canEditTargets)
                    <button type="submit" class="inline-flex h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-slate-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-800">
                        Simpan Target
                    </button>
                @else
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                        Pilih divisi untuk mulai edit target.
                    </div>
                @endif
            </div>

            @if ($isServandaSummaryMode)
                <div class="mt-5 rounded-3xl border border-sky-200 bg-sky-50/80 px-5 py-4 text-sm leading-7 text-sky-900">
                    <p class="font-semibold">Mode Semua Divisi hanya menampilkan ringkasan otomatis.</p>
                    <p class="mt-1">
                        Nilai target attendance di bawah ini dihitung dari rata-rata target <strong>Cleaning</strong> dan <strong>Security</strong> yang sudah disimpan pada filter yang sama.
                    </p>
                </div>
            @endif

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Bulan</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Target Attendance (%)</th>
                            @if ($isServandaSummaryMode)
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Sumber Ringkasan</th>
                            @endif
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($targets as $target)
                            <tr class="rounded-2xl bg-slate-50/80 shadow-sm">
                                <td class="rounded-l-2xl px-4 py-4 text-sm font-semibold text-slate-900">{{ $target['label'] }}</td>
                                <td class="px-4 py-4 {{ $isServandaSummaryMode ? '' : 'rounded-r-2xl' }}">
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max="100"
                                        name="attendance_target[{{ $target['month'] }}]"
                                        value="{{ old('attendance_target.' . $target['month'], $target['attendance_target']) }}"
                                        @disabled(! $canEditTargets)
                                        @readonly(! $canEditTargets)
                                        class="w-full rounded-2xl border border-slate-200 {{ $canEditTargets ? 'bg-white' : 'bg-slate-100 text-slate-500' }} px-4 py-3 text-sm text-slate-700 shadow-sm"
                                    >
                                </td>
                                @if ($isServandaSummaryMode)
                                    <td class="rounded-r-2xl px-4 py-4">
                                        <div class="space-y-2 text-sm text-slate-600">
                                            @foreach ($target['division_breakdown'] as $divisionTarget)
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <span class="inline-flex rounded-full bg-white px-3 py-1 text-xs font-semibold text-slate-700 shadow-sm">{{ $divisionTarget['division'] }}</span>
                                                    <span>{{ $divisionTarget['attendance_target'] !== null ? number_format((float) $divisionTarget['attendance_target'], 2) . '%' : '-' }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </form>
    </section>
@endsection
