@extends('public.activities.layout')

@section('title', 'ลิงก์ยังไม่พร้อมใช้งาน')

@section('content')
    <section class="detail-card qr-unavailable">
        <h1>QR นี้ยังไม่พร้อมใช้งาน</h1>
        <p class="detail-description">
            {{ $activity?->name ? 'กิจกรรม “'.$activity->name.'” ยังไม่เปิดใช้งานขั้นตอนนี้' : 'ไม่สามารถเปิดรายการจาก QR นี้ได้ในขณะนี้' }}
        </p>
        <a class="qr-unavailable-action" href="{{ route('public.activities') }}">ดูกิจกรรมทั้งหมด</a>
    </section>
@endsection
