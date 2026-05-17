<?php

return [
    'name' => env('APP_BRAND_NAME', 'PulseKPI'),
    'tagline' => env('APP_BRAND_TAGLINE', 'Performance Management Platform'),
    'description' => env(
        'APP_BRAND_DESCRIPTION',
        'Platform manajemen performa untuk KPI, assessment, approval, dan pelaporan.'
    ),
    'assets' => [
        'logo_full' => env('APP_BRAND_LOGO_FULL', 'images/branding/pulsekpi-logo-full.png'),
        'logo_mark' => env('APP_BRAND_LOGO_MARK', 'images/branding/pulsekpi-logo-mark.png'),
        'favicon' => env('APP_BRAND_FAVICON', 'favicon.png'),
        'hero_wave' => env('APP_BRAND_HERO_WAVE', 'resources/images/branding/hero-wave.svg'),
    ],
    'colors' => [
        'primary' => env('APP_BRAND_PRIMARY_COLOR', '#0F9D8A'),
        'secondary' => env('APP_BRAND_SECONDARY_COLOR', '#1E4D8C'),
        'accent' => env('APP_BRAND_ACCENT_COLOR', '#F59E0B'),
        'ink' => env('APP_BRAND_INK_COLOR', '#0F172A'),
    ],
    'logo_full' => env('APP_BRAND_LOGO_FULL', 'images/branding/pulsekpi-logo-full.png'),
    'logo_mark' => env('APP_BRAND_LOGO_MARK', 'images/branding/pulsekpi-logo-mark.png'),
    'primary_color' => env('APP_BRAND_PRIMARY_COLOR', '#0F9D8A'),
    'secondary_color' => env('APP_BRAND_SECONDARY_COLOR', '#1E4D8C'),
];
