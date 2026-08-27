{{-- Navbar Light / Dark / System mode switcher matching POS --}}
@php
    $wrapperTag = $wrapperTag ?? 'li';
    $wrapperClass = $wrapperClass ?? 'nav-item dropdown';
    $themeTriggerClass = $themeTriggerClass ?? 'nav-link dropdown-toggle hide-arrow';
    $themeIconClass = $themeIconClass ?? 'tabler-sun icon-base ti icon-md theme-icon-active';
@endphp
<{{ $wrapperTag }} class="{{ $wrapperClass }}">
    <a class="{{ $themeTriggerClass }}" id="nav-theme" href="javascript:void(0);"
        data-bs-toggle="dropdown" aria-label="Theme: light" aria-expanded="false">
        <i class="{{ $themeIconClass }}"></i>
    </a>
    <span class="d-none" id="nav-theme-text">Theme</span>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="nav-theme-text">
        <li>
            <button type="button" class="dropdown-item align-items-center waves-effect active"
                data-bs-theme-value="light" aria-pressed="true">
                <span><i class="icon-base ti tabler-sun icon-22px me-3" data-icon="sun"></i>Light</span>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item align-items-center waves-effect"
                data-bs-theme-value="dark" aria-pressed="false">
                <span><i class="icon-base ti tabler-moon-stars icon-22px me-3" data-icon="moon-stars"></i>Dark</span>
            </button>
        </li>
        <li>
            <button type="button" class="dropdown-item align-items-center waves-effect"
                data-bs-theme-value="system" aria-pressed="false">
                <span><i class="icon-base ti tabler-device-desktop-analytics icon-22px me-3" data-icon="device-desktop-analytics"></i>System</span>
            </button>
        </li>
    </ul>
</{{ $wrapperTag }}>

