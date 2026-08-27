@php
    $authUser = auth()->user();
    $isSuperAdmin = $authUser?->isSuperAdmin();
    $logoutLabel = $logoutLabel ?? 'Sign out';
    $iconClass = $iconClass ?? 'icon-base ti';
@endphp
<li>
    <a href="{{ route('profile.index') }}"
       class="dropdown-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
        <i class="{{ $iconClass }} tabler-user me-2"></i>
        Profile
    </a>
</li>
<li>
    <a href="{{ route('settings.index') }}"
       class="dropdown-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
        <i class="{{ $iconClass }} tabler-settings-cog me-2"></i>
        Extractor Settings
    </a>
</li>
@if ($isSuperAdmin)
    <li>
        <a href="{{ route('tenants.index') }}"
           class="dropdown-item {{ request()->routeIs('tenants.*') ? 'active' : '' }}">
            <i class="{{ $iconClass }} tabler-building me-2"></i>
            Manage Tenants
        </a>
    </li>
@endif
<li>
    <hr class="dropdown-divider">
</li>
<li>
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="dropdown-item text-danger">
            <i class="{{ $iconClass }} tabler-logout me-2"></i>
            {{ $logoutLabel }}
        </button>
    </form>
</li>

