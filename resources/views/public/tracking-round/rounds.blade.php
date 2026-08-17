@extends('public.activities.layout')

@section('title', 'รอบแบบประเมิน')

@section('content')
    <section class="detail-card tr-card">
        @includeWhen($proxyFor, 'public.tracking-round.partials.proxy-banner', ['proxyFor' => $proxyFor])

        <h1 class="tr-login-title">รอบแบบประเมิน</h1>
        <p class="tr-subheading">
            ทำได้ทีละรอบตามลำดับ · {{ $rounds->whereNotNull('answered_at')->count() }}/{{ $rounds->count() }} รอบ
        </p>

        @include('public.tracking-round.partials.timeline')

        <p class="tr-note">
            <a href="{{ route('public.tracking-round-qr.dashboard') }}">กลับหน้าหลัก</a>
        </p>
    </section>
@endsection
