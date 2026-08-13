<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'กิจกรรมทั้งหมด') | The Farm Concept</title>
    <link rel="icon" type="image/png" href="{{ asset('assets/images/favicon.png') }}">
    <link rel="manifest" href="{{ asset('manifest.json') }}">
    <meta name="theme-color" content="#81C060">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Noto+Sans+Thai:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('assets/css/public-activities.css') }}">
</head>
<body>
<div class="public-app">
    <header class="public-topbar">
        <button type="button" class="round-icon-button" id="public-menu-button" aria-label="เมนู" aria-expanded="false">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </button>
        <a class="public-logo-link" href="{{ route('public.activities') }}" aria-label="กิจกรรม The Farm Concept">
            <img src="{{ asset('assets/images/logo-farm.png') }}" alt="The Farm Concept">
        </a>
        @hasSection('search-action')
            @yield('search-action')
        @else
            <a class="round-icon-button" href="{{ route('public.activities', ['search' => 1]) }}" aria-label="ค้นหากิจกรรม">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
            </a>
        @endif
    </header>

    <aside class="public-drawer-frame" id="public-drawer-frame" aria-hidden="true">
        <button type="button" class="public-drawer-overlay" id="public-drawer-overlay" aria-label="ปิดเมนู"></button>
        <nav class="public-drawer" aria-label="เมนูหลัก">
            <button type="button" class="public-drawer-close" id="public-drawer-close" aria-label="ปิดเมนู">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
            <p class="public-drawer-heading">เมนู</p>

            <p class="public-menu-group-label">ทางลัด</p>
            <div class="public-menu-group">
                <a class="public-menu-link" href="{{ route('public.activities') }}">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg></span>
                    <span class="public-menu-text">หน้าหลัก</span>
                </a>
            </div>

            <p class="public-menu-group-label">โซเชียลมีเดีย</p>
            <div class="public-menu-group">
                <a class="public-menu-link" href="https://facebook.com/thefarmconcept" target="_blank" rel="noopener">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"/></svg></span>
                    <span class="public-menu-text">Facebook</span>
                </a>
                <a class="public-menu-link" href="https://instagram.com/thefarmconcept" target="_blank" rel="noopener">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="20" height="20" rx="5"/><path d="M16 11.37A4 4 0 1 1 12.63 8 4 4 0 0 1 16 11.37z"/><path d="M17.5 6.5h.01"/></svg></span>
                    <span class="public-menu-text">Instagram</span>
                </a>
                <a class="public-menu-link" href="https://tiktok.com/@thefarmconcept" target="_blank" rel="noopener">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M9 3v12.5a3.5 3.5 0 1 1-3-3.46"/><path d="M9 3a5 5 0 0 0 5 5"/></svg></span>
                    <span class="public-menu-text">TikTok</span>
                </a>
            </div>

            <p class="public-menu-group-label">ติดต่อโดยตรง</p>
            <div class="public-menu-group">
                <a class="public-menu-link" href="https://line.me/R/ti/p/@thefarmconcept" target="_blank" rel="noopener">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 11.5a8.38 8.38 0 0 1-.9 3.8 8.5 8.5 0 0 1-7.6 4.7 8.38 8.38 0 0 1-3.8-.9L3 21l1.9-5.7a8.38 8.38 0 0 1-.9-3.8 8.5 8.5 0 0 1 4.7-7.6 8.38 8.38 0 0 1 3.8-.9h.5a8.48 8.48 0 0 1 8 8z"/></svg></span>
                    <span class="public-menu-text">LINE OA</span>
                </a>
                <a class="public-menu-link" href="tel:0652564455">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.36 1.78.7 2.61a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.47-1.27a2 2 0 0 1 2.11-.45c.83.34 1.71.58 2.61.7A2 2 0 0 1 22 16.92z"/></svg></span>
                    <span class="public-menu-text">Tel 065 256 4455</span>
                </a>
                <a class="public-menu-link" href="https://maps.google.com/?q=The+Farm+Concept" target="_blank" rel="noopener">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <span class="public-menu-text">Google Map</span>
                </a>
            </div>
        </nav>
    </aside>

    <main>
        @yield('content')
    </main>

    <footer class="public-footer">TheFarmConcept © 2026</footer>
</div>

@stack('page-data')
<script src="{{ asset('assets/js/public-activities.js') }}" defer></script>
</body>
</html>
