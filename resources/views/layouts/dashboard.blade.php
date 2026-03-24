<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') | Business Management & Analysis System</title>

    @vite(['resources/css/app.css', 'resources/css/dashboard.css'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
</head>
@php
    $hasPasswordToast = session()->has('password_success') || $errors->passwordUpdate->any();
    $passwordToastType = session()->has('password_success') ? 'success' : ($errors->passwordUpdate->any() ? 'error' : '');
    $passwordToastMessage = session('password_success') ?: ($errors->passwordUpdate->first() ?: '');
@endphp
<body class="dashboard-shell bg-slate-100 text-slate-800">
    <div
        x-data="{
            sidebarOpen: window.innerWidth >= 1024,
            profileOpen: false,
            profileModalOpen: false,
            passwordModalOpen: {{ $hasPasswordToast ? 'true' : 'false' }},
            toastOpen: {{ $hasPasswordToast ? 'true' : 'false' }},
            toastType: @js($passwordToastType),
            toastMessage: @js($passwordToastMessage),
            screenWidth: window.innerWidth,
            init() {
                this.handleResize = this.handleResize.bind(this);
                window.addEventListener('resize', this.handleResize);
                this.handleResize();
            },
            handleResize() {
                this.screenWidth = window.innerWidth;
                if (this.screenWidth < 1024) {
                    this.sidebarOpen = false;
                }
            },
            destroy() {
                window.removeEventListener('resize', this.handleResize);
            }
        }"
        x-init="init()"
        class="dashboard-shell-layout"
    >
        <div
            :class="[
                'dashboard-layout',
                screenWidth >= 1024
                    ? (sidebarOpen ? 'layout-desktop-expanded' : 'layout-desktop-collapsed')
                    : (sidebarOpen ? 'layout-mobile-expanded' : 'layout-mobile-collapsed')
            ]"
        >
            <aside
                :class="[
                    'dashboard-sidebar',
                    sidebarOpen ? 'sidebar-expanded' : 'sidebar-collapsed',
                    screenWidth < 1024 ? 'sidebar-mobile' : 'sidebar-desktop'
                ]"
            >
                <div class="flex h-20 items-center gap-3 border-b border-white/10 px-5">
                    <img src="{{ asset('logo.png') }}" alt="Logo" class="h-11 w-11 rounded-xl object-cover shadow-lg">
                    <div x-show="sidebarOpen" x-transition.opacity class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-200">Business Management</p>
                        <p class="truncate text-xs text-slate-400">& Analysis System</p>
                    </div>
                </div>

                <div class="flex-1 px-4 py-5">
                    <p x-show="sidebarOpen" class="mb-3 px-3 text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">
                        Main Menu
                    </p>

                    @include('dashboard.partials.sidebar')
                </div>

                <div class="border-t border-white/10 p-4">
                    <button
                        @click="sidebarOpen = !sidebarOpen"
                        class="sidebar-collapse-toggle flex w-full items-center justify-center gap-3 rounded-2xl border border-white/10 bg-white/5 px-4 py-3 text-sm font-medium text-slate-200 transition hover:bg-white/10"
                    >
                        <span class="sidebar-icon">⇆</span>
                        <span x-show="sidebarOpen" x-transition.opacity>Collapsed View</span>
                    </button>
                </div>
            </aside>

            <div
                x-cloak
                x-show="screenWidth < 1024 && sidebarOpen"
                class="sidebar-backdrop"
                @click="sidebarOpen = false"
            ></div>

            <div class="dashboard-main-wrapper flex min-h-screen flex-1 flex-col">
                @include('dashboard.partials.topbar')

                <main class="dashboard-main flex-1 px-6 py-8 lg:px-10">
                    <div class="dashboard-main-inner">
                        @yield('content')
                    </div>
                </main>
            </div>
        </div>
    </div>
</body>
</html>
