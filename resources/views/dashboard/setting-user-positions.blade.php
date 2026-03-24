@extends('layouts.dashboard')

@section('title', 'Position User')
@section('page_title', 'Position User')
@section('page_subtitle', 'Master position untuk user sistem')

@section('content')
    @php
        $positionRows = collect($positions ?? [])->values();
    @endphp

    <section
        x-data="{
            positionSearch: @js($search ?? ''),
            formOpen: false,
            deleteOpen: false,
            formMode: 'create',
            deleteTarget: null,
            form: { id: null, name: '' },
            openCreate() {
                this.formMode = 'create';
                this.formOpen = true;
                this.form = { id: null, name: '' };
            },
            openEdit(item) {
                this.formMode = 'edit';
                this.formOpen = true;
                this.form = { id: item.id, name: item.name || '' };
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

        <div class="user-header">
            <div class="max-w-3xl">
                <p class="text-[11px] font-semibold uppercase tracking-[0.24em] text-slate-500">Settings User</p>
                <h2 class="mt-2 text-3xl font-bold tracking-tight text-slate-900">Position User Management</h2>
                <p class="mt-2 text-[13px] leading-7 text-slate-400">
                    Halaman ini menampilkan master position khusus untuk user sistem, bukan position employee.
                </p>
            </div>

            <div class="user-total-card">
                <span class="text-[10px] font-semibold uppercase tracking-[0.22em] text-slate-400">Total Position User</span>
                <span class="text-2xl font-bold text-slate-900">{{ number_format($totalPositions ?? 0) }}</span>
            </div>
        </div>

        <section class="dashboard-card border border-slate-200 bg-white">
            <div class="user-table-header">
                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Position User Table</p>
                    <h3 class="text-xl font-semibold text-slate-900">Tabel Position User</h3>
                    <p class="text-sm text-slate-500">Master position yang digunakan untuk kebutuhan user di sistem.</p>
                </div>

                <button
                    type="button"
                    @click="openCreate()"
                    class="user-add-button"
                >
                    <span class="inline-flex h-5 w-5 items-center justify-center rounded-full bg-white/20 text-white">+</span>
                    Tambah Position User
                </button>
            </div>

            <div class="user-table-toolbar">
                <div class="user-search-field">
                    <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-500">Search Position User</label>
                    <div class="relative">
                        <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8" />
                                <path d="m21 21-4.3-4.3" />
                            </svg>
                        </span>
                        <input
                            x-model="positionSearch"
                            type="text"
                            placeholder="Cari nama position user..."
                            class="user-input"
                        >
                    </div>
                </div>
            </div>

            <div class="mt-6 overflow-x-auto">
                <table class="user-table min-w-full border-separate border-spacing-y-2">
                    <thead>
                        <tr>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">No</th>
                            <th class="px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Position User</th>
                            <th class="px-4 py-3 text-center text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positionRows as $index => $item)
                            @php
                                $payload = [
                                    'id' => $item->id,
                                    'name' => $item->name,
                                ];
                                $searchTarget = strtolower(implode(' ', array_filter([
                                    $item->name,
                                ])));
                            @endphp
                            <tr x-show="@js($searchTarget).includes(positionSearch.toLowerCase())" x-transition.opacity class="user-table-row">
                                <td class="rounded-l-2xl px-4 py-4 text-center text-sm font-medium text-slate-700">{{ $index + 1 }}</td>
                                <td class="px-4 py-4 text-sm font-semibold text-slate-900">{{ $item->name }}</td>
                                <td class="rounded-r-2xl px-4 py-4 text-center">
                                    <div class="user-action-group">
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
                                <td colspan="3" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Belum ada data position user.
                                </td>
                            </tr>
                        @endforelse
                        @if ($positionRows->count() > 0)
                            <tr x-show="!Array.from($el.parentElement.querySelectorAll('tr[x-show]')).some((element) => element.style.display !== 'none')">
                                <td colspan="3" class="rounded-2xl border border-dashed border-slate-200 px-4 py-10 text-center text-sm text-slate-500">
                                    Data position user tidak ditemukan.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </section>

        <div x-cloak x-show="formOpen" x-transition.opacity class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-20">
            <div class="absolute inset-0" @click="formOpen = false"></div>
            <div x-show="formOpen" x-transition class="relative z-10 w-full max-w-2xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.20),_transparent_32%),linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#ecfeff_100%)] px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700" x-text="formMode === 'create' ? 'Tambah Position User' : 'Edit Position User'"></p>
                        <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900" x-text="formMode === 'create' ? 'Form Position User Baru' : 'Perbarui Position User'"></h3>
                    </div>

                    <button type="button" @click="formOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700" title="Tutup">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18" />
                            <path d="m6 6 12 12" />
                        </svg>
                    </button>
                </div>

                <form :action="formMode === 'create' ? '{{ route('settings.positions.store') }}' : '{{ url('/settings/positions') }}/' + form.id" method="POST" class="px-6 py-6">
                    @csrf
                    <template x-if="formMode === 'edit'">
                        <input type="hidden" name="_method" value="PUT">
                    </template>

                    <div class="grid gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Position User</label>
                            <input type="text" name="name" x-model="form.name" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
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
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-rose-600">Hapus Position User</p>
                <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Konfirmasi penghapusan</h3>
                <p class="mt-3 text-sm leading-6 text-slate-600">
                    Position user <span class="font-semibold text-slate-900" x-text="deleteTarget?.name || '-'"></span> akan dihapus. Lanjutkan?
                </p>

                <form :action="'{{ url('/settings/positions') }}/' + (deleteTarget?.id || '')" method="POST" class="mt-6 flex flex-wrap justify-end gap-3">
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
