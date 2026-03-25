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
            <div class="employee-filter-card rounded-3xl border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/60 p-5 shadow-sm">
                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <x-dashboard.filter-select
                        label="Nama PT"
                        name="company"
                        :options="collect($companyOptions ?? [])->mapWithKeys(fn ($company, $key) => [$key => $company['label']])"
                        :selected="$selectedCompany ?? 'servanda'"
                        placeholder="Semua"
                    />

                    @if (($selectedCompany ?? 'servanda') === 'servanda')
                        <x-dashboard.filter-select
                            label="Divisi"
                            name="division"
                            :options="collect($divisionOptions ?? collect())->map(fn ($division) => $division === $emptyFilterValue ? 'Tanpa Data' : $division)"
                            :selected="$selectedDivision ?? ''"
                            placeholder="Semua"
                            :locked="filled($forcedDivision)"
                            :locked-value="$forcedDivision"
                            :locked-label="$forcedDivision"
                        />
                    @endif

                    <x-dashboard.filter-select
                        label="Position"
                        name="position"
                        :options="collect($positions ?? collect())->map(fn ($position) => $position === $emptyFilterValue ? 'Tanpa Data' : $position)"
                        :selected="$selectedPosition ?? ''"
                        placeholder="Semua"
                    />

                    <x-dashboard.filter-select
                        label="Area Penempatan"
                        name="area"
                        :options="collect($areas ?? collect())->map(fn ($area) => $area === $emptyFilterValue ? 'Tanpa Data' : $area)"
                        :selected="$selectedArea ?? ''"
                        placeholder="Semua"
                    />

                    <x-dashboard.filter-select
                        label="Cabang"
                        name="branch"
                        :options="$branches ?? collect()"
                        :selected="$selectedBranch ?? ''"
                        placeholder="Semua"
                    />

                    <x-dashboard.filter-select
                        label="Area Manager"
                        name="area_manager"
                        :options="collect($areaManagers ?? collect())->map(fn ($areaManager) => $areaManager === $emptyFilterValue ? 'Tanpa Data' : $areaManager)"
                        :selected="$selectedAreaManager ?? ''"
                        placeholder="Semua"
                    />

                    <x-dashboard.filter-select
                        label="Operation Manager"
                        name="operation_manager"
                        :options="collect($operationManagers ?? collect())->map(fn ($operationManager) => $operationManager === $emptyFilterValue ? 'Tanpa Data' : $operationManager)"
                        :selected="$selectedOperationManager ?? ''"
                        placeholder="Semua"
                    />

                    <x-dashboard.filter-select
                        label="Gaji"
                        name="pay_freq"
                        :options="$payFrequencies ?? collect()"
                        :selected="$selectedPayFrequency ?? ''"
                        placeholder="Semua"
                    />
                </div>
            </div>
        </form>
    </section>
@endsection
