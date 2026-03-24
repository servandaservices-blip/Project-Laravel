@php
    $currentUser = auth()->user();
    $userName = $currentUser->name ?: ($currentUser->username ?? 'User');
    $userRole = $currentUser->access_role ?? 'User';
    $userDivision = $currentUser->division ?? null;
    $userCompanies = collect($currentUser->company_access ?? [])->filter()->values();
    $userPositions = collect($currentUser->position ?? [])->filter()->values();
    $passwordUpdateRouteExists = \Illuminate\Support\Facades\Route::has('profile.password.update');
@endphp

<header class="sticky top-0 z-30 border-b border-slate-200/70 bg-white/80 backdrop-blur">
    <div class="flex h-20 items-center justify-between gap-3 px-6 lg:px-10">
        <div class="flex items-center gap-3">
            <button
                type="button"
                @click="sidebarOpen = !sidebarOpen"
                class="dashboard-hamburger"
                aria-label="Toggle sidebar"
            >
                <span></span>
                <span></span>
                <span></span>
            </button>
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-slate-900">@yield('page_title', 'Dashboard')</h1>
                <p class="text-sm text-slate-500">@yield('page_subtitle', 'Modern admin portal')</p>
            </div>
        </div>

        <div class="relative" @click.outside="profileOpen = false">
            <button
                @click="profileOpen = !profileOpen"
                class="flex items-center gap-3 rounded-[1.35rem] border border-slate-200 bg-white px-3 py-2 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md"
            >
                <div class="flex h-11 w-11 items-center justify-center rounded-[1rem] bg-slate-900 text-sm font-bold text-white shadow-sm">
                    {{ strtoupper(substr($userName, 0, 1)) }}
                </div>
                <div class="hidden text-left sm:block">
                    <p class="text-sm font-semibold text-slate-900">{{ $userName }}</p>
                    <p class="text-xs text-slate-500">{{ $userRole }}</p>
                </div>
                <span class="text-slate-400 transition" :class="{ 'rotate-180': profileOpen }">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m6 9 6 6 6-6" />
                    </svg>
                </span>
            </button>

            <div
                x-show="profileOpen"
                x-transition
                class="absolute right-0 mt-3 w-72 overflow-hidden rounded-[1.7rem] border border-slate-200 bg-white shadow-2xl"
            >
                <div class="border-b border-slate-100 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.16),_transparent_35%),linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#ecfeff_100%)] px-4 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-[1rem] bg-slate-900 text-sm font-bold text-white shadow-sm">
                            {{ strtoupper(substr($userName, 0, 1)) }}
                        </div>
                        <div>
                            <p class="text-sm font-semibold text-slate-900">{{ $userName }}</p>
                            <p class="text-xs text-slate-500">{{ $userRole }}</p>
                        </div>
                    </div>
                </div>

                <div class="p-2">
                    <button
                        type="button"
                        @click="profileOpen = false; profileModalOpen = true"
                        class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-sky-50 text-sky-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                                <circle cx="12" cy="7" r="4" />
                            </svg>
                        </span>
                        <span>Profil</span>
                    </button>

                    <button
                        type="button"
                        @click="profileOpen = false; passwordModalOpen = true"
                        class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                    >
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-amber-50 text-amber-700">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="11" width="18" height="10" rx="2" />
                                <path d="M7 11V8a5 5 0 0 1 10 0v3" />
                            </svg>
                        </span>
                        <span>Rubah Password</span>
                    </button>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-3 rounded-2xl px-3 py-3 text-left text-sm font-medium text-rose-600 transition hover:bg-rose-50">
                            <span class="inline-flex h-9 w-9 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                    <path d="m16 17 5-5-5-5" />
                                    <path d="M21 12H9" />
                                </svg>
                            </span>
                            <span>Logout</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="toastOpen" x-transition.opacity class="pointer-events-none fixed right-4 top-24 z-[120] w-full max-w-sm px-4 sm:right-6 sm:px-0">
        <div
            class="pointer-events-auto overflow-hidden rounded-[28px] border shadow-2xl backdrop-blur"
            :class="toastType === 'success'
                ? 'border-emerald-200 bg-emerald-50/95 text-emerald-900'
                : 'border-rose-200 bg-rose-50/95 text-rose-900'"
        >
            <div class="flex items-start gap-3 px-5 py-4">
                <span
                    class="mt-0.5 inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl"
                    :class="toastType === 'success'
                        ? 'bg-emerald-100 text-emerald-700'
                        : 'bg-rose-100 text-rose-700'"
                >
                    <svg x-show="toastType === 'success'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m20 6-11 11-5-5" />
                    </svg>
                    <svg x-show="toastType === 'error'" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10" />
                        <path d="M12 8v4" />
                        <path d="M12 16h.01" />
                    </svg>
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold" x-text="toastType === 'success' ? 'Berhasil' : 'Gagal'"></p>
                    <p class="mt-1 text-sm leading-6" x-text="toastMessage"></p>
                </div>

                <button
                    type="button"
                    @click="toastOpen = false"
                    class="inline-flex h-9 w-9 items-center justify-center rounded-2xl border border-current/10 bg-white/70 text-current transition hover:bg-white"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div x-cloak x-show="profileModalOpen" x-transition.opacity class="fixed inset-0 z-[90] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-24">
        <div class="absolute inset-0" @click="profileModalOpen = false"></div>
        <div x-show="profileModalOpen" x-transition class="relative z-10 w-full max-w-2xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(14,165,233,0.18),_transparent_35%),linear-gradient(135deg,#eff6ff_0%,#ffffff_55%,#ecfeff_100%)] px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sky-700">Profil User</p>
                    <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900">{{ $userName }}</h3>
                    <p class="mt-2 text-sm text-slate-500">Informasi akun yang sedang login ke sistem.</p>
                </div>

                <button type="button" @click="profileModalOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            <div class="px-6 py-6">
                <div class="grid gap-4 md:grid-cols-2">
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Username</p>
                        <div class="mt-3 rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm font-medium text-slate-700 shadow-sm">
                            {{ $userName }}
                        </div>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Rules</p>
                        <p class="mt-2 text-base font-bold text-slate-900">{{ $userRole }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Status</p>
                        <p class="mt-2 text-base font-bold text-slate-900">{{ $currentUser->status ?? '-' }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Divisi</p>
                        <p class="mt-2 text-base font-bold text-slate-900">{{ $userDivision ?: '-' }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Total Akses PT</p>
                        <p class="mt-2 text-base font-bold text-slate-900">{{ $userCompanies->count() ?: 'Semua PT' }}</p>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Nama PT</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($userCompanies as $company)
                                <span class="inline-flex rounded-full bg-sky-50 px-3 py-1 text-xs font-semibold text-sky-700 ring-1 ring-inset ring-sky-200">{{ $company }}</span>
                            @empty
                                <span class="text-sm text-slate-500">Semua PT</span>
                            @endforelse
                        </div>
                    </div>
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-4 md:col-span-2">
                        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-slate-500">Position</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @forelse ($userPositions as $position)
                                <span class="inline-flex rounded-full bg-violet-50 px-3 py-1 text-xs font-semibold text-violet-700 ring-1 ring-inset ring-violet-200">{{ $position }}</span>
                            @empty
                                <span class="text-sm text-slate-500">Belum diisi</span>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <button type="button" @click="profileModalOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div x-cloak x-show="passwordModalOpen" x-transition.opacity class="fixed inset-0 z-[95] flex items-start justify-center bg-slate-950/45 px-4 pb-6 pt-24">
        <div class="absolute inset-0" @click="passwordModalOpen = false"></div>
        <div x-show="passwordModalOpen" x-transition class="relative z-10 w-full max-w-2xl overflow-hidden rounded-[30px] border border-slate-200 bg-white shadow-2xl">
            <div class="flex items-start justify-between gap-4 border-b border-slate-200 bg-[radial-gradient(circle_at_top_left,_rgba(245,158,11,0.16),_transparent_35%),linear-gradient(135deg,#fff7ed_0%,#ffffff_55%,#fffbeb_100%)] px-6 py-5">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-amber-700">Rubah Password</p>
                    <h3 class="mt-2 text-xl font-bold tracking-tight text-slate-900">Perbarui Password Login</h3>
                </div>

                <button type="button" @click="passwordModalOpen = false" class="inline-flex h-10 w-10 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:bg-slate-50 hover:text-slate-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M18 6 6 18" />
                        <path d="m6 6 12 12" />
                    </svg>
                </button>
            </div>

            @if ($passwordUpdateRouteExists)
                <form method="POST" action="{{ route('profile.password.update') }}" class="px-6 py-6">
                    @csrf
                    @method('PUT')

                    <div class="grid gap-4">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Password Saat Ini</label>
                            <input type="password" name="current_password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Password Baru</label>
                            <input type="password" name="password" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
                        </div>
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Konfirmasi Password Baru</label>
                            <input type="password" name="password_confirmation" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-700 shadow-sm" required>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap justify-end gap-3">
                        <button type="button" @click="passwordModalOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Batal
                        </button>
                        <button type="submit" class="rounded-2xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Simpan Password
                        </button>
                    </div>
                </form>
            @else
                <div class="px-6 py-6">
                    <div class="rounded-2xl border border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-800">
                        Fitur ubah password belum tersedia karena route `profile.password.update` belum didaftarkan.
                    </div>

                    <div class="mt-6 flex justify-end">
                        <button type="button" @click="passwordModalOpen = false" class="rounded-2xl border border-slate-200 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Tutup
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </div>
</header>
