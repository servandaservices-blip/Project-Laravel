@extends('layouts.dashboard')

@section('title', 'Site Area')
@section('page_title', 'Site Area')
@section('page_subtitle', 'Master data area per company')

@section('content')
    @php
        $companyOptions = collect($companyOptions ?? [])->mapWithKeys(fn ($config, $key) => [$key => $config['label']])->all();
        $divisionOptions = ['Security', 'Cleaning'];
        $forcedDivision = auth()->user()?->forcedDivision();
        $statusOptions = ['Aktif', 'Tidak Aktif'];
        $branchOptions = ['Metro', 'Balikpapan'];
        $siteAreaRows = collect($siteAreas?->items() ?? [])->values();
        $selectedPerPage = $selectedPerPage ?? 10;
        $areaManagerOptions = collect($areaManagerOptions ?? [])->values();
        $operationManagerOptions = collect($operationManagerOptions ?? [])->values();
        $selectedAreaManager = $selectedAreaManager ?? '';
        $selectedOperationManager = $selectedOperationManager ?? '';
        $selectedStatus = $selectedStatus ?? '';
    @endphp

    <section
        x-data="{
            areaSearch: @js($search ?? ''),
            formOpen: false,
            importOpen: false,
            form: {
                id: null,
                company: @js($selectedCompany ?? 'servanda'),
                area_name: '',
                division: @js(($selectedCompany ?? 'servanda') === 'servanda' ? ($selectedDivision ?? '') : 'General'),
                branch: '',
                area_manager: '',
                operation_manager: '',
                status: 'Aktif'
            },
            openEdit(item) {
                this.formOpen = true;
                this.form = {
                    id: item.id,
                    company: item.company,
                    area_name: item.area_name || '',
                    division: item.division || '',
                    branch: item.branch || '',
                    area_manager: item.area_manager || '',
                    operation_manager: item.operation_manager || '',
                    status: item.status || 'Aktif'
                };
            }
        }"
        @keydown.escape.window="formOpen = false; importOpen = false"
        class="space-y-6"
    >
        @if (session('success'))
            <div class="rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="flex flex-col gap-4 xl:flex-row xl:items-start xl:justify-between">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-sky-700">Site Area Master</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Site Area {{ $companyName }}</h2>
                <p class="mt-3 text-sm leading-7 text-slate-500">
                    Halaman ini menampilkan master data area yang dapat dikelola langsung dari sistem, termasuk cabang, manager, dan status area.
                </p>
            </div>

            <form method="GET" action="{{ route('site-area.index') }}" class="w-full xl:max-w-[1120px]">
                <input type="hidden" name="company" value="{{ $selectedCompany }}">
                <div class="rounded-[1.5rem] border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="grid gap-3 md:grid-cols-2 xl:grid-cols-[minmax(160px,1fr)_minmax(160px,1fr)_minmax(180px,1fr)_minmax(200px,1fr)_minmax(150px,1fr)] xl:items-end">
                        @if ($selectedCompany === 'servanda')
                            <div>
                                <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Divisi</label>
                                @if (filled($forcedDivision))
                                    <input type="hidden" name="division" value="{{ $forcedDivision }}">
                                    <div class="flex h-[50px] items-center rounded-2xl border border-amber-200 bg-amber-50 px-4 text-sm font-semibold text-amber-800">
                                        {{ $forcedDivision }}
                                    </div>
                                @else
                                    <select name="division" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                        <option value="">Semua</option>
                                        @foreach ($divisionOptions as $division)
                                            <option value="{{ $division }}" @selected(($selectedDivision ?? null) === $division)>{{ $division }}</option>
                                        @endforeach
                                    </select>
                                @endif
                            </div>
                        @endif

                        <div>
                            <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang</label>
                            <select name="branch" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach (collect($branchOptions ?? [])->values() as $branch)
                                    <option value="{{ $branch }}" @selected(($selectedBranch ?? '') === $branch)>{{ $branch }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Area Manager</label>
                            <select name="area_manager" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($areaManagerOptions as $areaManagerOption)
                                    <option value="{{ $areaManagerOption }}" @selected($selectedAreaManager === $areaManagerOption)>{{ $areaManagerOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Operation Manager</label>
                            <select name="operation_manager" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($operationManagerOptions as $operationManagerOption)
                                    <option value="{{ $operationManagerOption }}" @selected($selectedOperationManager === $operationManagerOption)>{{ $operationManagerOption }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-[10px] font-semibold uppercase tracking-[0.18em] text-slate-500">Status</label>
                            <select name="status" onchange="this.form.submit()" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($statusOptions as $statusOption)
                                    <option value="{{ $statusOption }}" @selected($selectedStatus === $statusOption)>{{ $statusOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <section class="dashboard-card border border-slate-200 bg-white">
            <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 lg:flex-row lg:items-end lg:justify-between">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Area Table</p>
                    <h3 class="text-xl font-semibold text-slate-900">Tabel Nama Area</h3>
                    <p class="text-sm text-slate-500">Daftar master area {{ $companyName }} beserta informasi cabang dan manager.</p>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button
                        type="button"
                        @click="importOpen = true"
                        class="inline-flex h-[48px] items-center justify-center rounded-2xl border border-violet-200 bg-violet-50 px-5 text-sm font-semibold text-violet-700 shadow-sm transition hover:border-violet-300 hover:bg-violet-100"
                    >
                        Import Excel
                    </button>

                    <form method="GET" action="{{ route('site-area.template-export') }}">
                        <input type="hidden" name="company" value="{{ $selectedCompany }}">
                        @if ($selectedCompany === 'servanda' && ! empty($selectedDivision))
                            <input type="hidden" name="division" value="{{ $selectedDivision }}">
                        @endif
                        @if (! empty($selectedBranch))
                            <input type="hidden" name="branch" value="{{ $selectedBranch }}">
                        @endif
                        @if (! empty($selectedAreaManager))
                            <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                        @endif
                        @if (! empty($selectedOperationManager))
                            <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">
                        @endif
                        @if (! empty($selectedStatus))
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                        <button
                            type="submit"
                            class="inline-flex h-[48px] items-center justify-center rounded-2xl border border-slate-200 bg-white px-5 text-sm font-semibold text-slate-700 shadow-sm transition hover:border-slate-300 hover:bg-slate-50"
                        >
                            Export Template
                        </button>
                    </form>

                    <form method="GET" action="{{ route('site-area.export') }}">
                        <input type="hidden" name="company" value="{{ $selectedCompany }}">
                        @if ($selectedCompany === 'servanda' && ! empty($selectedDivision))
                            <input type="hidden" name="division" value="{{ $selectedDivision }}">
                        @endif
                        @if (! empty($selectedBranch))
                            <input type="hidden" name="branch" value="{{ $selectedBranch }}">
                        @endif
                        @if (! empty($selectedAreaManager))
                            <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                        @endif
                        @if (! empty($selectedOperationManager))
                            <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">
                        @endif
                        @if (! empty($selectedStatus))
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                        <button
                            type="submit"
                            class="inline-flex h-[48px] items-center justify-center rounded-2xl border border-emerald-200 bg-emerald-50 px-5 text-sm font-semibold text-emerald-700 shadow-sm transition hover:border-emerald-300 hover:bg-emerald-100"
                        >
                            Export Excel
                        </button>
                    </form>

                    <form method="POST" action="{{ route('site-area.sync') }}">
                        @csrf
                        <input type="hidden" name="company" value="{{ $selectedCompany }}">
                        @if ($selectedCompany === 'servanda' && ! empty($selectedDivision))
                            <input type="hidden" name="division" value="{{ $selectedDivision }}">
                        @endif
                        @if (! empty($selectedBranch))
                            <input type="hidden" name="branch" value="{{ $selectedBranch }}">
                        @endif
                        @if (! empty($selectedAreaManager))
                            <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                        @endif
                        @if (! empty($selectedOperationManager))
                            <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">
                        @endif
                        @if (! empty($selectedStatus))
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif
                        <button
                            type="submit"
                            class="inline-flex h-[48px] items-center justify-center rounded-2xl border border-sky-200 bg-sky-50 px-5 text-sm font-semibold text-sky-700 shadow-sm transition hover:border-sky-300 hover:bg-sky-100"
                        >
                            SYC Site Area
                        </button>
                    </form>
                </div>
            </div>

            <div class="mt-4">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div class="flex-1">
                        <label class="mb-2 block text-sm font-medium text-slate-700">Search Area</label>
                        <input
                            x-model="areaSearch"
                            type="text"
                            placeholder="Cari nama area, cabang, manager..."
                            class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm outline-none transition focus:border-sky-300 focus:bg-white"
                        >
                    </div>

                    <form method="GET" action="{{ route('site-area.index') }}" class="w-full lg:w-auto">
                        <input type="hidden" name="company" value="{{ $selectedCompany }}">
                        @if ($selectedCompany === 'servanda' && filled($selectedDivision ?? null))
                            <input type="hidden" name="division" value="{{ $selectedDivision }}">
                        @endif
                        @if (filled($selectedBranch ?? ''))
                            <input type="hidden" name="branch" value="{{ $selectedBranch }}">
                        @endif
                        @if (filled($selectedAreaManager ?? ''))
                            <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                        @endif
                        @if (filled($selectedOperationManager ?? ''))
                            <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">
                        @endif
                        @if (filled($selectedStatus ?? ''))
                            <input type="hidden" name="status" value="{{ $selectedStatus }}">
                        @endif

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Show</label>
                            <select
                                name="per_page"
                                onchange="this.form.submit()"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm lg:min-w-[120px]"
                            >
                                @foreach ([10, 50, 100] as $perPageOption)
                                    <option value="{{ $perPageOption }}" @selected((int) $selectedPerPage === $perPageOption)>{{ $perPageOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <div class="mt-5 overflow-x-auto">
                <table class="min-w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">No</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Nama Area</th>
                            @if ($selectedCompany === 'servanda')
                                <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Division</th>
                            @endif
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Cabang</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Area Manager</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Operation Manager</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Status</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($siteAreaRows as $index => $siteArea)
                            @php
                                $payload = [
                                    'id' => $siteArea->id,
                                    'company' => $siteArea->company,
                                    'area_name' => $siteArea->area_name,
                                    'division' => $siteArea->division,
                                    'branch' => $siteArea->branch,
                                    'area_manager' => $siteArea->area_manager,
                                    'operation_manager' => $siteArea->operation_manager,
                                    'status' => $siteArea->status,
                                ];
                                $searchTarget = strtolower(implode(' ', array_filter([
                                    $siteArea->area_name,
                                    $siteArea->division,
                                    $siteArea->branch,
                                    $siteArea->area_manager,
                                    $siteArea->operation_manager,
                                    $siteArea->status,
                                ])));
                            @endphp
                            <tr x-show="@js($searchTarget).includes(areaSearch.toLowerCase())" x-transition.opacity class="rounded-2xl bg-slate-50/80 shadow-sm">
                                <td class="rounded-l-2xl px-4 py-4 text-sm font-medium text-slate-700">{{ (($siteAreas->currentPage() - 1) * $siteAreas->perPage()) + $index + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $siteArea->area_name }}</td>
                                @if ($selectedCompany === 'servanda')
                                    <td class="px-4 py-4 text-sm">
                                        <span class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold {{ ($siteArea->division ?? '') === 'Security' ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-emerald-200 bg-emerald-50 text-emerald-700' }}">
                                            {{ $siteArea->division ?: '-' }}
                                        </span>
                                    </td>
                                @endif
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $siteArea->branch ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $siteArea->area_manager ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm text-slate-700">{{ $siteArea->operation_manager ?: '-' }}</td>
                                <td class="px-4 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ ($siteArea->status ?? '') === 'Aktif' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-inset ring-emerald-200' : 'bg-rose-50 text-rose-700 ring-1 ring-inset ring-rose-200' }}">
                                        {{ $siteArea->status ?: '-' }}
                                    </span>
                                </td>
                                <td class="rounded-r-2xl px-4 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <button
                                            type="button"
                                            @click='openEdit(@json($payload))'
                                            class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-amber-200 bg-amber-50 text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
                                            title="Edit"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ $selectedCompany === 'servanda' ? '8' : '7' }}" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Belum ada data area untuk company ini.
                                </td>
                            </tr>
                        @endforelse
                        @if ($siteAreaRows->count() > 0)
                            <tr x-show="!Array.from($el.parentElement.querySelectorAll('tr[x-show]')).some((element) => element.style.display !== 'none')">
                                <td colspan="{{ $selectedCompany === 'servanda' ? '8' : '7' }}" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Data area tidak ditemukan.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            @if (method_exists($siteAreas, 'links'))
                <div class="mt-5">
                    {{ $siteAreas->links() }}
                </div>
            @endif
        </section>

        <div
            x-cloak
            x-show="importOpen"
            x-transition.opacity
            class="fixed inset-0 z-[92] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-20"
        >
            <div class="absolute inset-0" @click="importOpen = false"></div>

            <div x-show="importOpen" x-transition class="relative z-10 w-full max-w-2xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(139,92,246,0.18),_transparent_35%),linear-gradient(135deg,#f5f3ff_0%,#ffffff_55%,#eef2ff_100%)] px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-violet-700">Import Site Area</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Upload File Bulk Update</h3>
                        <p class="mt-2 text-sm text-slate-500">Gunakan file hasil `Export Template`, edit di Excel, lalu upload kembali untuk update massal.</p>
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

                <form action="{{ route('site-area.import') }}" method="POST" enctype="multipart/form-data" class="px-6 py-6">
                    @csrf
                    <input type="hidden" name="company" value="{{ $selectedCompany }}">
                    @if ($selectedCompany === 'servanda' && ! empty($selectedDivision))
                        <input type="hidden" name="division" value="{{ $selectedDivision }}">
                    @endif
                    @if (! empty($selectedBranch))
                        <input type="hidden" name="branch" value="{{ $selectedBranch }}">
                    @endif
                    @if (! empty($selectedAreaManager))
                        <input type="hidden" name="area_manager" value="{{ $selectedAreaManager }}">
                    @endif
                    @if (! empty($selectedOperationManager))
                        <input type="hidden" name="operation_manager" value="{{ $selectedOperationManager }}">
                    @endif
                    @if (! empty($selectedStatus))
                        <input type="hidden" name="status" value="{{ $selectedStatus }}">
                    @endif

                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <label class="mb-2 block text-sm font-medium text-slate-700">File Import</label>
                        <input type="file" name="import_file" accept=".csv,.xlsx,.txt" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
                        <div class="mt-3 space-y-1 text-xs leading-5 text-slate-500">
                            <p>Format yang didukung: `.csv` dan `.xlsx`</p>
                            <p>Kolom wajib: `id`, `company`, `area_name`, `division`, `branch`, `area_manager`, `operation_manager`, `status`</p>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <button type="button" @click="importOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Import Sekarang
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div
            x-cloak
            x-show="formOpen"
            x-transition.opacity
            class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-20"
        >
            <div class="absolute inset-0" @click="formOpen = false"></div>

            <div x-show="formOpen" x-transition class="relative z-10 w-full max-w-3xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.20),_transparent_32%),linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#ecfeff_100%)] px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Edit Area</p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Perbarui Site Area</h3>
                    </div>

                    <button
                        type="button"
                        @click="formOpen = false"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700"
                        title="Tutup"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="'{{ url('/site-area') }}/' + form.id" method="POST" class="px-6 py-6">
                    @csrf
                    <input type="hidden" name="_method" value="PUT">
                    <input type="hidden" name="company" :value="form.company">
                    <input type="hidden" name="division" :value="form.division">

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Company</label>
                            <input type="text" :value="form.company" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm" readonly>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Division</label>
                            <div class="flex h-[50px] items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-medium text-slate-700">
                                <span
                                    class="inline-flex rounded-full border px-3 py-1 text-xs font-semibold"
                                    :class="form.division === 'Security'
                                        ? 'border-amber-200 bg-amber-50 text-amber-700'
                                        : (form.division === 'Cleaning'
                                            ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                            : 'border-sky-200 bg-sky-50 text-sky-700')"
                                    x-text="form.division || 'General'"
                                ></span>
                            </div>
                        </div>
                        <div class="md:col-span-2">
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nama Area</label>
                            <input type="text" name="area_name" x-model="form.area_name" class="w-full rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-700 shadow-sm" readonly>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Cabang</label>
                            <template x-if="form.company === 'servanda'">
                                <div class="flex h-[50px] items-center rounded-2xl border border-slate-200 bg-slate-50 px-4 text-sm font-medium text-slate-700">
                                    <span x-text="form.branch || '-'"></span>
                                    <input type="hidden" name="branch" :value="form.branch">
                                </div>
                            </template>
                            <template x-if="form.company !== 'servanda'">
                                <select name="branch" x-model="form.branch" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                    <option value="">Pilih Cabang</option>
                                    @foreach ($branchOptions as $branch)
                                        <option value="{{ $branch }}">{{ $branch }}</option>
                                    @endforeach
                                </select>
                            </template>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" x-model="form.status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Area Manager</label>
                            <select name="area_manager" x-model="form.area_manager" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                <option value="">Pilih Area Manager</option>
                                @foreach ($areaManagerOptions as $areaManagerOption)
                                    <option value="{{ $areaManagerOption }}">{{ $areaManagerOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Operation Manager</label>
                            <select name="operation_manager" x-model="form.operation_manager" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                <option value="">Pilih Operation Manager</option>
                                @foreach ($operationManagerOptions as $operationManagerOption)
                                    <option value="{{ $operationManagerOption }}">{{ $operationManagerOption }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <button type="button" @click="formOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button
                            type="submit"
                            class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800"
                            :disabled="form.company === 'servanda' && !form.division"
                        >
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>
@endsection
