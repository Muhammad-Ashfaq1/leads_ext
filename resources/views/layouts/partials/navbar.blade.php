@php
    $authUser = Auth::user();
    $isSuperAdmin = $authUser?->isSuperAdmin();
    $contextLabel = $isSuperAdmin ? 'Super Admin Console' : ($authUser?->tenant?->name ?? 'Leads Engine');
    $contextSub = $isSuperAdmin ? 'Global SaaS Management' : (strtoupper($authUser?->tenant?->plan ?? 'STARTER').' Workspace');
@endphp

<nav class="layout-navbar container-xxl navbar-detached navbar navbar-expand-xl align-items-center bg-navbar-theme pos-navbar pos-tone-primary" id="layout-navbar">
    <div class="layout-menu-toggle navbar-nav align-items-xl-center me-3 me-xl-0 d-xl-none">
        <a class="nav-item nav-link px-0 me-xl-4" href="javascript:void(0)" aria-label="Toggle menu">
            <i class="icon-base ti tabler-menu-2 icon-md" aria-hidden="true"></i>
        </a>
    </div>

    <div class="navbar-nav-right d-flex align-items-center justify-content-between w-100" id="navbar-collapse">
        <!-- Left: Brand / Organization Name Treatment (Matching POS) -->
        <div class="pos-navbar-brand">
            <span class="pos-navbar-org-name" title="{{ $contextLabel }}">{{ $contextLabel }}</span>
            <small class="pos-navbar-subtitle text-muted">{{ $contextSub }}</small>
        </div>

        <!-- Right: Actions, Theme Switcher & Account Dropdown -->
        <ul class="navbar-nav flex-row align-items-center ms-auto gap-3">
            <li class="nav-item d-none d-md-block">
                <a href="{{ route('extractor.index') }}" class="btn btn-sm btn-primary">
                    <i class="icon-base ti tabler-plus me-1"></i> New Extraction
                </a>
            </li>

            @include('layouts.partials.theme-switcher')

            <li class="nav-item dropdown pos-navbar-account">
                <a class="nav-link dropdown-toggle hide-arrow p-0 pos-navbar-avatar-trigger" href="javascript:void(0);" data-bs-toggle="dropdown" aria-label="Account menu" aria-expanded="false">
                    <div class="avatar avatar-online pos-navbar-avatar">
                        <span class="avatar-initial rounded-circle bg-label-primary">
                            {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                        </span>
                    </div>
                </a>
                <ul class="dropdown-menu dropdown-menu-end pos-navbar-dropdown">
                    <li>
                        <div class="pos-navbar-dropdown-head">
                            <div class="avatar avatar-online pos-navbar-avatar pos-navbar-avatar--lg">
                                <span class="avatar-initial rounded-circle bg-label-primary">
                                    {{ strtoupper(substr($authUser->name ?? 'U', 0, 1)) }}
                                </span>
                            </div>
                            <div class="pos-navbar-dropdown-meta min-w-0">
                                <div class="pos-navbar-dropdown-name text-truncate">{{ $authUser->name }}</div>
                                <small class="pos-navbar-dropdown-email text-truncate">{{ $authUser->email }}</small>
                                <div class="mt-1">
                                    <span class="badge {{ $authUser->getRoleBadgeClass() }}" style="font-size: 0.65rem;">
                                        {{ $authUser->getRoleLabel() }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </li>
                    @unless ($isSuperAdmin)
                        <li>
                            <hr class="dropdown-divider">
                        </li>
                        <li>
                            <div class="pos-navbar-dropdown-org px-3 py-1">
                                <span class="pos-navbar-org-name" title="{{ $contextLabel }}">{{ $contextLabel }}</span>
                            </div>
                        </li>
                    @endunless
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    @include('layouts.partials.account-menu-items', [
                        'logoutLabel' => 'Sign out',
                        'iconClass' => 'icon-base ti',
                    ])
                </ul>
            </li>
        </ul>
    </div>
</nav>

