@extends('public.activities.layout')

@section('title', $activity['title'])

@section('content')
    <article class="detail-card">
        <div class="detail-hero{{ $activity['image'] ? '' : ' has-fallback' }}" @if($activity['image']) style="background-image:url('{{ $activity['image'] }}')" @endif>
            <a class="detail-back-overlay" href="{{ route('public.activities') }}" aria-label="กลับไปหน้ากิจกรรม">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="m15 18-6-6 6-6"/></svg>
                <span>กลับ</span>
            </a>
            <span class="activity-badge detail-category-badge">{{ strtoupper($activity['category'] ?: $activity['type']) }}</span>
        </div>
        <div class="detail-body">
            <h1>{{ $activity['title'] }}</h1>

            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><rect x="3" y="4" width="18" height="17" rx="2"/><path d="M8 2v4M16 2v4M3 10h18"/></svg>
                <span>{{ $activity['scheduleLabel'] ?: '-' }}</span>
            </div>
            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><path d="M20 10c0 5-8 11-8 11S4 15 4 10a8 8 0 1 1 16 0z"/><circle cx="12" cy="10" r="3"/></svg>
                <span>{{ $activity['location'] }}</span>
            </div>
            <div class="detail-meta">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3-7 8-7s8 3 8 7"/></svg>
                <span>{{ $activity['speaker'] }}</span>
            </div>
            <div class="detail-meta detail-price">
                <span class="baht-icon">฿</span>
                <span>{{ $activity['priceLabel'] }}</span>
            </div>

            @if($activity['description'])
                <p class="detail-description">{{ $activity['description'] }}</p>
            @endif

            @if($activity['registrationDeadlineLabel'])
                <div class="deadline-note">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                    <span>{{ $activity['registrationDeadlineLabel'] }}</span>
                </div>
            @endif
        </div>
    </article>
@endsection
