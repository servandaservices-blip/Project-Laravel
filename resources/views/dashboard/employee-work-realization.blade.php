@extends('layouts.dashboard')

@section('title', 'Realisasi Hari Kerja')
@section('page_title', 'Realisasi Hari Kerja - ' . ($companyName ?? 'Servanda'))
@section('page_subtitle', 'Monitoring realisasi kehadiran dan hari kerja PT ' . ($companyName ?? 'Servanda'))

@section('content')
    @php
        $forcedDivision = auth()->user()?->forcedDivision();
        $emptyFilterValue = '__EMPTY__';
    @endphp
    <section class="dashboard-card">
        <div>
            <p class="dashboard-eyebrow">Employee Work Realization</p>
            <h2 class="text-2xl font-bold text-slate-900">Realisasi Hari Kerja - {{ $companyName ?? 'Servanda' }}</h2>
        </div>

        <form method="GET" action="{{ route('employee.work-realization') }}" class="mt-6">
            <div class="rounded-3xl border border-slate-200 bg-white p-5">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Nama PT</label>
                        <select name="company" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            @foreach (($companyOptions ?? []) as $companyKey => $company)
                                <option value="{{ $companyKey }}" @selected(($selectedCompany ?? 'servanda') === $companyKey)>{{ $company['label'] }}</option>
                            @endforeach
                        </select>
                    </div>

                    @if (($selectedCompany ?? 'servanda') === 'servanda')
                        <div>
                            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Divisi</label>
                            @if (filled($forcedDivision))
                                <input type="hidden" name="division" value="{{ $forcedDivision }}">
                                <div class="flex h-[50px] items-center rounded-2xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-800">
                                    {{ $forcedDivision }}
                                </div>
                            @else
                                <select name="division" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                    <option value="">Semua Divisi</option>
                                    @foreach (($divisionOptions ?? collect()) as $division)
                                        <option value="{{ $division }}" @selected(($selectedDivision ?? '') === $division)>
                                            {{ $division === $emptyFilterValue ? 'Tanpa Data' : $division }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    @endif

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Position</label>
                        <select name="position" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            <option value="">Semua Position</option>
                            @foreach (($positions ?? collect()) as $position)
                                <option value="{{ $position }}" @selected(($selectedPosition ?? '') === $position)>
                                    {{ $position === $emptyFilterValue ? 'Tanpa Data' : $position }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Area Penempatan</label>
                        <select name="area" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            <option value="">Semua Area</option>
                            @foreach (($areas ?? collect()) as $area)
                                <option value="{{ $area }}" @selected(($selectedArea ?? '') === $area)>
                                    {{ $area === $emptyFilterValue ? 'Tanpa Data' : $area }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang</label>
                        <select name="branch" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            <option value="">Semua</option>
                            @foreach (($branches ?? collect()) as $branch)
                                <option value="{{ $branch }}" @selected(($selectedBranch ?? '') === $branch)>{{ $branch }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Area Manager</label>
                        <select name="area_manager" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            <option value="">Semua Area Manager</option>
                            @foreach (($areaManagers ?? collect()) as $areaManager)
                                <option value="{{ $areaManager }}" @selected(($selectedAreaManager ?? '') === $areaManager)>
                                    {{ $areaManager === $emptyFilterValue ? 'Tanpa Data' : $areaManager }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Operation Manager</label>
                        <select name="operation_manager" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            <option value="">Semua Operation Manager</option>
                            @foreach (($operationManagers ?? collect()) as $operationManager)
                                <option value="{{ $operationManager }}" @selected(($selectedOperationManager ?? '') === $operationManager)>
                                    {{ $operationManager === $emptyFilterValue ? 'Tanpa Data' : $operationManager }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Gaji</label>
                        <select name="pay_freq" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            <option value="">Semua</option>
                            @foreach (($payFrequencies ?? collect()) as $payFrequency)
                                <option value="{{ $payFrequency }}" @selected(($selectedPayFrequency ?? '') === $payFrequency)>{{ $payFrequency }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
        </form>
    </section>
@endsection
