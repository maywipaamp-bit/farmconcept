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
    {{-- หน้าที่ต้องการ CSS หรือ meta เพิ่มเติมต่อท้ายตรงนี้ — ต้องมาหลัง public-activities.css เสมอ --}}
    @stack('head')
</head>
<body class="@yield('body-class')">
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
            <img class="public-drawer-logo" src="{{ asset('assets/images/logo-farm.png') }}" alt="The Farm Concept">

            {{-- เมนูล้วน ๆ — ช่องทางติดต่อและโซเชียลมีหน้าของตัวเอง ("ติดต่อเรา")
                 ลิ้นชักนี้จะได้ทำหน้าที่เดียวคือพาไปหน้าอื่น ไม่ใช่กล่องรวมลิงก์ --}}
            <div class="public-menu-group">
                <a class="public-menu-link" href="{{ route('public.activities') }}">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><path d="M9 22V12h6v10"/></svg></span>
                    <span class="public-menu-text">หน้าหลัก</span>
                </a>
                <a class="public-menu-link" href="{{ route('public.about') }}">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21v-7M12 14c-4 0-7-2.5-7-6.5C5 4 8 3 12 3s7 1 7 4.5c0 4-3 6.5-7 6.5Z"/></svg></span>
                    <span class="public-menu-text">เกี่ยวกับเรา</span>
                </a>
                <a class="public-menu-link" href="{{ route('public.contact') }}">
                    <span class="public-menu-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.36 1.78.7 2.61a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.47-1.27a2 2 0 0 1 2.11-.45c.83.34 1.71.58 2.61.7A2 2 0 0 1 22 16.92z"/></svg></span>
                    <span class="public-menu-text">ติดต่อเรา</span>
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
@stack('page-script')
</body>
</html>
