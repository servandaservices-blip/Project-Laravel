@extends('layouts.dashboard')

@section('title', 'User')
@section('page_title', 'User')
@section('page_subtitle', 'Daftar user sistem')

@section('content')
    @php
        $userRows = collect($users ?? [])->values();
        $companyOptions = collect($companyOptions ?? [])->values();
        $divisionOptions = collect($divisionOptions ?? [])->values();
        $statusOptions = collect($statusOptions ?? [])->values();
        $positionOptions = collect($positionOptions ?? [])->values();
        $ruleOptions = collect($ruleOptions ?? []);
        $selectedDivisionFilter = $selectedDivisionFilter ?? '';
    @endphp

    <section
        x-data="{
            userSearch: @js($search ?? ''),
            formOpen: false,
            deleteOpen: false,
            formMode: 'create',
            deleteTarget: null,
            form: {
                id: null,
                name: '',
                username: '',
                division: '',
                company_access: [],
                position: [],
                status: 'Aktif',
                rules: '',
                password: ''
            },
            openCreate() {
                this.formMode = 'create';
                this.formOpen = true;
                this.form = {
                    id: null,
                    name: '',
                    username: '',
                    division: '',
                    company_access: [],
                    position: [],
                    status: 'Aktif',
                    rules: '',
                    password: ''
                };
            },
            openEdit(item) {
                this.formMode = 'edit';
                this.formOpen = true;
                this.form = {
                    id: item.id,
                    name: item.name || '',
                    username: item.username || '',
                    division: item.division || '',
                    company_access: Array.isArray(item.company_access) ? item.company_access : [],
                    position: Array.isArray(item.position) ? item.position : [],
                    status: item.status || 'Aktif',
                    rules: item.access_role || '',
                    password: ''
                };
            },
            openDelete(item) {
                this.deleteTarget = item;
                this.deleteOpen = true;
            }
        }"
        @keydown.escape.window="formOpen = false; deleteOpen = false"
        class="space-y-6"
    >
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

        <div class="user-header">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Settings User</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">User Management</h2>
                <p class="mt-2 text-[13px] leading-7 text-slate-400">
                    Halaman ini menampilkan master user beserta akses company, division, position, status, dan role rules.
                </p>
            </div>

            <div class="user-total-card">
                <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">Total User</span>
                <span class="text-2xl font-bold text-slate-900">{{ number_format($totalUsers ?? 0) }}</span>
            </div>
        </div>

        <section class="dashboard-card border border-slate-200 bg-white">
            <div class="user-table-header">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">User Table</p>
                    <h3 class="text-xl font-semibold text-slate-900">Tabel User</h3>
                    <p class="text-sm text-slate-500">Data user login beserta pengaturan akses di dalam sistem.</p>
                </div>

                <button
                    type="button"
                    @click="openCreate()"
                    class="user-add-button"
                >
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-white">+</span>
                    Tambah User
                </button>
            </div>

            <form method="GET" action="{{ route('settings.users.index') }}" class="user-table-toolbar">
                <div class="user-search-field">
                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Search User</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                        </span>
                        <input
                            x-model="userSearch"
                            type="text"
                            name="search"
                            placeholder="Cari nama, username, divisi, company, position..."
                            class="user-input"
                        >
                    </div>
                </div>

                <div class="user-filter-field">
                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Divisi</label>
                    <select name="division" onchange="this.form.submit()" class="user-input">
                        <option value="">Semua</option>
                        <option value="Cleaning" @selected($selectedDivisionFilter === 'Cleaning')>Cleaning</option>
                        <option value="Security" @selected($selectedDivisionFilter === 'Security')>Security</option>
                        <option value="-" @selected($selectedDivisionFilter === '-')>-</option>
                    </select>
                </div>
            </form>

            <div class="mt-6 overflow-x-auto">
                <table class="user-table min-w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">No</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Nama / Username</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Divisi</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Nama PT</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Position</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Status</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Rules</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($userRows as $index => $user)
                            @php
                                $payload = [
                                    'id' => $user->id,
                                    'name' => $user->name,
                                    'username' => $user->username,
                                    'division' => $user->division,
                                    'company_access' => array_values($user->company_access ?? []),
                                    'position' => array_values($user->position ?? []),
                                    'status' => $user->status,
                                    'access_role' => $user->access_role,
                                ];
                                $searchTarget = strtolower(implode(' ', array_filter([
                                    $user->name,
                                    $user->username,
                                    $user->division,
                                    implode(' ', $user->company_access ?? []),
                                    implode(' ', $user->position ?? []),
                                    $user->status,
                                    $user->access_role,
                                ])));
                            @endphp
                            <tr x-show="@js($searchTarget).includes(userSearch.toLowerCase())" x-transition.opacity class="user-table-row">
                                <td class="rounded-l-2xl px-4 py-4 text-center align-top text-sm font-medium text-slate-700">{{ $index + 1 }}</td>
                                <td class="px-4 py-4 align-top">
                                    <div class="space-y-1">
                                        <p class="text-sm font-semibold text-slate-900">{{ $user->name ?: '-' }}</p>
                                        <p class="text-sm text-slate-500">{{ $user->username }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-slate-700">{{ $user->division ?: '-' }}</td>
                                <td class="px-4 py-4 align-top text-sm text-slate-700">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse (($user->company_access ?? []) as $company)
                                            <span class="badge badge-company">{{ $company }}</span>
                                        @empty
                                            <span>-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-slate-700">
                                    <div class="flex flex-wrap gap-2">
                                        @forelse (($user->position ?? []) as $position)
                                            <span class="badge badge-role">{{ $position }}</span>
                                        @empty
                                            <span>-</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-center align-top text-sm">
                                    <span class="badge badge-status {{ ($user->status ?? '') === 'Aktif' ? 'badge-status-active' : 'badge-status-inactive' }}">
                                        {{ $user->status ?? '-' }}
                                    </span>
                                </td>
                                <td class="px-4 py-4 align-top text-sm text-slate-700">
                                    <div class="flex flex-wrap gap-2">
                                        @if (filled($user->access_role))
                                            @php
                                                $roleLabel = trim((string) $user->access_role);
                                                $roleTone = match (strtolower($roleLabel)) {
                                                    'admin', 'administrator' => 'badge-role-admin',
                                                    'security' => 'badge-role-security',
                                                    default => 'badge-role',
                                                };
                                            @endphp
                                            <span class="badge {{ $roleTone }}">{{ $roleLabel }}</span>
                                        @else
                                            <span>-</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="rounded-r-2xl px-4 py-4 text-center align-top">
                                    <div class="user-action-group">
                                        <form action="{{ route('settings.users.reset-password', $user->id) }}" method="POST" onsubmit="return confirm('Reset password user {{ $user->username }} menjadi Servanda123?');">
                                            @csrf
                                            <button
                                                type="submit"
                                                class="user-action-button text-emerald-700"
                                                title="Reset Password"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                    <path d="M3 12a9 9 0 1 0 3-6.708" />
                                                    <path d="M3 3v6h6" />
                                                    <path d="M12 7v5l3 3" />
                                                </svg>
                                            </button>
                                        </form>
                                        <button
                                            type="button"
                                            @click='openEdit(@json($payload))'
                                            class="user-action-button text-amber-700"
                                            title="Edit"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M12 20h9" />
                                                <path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4Z" />
                                            </svg>
                                        </button>
                                        <button
                                            type="button"
                                            @click='openDelete(@json($payload))'
                                            class="user-action-button text-rose-700"
                                            title="Hapus"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M3 6h18" />
                                                <path d="M8 6V4h8v2" />
                                                <path d="M19 6l-1 14H6L5 6" />
                                                <path d="M10 11v6" />
                                                <path d="M14 11v6" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Belum ada data user.
                                </td>
                            </tr>
                        @endforelse
                        @if ($userRows->count() > 0)
                            <tr x-show="!Array.from($el.parentElement.querySelectorAll('tr[x-show]')).some((element) => element.style.display !== 'none')">
                                <td colspan="7" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Data user tidak ditemukan.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <div x-cloak x-show="formOpen" x-transition.opacity class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-20">
            <div class="absolute inset-0" @click="formOpen = false"></div>
            <div x-show="formOpen" x-transition class="relative z-10 w-full max-w-4xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.20),_transparent_32%),linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#ecfeff_100%)] px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700" x-text="formMode === 'create' ? 'Tambah User' : 'Edit User'"></p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900" x-text="formMode === 'create' ? 'Form User Baru' : 'Perbarui User'"></h3>
                    </div>

                    <button type="button" @click="formOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="formMode === 'create' ? '{{ route('settings.users.store') }}' : '{{ url('/settings/users') }}/' + form.id" method="POST" class="px-6 py-6">
                    @csrf
                    <template x-if="formMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid gap-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Nama</label>
                            <input type="text" name="name" x-model="form.name" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Username</label>
                            <input type="text" name="username" x-model="form.username" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>
                            <select name="status" x-model="form.status" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                @foreach ($statusOptions as $status)
                                    <option value="{{ $status }}">{{ $status }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Divisi</label>
                            <select name="division" x-model="form.division" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                <option value="">Semua</option>
                                @foreach ($divisionOptions as $division)
                                    <option value="{{ $division }}">{{ $division }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700" x-text="formMode === 'create' ? 'Password' : 'Password Baru (Opsional)'"></label>
                            <template x-if="formMode === 'create'">
                                <div>
                                    <input type="text" value="Servanda123" class="w-full rounded-2xl border border-slate-200 bg-slate-100 px-4 py-3 text-sm font-medium text-slate-700 shadow-sm" readonly>
                                    <p class="mt-2 text-xs leading-5 text-slate-500">Password default user baru otomatis `Servanda123`.</p>
                                </div>
                            </template>
                            <template x-if="formMode === 'edit'">
                                <div>
                                    <input type="password" name="password" x-model="form.password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm">
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="mt-5 grid gap-5 xl:grid-cols-3">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-sm font-semibold text-slate-900">Nama PT</p>
                            <div class="mt-3 space-y-2">
                                @foreach ($companyOptions as $company)
                                    <label class="flex items-center gap-3 text-base font-medium text-slate-700">
                                        <input type="checkbox" name="company_access[]" value="{{ $company }}" x-model="form.company_access" class="h-5 w-5 rounded-md border-slate-300 text-blue-600 accent-blue-600 focus:ring-2 focus:ring-blue-500">
                                        <span>{{ $company }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-sm font-semibold text-slate-900">Position</p>
                            <div class="mt-3 max-h-[21rem] space-y-2 overflow-y-auto pr-2">
                                @forelse ($positionOptions as $position)
                                    <label class="flex items-center gap-3 text-base font-medium text-slate-700">
                                        <input type="checkbox" name="position[]" value="{{ $position }}" x-model="form.position" class="h-5 w-5 rounded-md border-slate-300 text-blue-600 accent-blue-600 focus:ring-2 focus:ring-blue-500">
                                        <span>{{ $position }}</span>
                                    </label>
                                @empty
                                    <p class="text-sm text-slate-500">Belum ada master position user.</p>
                                @endforelse
                            </div>
                        </div>

                        <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                            <p class="text-sm font-semibold text-slate-900">Rules</p>
                            <div class="mt-3">
                                <select name="rules" x-model="form.rules" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
                                    <option value="">Pilih Rules</option>
                                    @foreach ($ruleOptions as $ruleOption)
                                        <option value="{{ $ruleOption }}">{{ $ruleOption }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-3 text-xs leading-5 text-slate-500">
                                    Cleaning hanya melihat data Cleaning, Security hanya melihat data Security, Cleaning & Security dapat melihat data Cleaning dan Security, Administrator dapat melihat semua data dan menu Setting.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <button type="button" @click="formOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Simpan
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div x-cloak x-show="deleteOpen" x-transition.opacity class="fixed inset-0 z-[95] flex items-center justify-center bg-slate-950/45 px-4">
            <div class="absolute inset-0" @click="deleteOpen = false"></div>
            <div x-show="deleteOpen" x-transition class="relative z-10 w-full max-w-lg rounded-[30px] border border-slate-200 bg-white p-6 shadow-2xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600">Hapus User</p>
                <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Konfirmasi penghapusan</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    User <span class="font-semibold text-slate-900" x-text="deleteTarget?.username || '-'"></span> akan dihapus. Lanjutkan?
                </p>

                <form :action="'{{ url('/settings/users') }}/' + (deleteTarget?.id || '')" method="POST" class="mt-6 flex flex-wrap justify-end gap-3">
                    @csrf
                    @method('DELETE')
                    <button type="button" @click="deleteOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Batal
                    </button>
                    <button type="submit" class="rounded-2xl bg-rose-600 px-5 py-3 text-sm font-semibold text-white transition hover:bg-rose-500">
                        Hapus
                    </button>
                </form>
            </div>
        </div>
    </section>
@endsection
