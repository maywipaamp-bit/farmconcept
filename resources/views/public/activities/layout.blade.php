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
        <span class="round-icon-button" aria-hidden="true">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
        </span>
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

    <main>
        @yield('content')
    </main>

    <footer class="public-footer">TheFarmConcept © 2026</footer>
</div>

@stack('page-data')
<script src="{{ asset('assets/js/public-activities.js') }}" defer></script>
</body>
</html>
