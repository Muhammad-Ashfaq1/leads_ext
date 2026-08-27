@php
    $size = $size ?? 32;
    $radius = max(6, round($size * 0.25));
    $padding = max(4, round($size * 0.16));
@endphp
<span class="app-brand-logo-custom d-inline-flex align-items-center justify-content-center flex-shrink-0" style="width: {{ $size }}px; height: {{ $size }}px; border-radius: {{ $radius }}px; background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #06b6d4 100%); box-shadow: 0 4px 12px rgba(99, 102, 241, 0.35); padding: {{ $padding }}px;">
    <svg viewBox="0 0 24 24" width="100%" height="100%" fill="none" xmlns="http://www.w3.org/2000/svg" style="display: block;">
        <!-- Precision Vector Compass & V Icon -->
        <path d="M4 4.5L12 21L20 4.5L15.5 4.5L12 13.5L8.5 4.5L4 4.5Z" fill="#FFFFFF"/>
        <!-- Radar Pulse Center Dot -->
        <circle cx="12" cy="7" r="2.2" fill="#38BDF8"/>
        <!-- Vector Reticle Crosshairs -->
        <path d="M12 1.5V3.5M12 10.5V12.5M17.5 7H19.5M4.5 7H6.5" stroke="#E0E7FF" stroke-width="1.4" stroke-linecap="round"/>
    </svg>
</span>

