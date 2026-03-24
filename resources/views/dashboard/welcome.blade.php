@extends('layouts.dashboard')

@section('title', 'Welcome')
@section('page_title', 'Welcome')
@section('page_subtitle', 'Halaman sambutan')

@section('content')
    @php
        $userName = trim((string) (auth()->user()?->name ?? auth()->user()?->username ?? ''));
        $greetingName = $userName !== '' ? $userName : 'Pengguna';
    @endphp

    <section class="dashboard-card border border-slate-200 bg-white">
        <div class="mx-auto max-w-3xl">
            <div class="rounded-[1.75rem] border border-slate-200 bg-gradient-to-br from-slate-50 via-white to-blue-50/60 p-8 shadow-sm text-center">
                <h2 class="text-3xl font-bold tracking-tight text-slate-900">Selamat datang</h2>
                <p class="mt-3 text-sm font-semibold uppercase tracking-[0.22em] text-slate-500">Business Management & Analysis System</p>
            </div>
        </div>
    </section>
@endsection
