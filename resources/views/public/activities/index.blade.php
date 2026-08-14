@extends('public.activities.layout')

@section('title', 'กิจกรรมทั้งหมด')

{{-- พื้นหลังไล่สีชุดเดียวกับหน้ารายละเอียดและหน้าลงทะเบียน --}}
@section('body-class', 'is-home-page')

@section('search-action')
    <button type="button" class="round-icon-button" id="public-search-button" aria-label="ค้นหากิจกรรม">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
    </button>
@endsection

@section('content')
    <nav class="category-bar" id="category-bar" aria-label="หมวดหมู่กิจกรรม"></nav>

    <div class="page-state" id="page-state" hidden></div>

    <section class="activity-section" id="featured-activities-section" hidden>
        <h2 class="section-heading"><span>★</span>กิจกรรมแนะนำ</h2>
        <div class="promo-carousel" id="featured-activities-carousel"></div>
        <div class="carousel-dots" id="featured-activities-dots"></div>
    </section>

    <section class="activity-section" id="featured-events-section" hidden>
        <h2 class="section-heading"><span>★</span>อีเว้นท์แนะนำ</h2>
        <div class="promo-carousel event-carousel" id="featured-events-carousel"></div>
        <div class="carousel-dots" id="featured-events-dots"></div>
    </section>

    <section class="activity-section" id="other-activities-section" hidden>
        <h2 class="section-heading"><span>★</span>กิจกรรมอื่นๆ</h2>
        <div class="other-carousel" id="other-activities-carousel">
            <div class="other-track" id="other-activities-track"></div>
        </div>
        <div class="carousel-dots" id="other-activities-dots"></div>
    </section>

    <section class="search-screen" id="search-screen" hidden aria-label="ค้นหากิจกรรม">
        <div class="search-head">
            <button type="button" class="round-icon-button" id="search-back" aria-label="กลับ">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m15 18-6-6 6-6"/></svg>
            </button>
            <div class="search-input-wrap">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m20 20-4-4"/></svg>
                <input type="search" id="activity-search" placeholder="ค้นหากิจกรรม" autocomplete="off">
            </div>
        </div>
        <div id="search-results"></div>
    </section>
@endsection

@push('page-data')
<script>
window.TFC_PUBLIC_ACTIVITIES = @json($activities);
window.TFC_PUBLIC_CATEGORIES = @json($categories);
</script>
@endpush
