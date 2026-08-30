@php
    $authUser = auth()->user();
    $isSuperAdmin = $authUser?->isSuperAdmin();
    $homeRoute = 'dashboard';
@endphp

@once
    <style>
        #layout-menu .menu-link {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        #layout-menu .menu-link .menu-icon {
            flex: 0 0 1.375rem;
        }

        #layout-menu .menu-sub > .menu-item > .menu-link::before {
            display: none;
        }

        #layout-menu .menu-sub .menu-link {
            padding-inline-start: 1rem;
        }

        #layout-menu .menu-sub .menu-icon {
            opacity: 0.9;
        }

        #layout-menu .menu-inner.menu-layout-column {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        #layout-menu .menu-item-settings-bottom {
            margin-top: auto;
        }

        #layout-menu .menu-copyright {
            pointer-events: none;
        }

        #layout-menu .menu-copyright .menu-link {
            cursor: default;
            padding-top: 0.25rem;
            padding-bottom: 1rem;
            color: var(--bs-secondary-color);
            font-size: 0.8125rem;
        }

        #layout-menu .menu-copyright .menu-link:hover {
            background: transparent !important;
            color: var(--bs-secondary-color) !important;
        }
    </style>
@endonce

<aside id="layout-menu" class="layout-menu menu-vertical menu bg-menu-theme">
    <div class="app-brand demo">
        <a href="{{ route($homeRoute) }}" class="app-brand-link text-decoration-none d-flex align-items-center gap-2">
            @include('layouts.partials.brand-logo', ['size' => 32])
            <span class="app-brand-text demo menu-text fw-bold fs-5 text-heading">Vektor<span class="text-primary">Leads</span></span>
        </a>

        <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large ms-auto">
            <i class="icon-base ti menu-toggle-icon d-none d-xl-block"></i>
            <i class="icon-base ti tabler-x d-block d-xl-none"></i>
        </a>
    </div>

    <div class="menu-inner-shadow"></div>

    <ul class="menu-inner py-1 menu-layout-column">
        <!-- Lead Generation Section -->
        <li class="menu-header small text-uppercase">
            <span class="menu-header-text">Lead Generation</span>
        </li>

        <li class="menu-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
            <a href="{{ route('dashboard') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-smart-home"></i>
                <div data-i18n="Dashboard">Dashboard</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('extractor.*') ? 'active' : '' }}">
            <a href="{{ route('extractor.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-map-pin-search"></i>
                <div data-i18n="Lead Finder">Lead Finder</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('leads.*') ? 'active' : '' }}">
            <a href="{{ route('leads.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-users-group"></i>
                <div data-i18n="Prospects Directory">Prospects Directory</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('email-templates.*') ? 'active' : '' }}">
            <a href="{{ route('email-templates.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-template"></i>
                <div data-i18n="Email Templates">Email Templates</div>
            </a>
        </li>

        <li class="menu-item {{ request()->routeIs('jobs.*') ? 'active' : '' }}">
            <a href="{{ route('jobs.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-history"></i>
                <div data-i18n="Search Campaigns">Search Campaigns</div>
            </a>
        </li>

        @if ($isSuperAdmin)
            <!-- Super Admin Section -->
            <li class="menu-header small text-uppercase mt-3">
                <span class="menu-header-text">Platform Admin</span>
            </li>

            <li class="menu-item {{ request()->routeIs('tenants.*') ? 'active' : '' }}">
                <a href="{{ route('tenants.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-building"></i>
                    <div data-i18n="Workspaces & Accounts">Workspaces &amp; Accounts</div>
                </a>
            </li>

            <li class="menu-item {{ request()->routeIs('plans.*') ? 'active' : '' }}">
                <a href="{{ route('plans.index') }}" class="menu-link">
                    <i class="menu-icon icon-base ti tabler-packages"></i>
                    <div data-i18n="Subscription Plans">Subscription Plans</div>
                </a>
            </li>
        @endif

        <!-- Bottom Settings & Copyright (Matching POS) -->
        <li class="menu-item menu-item-settings-bottom {{ request()->routeIs('settings.*') ? 'active' : '' }}">
            <a href="{{ route('settings.index') }}" class="menu-link">
                <i class="menu-icon icon-base ti tabler-settings-cog"></i>
                <div data-i18n="Settings">Settings</div>
            </a>
        </li>

        <li class="menu-item menu-copyright">
            <div class="menu-link">
                <div>&copy; {{ date('Y') }} VektorLeads</div>
            </div>
        </li>
    </ul>
</aside>
<div class="menu-mobile-toggler d-xl-none rounded-1">
    <a href="javascript:void(0);" class="layout-menu-toggle menu-link text-large text-bg-secondary p-2 rounded-1">
        <i class="ti tabler-menu icon-base"></i>
        <i class="ti tabler-chevron-right icon-base"></i>
    </a>
</div>

