@extends('public.activities.layout')

@section('title', 'ติดต่อเรา')

@section('body-class', 'is-contact-page')

@section('content')
    {{-- ใช้โครงและสไตล์ชุดเดียวกับหน้าเกี่ยวกับเรา (.about-*) — เนื้อหาย้ายมาจากท้ายหน้านั้น --}}
    <article class="about-page">
        <section class="about-contact">
            <h1 class="about-contact-title">ติดต่อเรา</h1>
            {{-- แถวละไอคอน + ข้อความบรรทัดเดียว ไม่มีป้ายกำกับซ้อน
                 เรียงโทร → อีเมล → ที่อยู่ → เวลาเปิด แถวที่กดแล้วทำอะไรได้ (โทร · อีเมล · เปิดแผนที่) ยังเป็นลิงก์อยู่ --}}
            <div class="about-contact-list">
                <a class="about-contact-item" href="tel:0925399788">
                    <span class="about-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.12.9.36 1.78.7 2.61a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.47-1.27a2 2 0 0 1 2.11-.45c.83.34 1.71.58 2.61.7A2 2 0 0 1 22 16.92z"/></svg></span>
                    <span class="about-contact-text">092-539-9788</span>
                </a>
                <a class="about-contact-item" href="mailto:info@farmconcept.org">
                    <span class="about-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-10 6L2 7"/></svg></span>
                    <span class="about-contact-text">info@farmconcept.org</span>
                </a>
                <a class="about-contact-item" href="https://maps.google.com/?q=1018+ถนนแบริ่ง+แขวงบางนาใต้+เขตบางนา+กรุงเทพฯ" target="_blank" rel="noopener">
                    <span class="about-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg></span>
                    <span class="about-contact-text">1018 ถนนแบริ่ง (สุขุมวิท 107) แขวงบางนาใต้ เขตบางนา กรุงเทพฯ 10270</span>
                </a>
                <div class="about-contact-item">
                    <span class="about-contact-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg></span>
                    <span class="about-contact-text">อังคาร–อาทิตย์ 09:00–18:00 น. · ปิดวันจันทร์</span>
                </div>
            </div>
        </section>
    </article>
@endsection
