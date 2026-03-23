@extends('layouts.dashboard')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('page_subtitle', 'Modern admin portal starter')

@section('content')
    <div class="grid gap-6 lg:grid-cols-3">
        <section class="dashboard-card lg:col-span-2">
            <div class="mb-4">
                <p class="dashboard-eyebrow">Welcome</p>
                <h2 class="text-2xl font-bold text-slate-900">Business Management & Analysis System</h2>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-500">
                    Halaman ini masih kosong dan siap dipakai sebagai dashboard utama setelah login.
                    Struktur sudah dibuat rapi agar mudah dikembangkan menjadi modul monitoring, laporan, employee, area, dan kontrak.
                </p>
            </div>

            <div class="dashboard-empty-state">
                <div class="dashboard-empty-icon">◫</div>
                <h3 class="text-lg font-semibold text-slate-900">Dashboard Starter Ready</h3>
                <p class="mt-2 text-sm text-slate-500">
                    Anda bisa mulai menambahkan card statistik, tabel data, chart, dan widget operasional dari sini.
                </p>
            </div>
        </section>

        <section class="dashboard-card">
            <p class="dashboard-eyebrow">Quick Info</p>
            <h3 class="text-lg font-semibold text-slate-900">Panel Ringkas</h3>

            <div class="mt-5 space-y-4">
                <div class="quick-info-box">
                    <p class="quick-info-label">Status Sistem</p>
                    <p class="quick-info-value text-emerald-600">Online</p>
                </div>

                <div class="quick-info-box">
                    <p class="quick-info-label">Login User</p>
                    <p class="quick-info-value">{{ auth()->user()->name ?? 'User' }}</p>
                </div>

                <div class="quick-info-box">
                    <p class="quick-info-label">Mode</p>
                    <p class="quick-info-value">Vite Development Ready</p>
                </div>
            </div>
        </section>
    </div>
@endsection
