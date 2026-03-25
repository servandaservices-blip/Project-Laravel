@php
    $currentUser = auth()->user();
    $employeeMenuCompanies = [
        'servanda' => 'Servanda',
        'gabe' => 'Gabe',
        'salus' => 'Salus',
    ];
    $employeeMenuCompanies = collect($employeeMenuCompanies)
        ->filter(fn ($companyLabel, $companyKey) => ! $currentUser || $currentUser->canAccessCompany($companyKey))
        ->all();
    $showSettingMenu = $currentUser?->isAdministrator() ?? false;
    $showTargetMenu = $currentUser?->canAccessTargetMenu() ?? false;
    $activeCompany = request('company', 'servanda');
@endphp

<nav class="space-y-2">
    <div x-data="{ open: {{ request()->routeIs('dashboard') || request()->routeIs('dashboard.workday') || request()->routeIs('dashboard.attendance-area') || request()->routeIs('dashboard.attendance-position') ? 'true' : 'false' }} }" class="space-y-2">
        <button
            type="button"
            @click="open = !open"
            class="sidebar-link w-full justify-between {{ request()->routeIs('dashboard') || request()->routeIs('dashboard.workday') || request()->routeIs('dashboard.attendance-area') || request()->routeIs('dashboard.attendance-position') ? 'sidebar-link-active' : '' }}"
        >
            <span class="flex items-center gap-3">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 10.5L12 3l9 7.5" />
                        <path d="M5.25 9.75V20h13.5V9.75" />
                    </svg>
                </span>
                <span x-show="sidebarOpen" x-transition.opacity>Dashboard</span>
            </span>

            <span x-show="sidebarOpen" x-transition.opacity class="sidebar-chevron" :class="{ 'rotate-180': open }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <div
            x-show="open && sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sidebar-submenu"
        >
            <a
                href="{{ route('dashboard.workday', ['company' => $activeCompany]) }}"
                class="sidebar-sublink {{ request()->routeIs('dashboard.workday') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="5" width="16" height="15" rx="2.5" />
                        <path d="M8 3v4" />
                        <path d="M16 3v4" />
                        <path d="M4 10h16" />
                        <path d="M9 14h2" />
                        <path d="M13 14h2" />
                    </svg>
                </span>
                <span>Hari Kerja</span>
            </a>
            <a
                href="{{ route('dashboard.attendance-area', ['company' => $activeCompany]) }}"
                class="sidebar-sublink {{ request()->routeIs('dashboard.attendance-area') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5h16" />
                        <path d="M7 16V8" />
                        <path d="M12 16V4.5" />
                        <path d="M17 16v-6" />
                    </svg>
                </span>
                <span>Attendance Area</span>
            </a>
            <a
                href="{{ route('dashboard.attendance-position', ['company' => $activeCompany]) }}"
                class="sidebar-sublink {{ request()->routeIs('dashboard.attendance-position') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 7h8" />
                        <path d="M6 12h12" />
                        <path d="M10 17h4" />
                        <rect x="4" y="4" width="16" height="16" rx="2.5" />
                    </svg>
                </span>
                <span>Attandence Position</span>
            </a>
        </div>
    </div>

    <div x-data="{ open: {{ request()->routeIs('employee.index') ? 'true' : 'false' }} }" class="space-y-2">
        <button
            type="button"
            @click="open = !open"
            class="sidebar-link w-full justify-between {{ request()->routeIs('employee.index') ? 'sidebar-link-active' : '' }}"
        >
            <span class="flex items-center gap-3">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v2" />
                        <circle cx="9.5" cy="7" r="3.5" />
                        <path d="M20.5 20.5v-1.5a3.2 3.2 0 0 0-2.4-3.1" />
                        <path d="M15.5 4.2a3.2 3.2 0 0 1 0 6.1" />
                    </svg>
                </span>
                <span x-show="sidebarOpen" x-transition.opacity>Employee</span>
            </span>

            <span x-show="sidebarOpen" x-transition.opacity class="sidebar-chevron" :class="{ 'rotate-180': open }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <div
            x-show="open && sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sidebar-submenu"
        >
            @foreach ($employeeMenuCompanies as $companyKey => $companyLabel)
                <a
                    href="{{ route('employee.index', ['company' => $companyKey]) }}"
                    class="sidebar-sublink {{ request()->routeIs('employee.index') && $activeCompany === $companyKey ? 'sidebar-sublink-active' : '' }}"
                >
                    <span class="sidebar-subicon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5h16" />
                            <path d="M7 16V8" />
                            <path d="M12 16V4.5" />
                            <path d="M17 16v-6" />
                        </svg>
                    </span>
                    <span>{{ $companyLabel }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div x-data="{ open: {{ request()->routeIs('attendance.summary') ? 'true' : 'false' }} }" class="space-y-2">
        <button
            type="button"
            @click="open = !open"
            class="sidebar-link w-full justify-between {{ request()->routeIs('attendance.summary') ? 'sidebar-link-active' : '' }}"
        >
            <span class="flex items-center gap-3">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 20V10" />
                        <path d="M18 20V4" />
                        <path d="M6 20v-6" />
                    </svg>
                </span>
                <span x-show="sidebarOpen" x-transition.opacity>Realisasi Hari Kerja</span>
            </span>

            <span x-show="sidebarOpen" x-transition.opacity class="sidebar-chevron" :class="{ 'rotate-180': open }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <div
            x-show="open && sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sidebar-submenu"
        >
            @foreach ($employeeMenuCompanies as $companyKey => $companyLabel)
                <a
                    href="{{ route('attendance.summary', ['company' => $companyKey]) }}"
                    class="sidebar-sublink {{ request()->routeIs('attendance.summary') && $activeCompany === $companyKey ? 'sidebar-sublink-active' : '' }}"
                >
                    <span class="sidebar-subicon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20V10" />
                            <path d="M18 20V4" />
                            <path d="M6 20v-6" />
                        </svg>
                    </span>
                    <span>{{ $companyLabel }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <div x-data="{ open: {{ request()->routeIs('site-area.index') ? 'true' : 'false' }} }" class="space-y-2">
        <button
            type="button"
            @click="open = !open"
            class="sidebar-link w-full justify-between {{ request()->routeIs('site-area.index') ? 'sidebar-link-active' : '' }}"
        >
            <span class="flex items-center gap-3">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10Z" />
                        <circle cx="12" cy="11" r="2.5" />
                    </svg>
                </span>
                <span x-show="sidebarOpen" x-transition.opacity>Site Area</span>
            </span>

            <span x-show="sidebarOpen" x-transition.opacity class="sidebar-chevron" :class="{ 'rotate-180': open }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <div
            x-show="open && sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sidebar-submenu"
        >
            @foreach ($employeeMenuCompanies as $companyKey => $companyLabel)
                <a
                    href="{{ route('site-area.index', ['company' => $companyKey]) }}"
                    class="sidebar-sublink {{ request()->routeIs('site-area.index') && $activeCompany === $companyKey ? 'sidebar-sublink-active' : '' }}"
                >
                    <span class="sidebar-subicon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 21s7-4.35 7-10a7 7 0 1 0-14 0c0 5.65 7 10 7 10Z" />
                            <circle cx="12" cy="11" r="2.5" />
                        </svg>
                    </span>
                    <span>{{ $companyLabel }}</span>
                </a>
            @endforeach
        </div>
    </div>

    <a href="#" class="sidebar-link">
        <span class="sidebar-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                <path d="M14 3.5H7A2.5 2.5 0 0 0 4.5 6v12A2.5 2.5 0 0 0 7 20.5h10A2.5 2.5 0 0 0 19.5 18V9Z" />
                <path d="M14 3.5V9h5.5" />
                <path d="M8 13h8" />
                <path d="M8 16.5h5" />
            </svg>
        </span>
        <span x-show="sidebarOpen" x-transition.opacity>Kontrak</span>
    </a>

    @if ($showTargetMenu)
    <div x-data="{ open: {{ request()->routeIs('settings.workday-targets.index') || request()->routeIs('settings.attendance-targets.index') ? 'true' : 'false' }} }" class="space-y-2">
        <button
            type="button"
            @click="open = !open"
            class="sidebar-link w-full justify-between {{ request()->routeIs('settings.workday-targets.index') || request()->routeIs('settings.attendance-targets.index') ? 'sidebar-link-active' : '' }}"
        >
            <span class="flex items-center gap-3">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5h16" />
                        <path d="M8 16V8" />
                        <path d="M12 16V4.5" />
                        <path d="M16 16v-6" />
                    </svg>
                </span>
                <span x-show="sidebarOpen" x-transition.opacity>Target</span>
            </span>

            <span x-show="sidebarOpen" x-transition.opacity class="sidebar-chevron" :class="{ 'rotate-180': open }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <div
            x-show="open && sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sidebar-submenu"
        >
            <a
                href="{{ route('settings.workday-targets.index') }}"
                class="sidebar-sublink {{ request()->routeIs('settings.workday-targets.index') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5h16" />
                        <path d="M8 16V8" />
                        <path d="M12 16V4.5" />
                        <path d="M16 16v-6" />
                    </svg>
                </span>
                <span>Target Workday</span>
            </a>
            <a
                href="{{ route('settings.attendance-targets.index') }}"
                class="sidebar-sublink {{ request()->routeIs('settings.attendance-targets.index') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M6 15.5 10 11.5 13 14.5 18 8.5" />
                        <path d="M18 8.5H14.5" />
                        <path d="M18 8.5v3.5" />
                        <path d="M4.5 19.5h15" />
                    </svg>
                </span>
                <span>Target Attendance</span>
            </a>
        </div>
    </div>
    @endif
    @if ($showSettingMenu)
    <div x-data="{ open: {{ request()->routeIs('settings.users.index') || request()->routeIs('settings.positions.index') ? 'true' : 'false' }} }" class="space-y-2">
        <button
            type="button"
            @click="open = !open"
            class="sidebar-link w-full justify-between {{ request()->routeIs('settings.users.index') || request()->routeIs('settings.positions.index') ? 'sidebar-link-active' : '' }}"
        >
            <span class="flex items-center gap-3">
                <span class="sidebar-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-5 w-5" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M12 3v3" />
                        <path d="M18.36 5.64 16.24 7.76" />
                        <path d="M21 12h-3" />
                        <path d="m18.36 18.36-2.12-2.12" />
                        <path d="M12 21v-3" />
                        <path d="m5.64 18.36 2.12-2.12" />
                        <path d="H3" />
                        <path d="m5.64 5.64 2.12 2.12" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                </span>
                <span x-show="sidebarOpen" x-transition.opacity>Setting</span>
            </span>

            <span x-show="sidebarOpen" x-transition.opacity class="sidebar-chevron" :class="{ 'rotate-180': open }" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m6 9 6 6 6-6" />
                </svg>
            </span>
        </button>

        <div
            x-show="open && sidebarOpen"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 -translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
            class="sidebar-submenu"
        >
            <a
                href="{{ route('settings.users.index') }}"
                class="sidebar-sublink {{ request()->routeIs('settings.users.index') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2" />
                        <circle cx="10" cy="7" r="3" />
                        <path d="M20 8v6" />
                        <path d="M23 11h-6" />
                    </svg>
                </span>
                <span>User</span>
            </a>
            <a
                href="{{ route('settings.positions.index') }}"
                class="sidebar-sublink {{ request()->routeIs('settings.positions.index') ? 'sidebar-sublink-active' : '' }}"
            >
                <span class="sidebar-subicon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" class="h-4 w-4" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M8 7h8" />
                        <path d="M6 12h12" />
                        <path d="M10 17h4" />
                        <rect x="4" y="4" width="16" height="16" rx="2.5" />
                    </svg>
                </span>
                <span>Position User</span>
            </a>
        </div>
    </div>
    @endif
</nav>
