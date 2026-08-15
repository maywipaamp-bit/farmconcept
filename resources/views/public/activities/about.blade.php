@extends('public.activities.layout')

@section('title', 'เกี่ยวกับเรา')

@section('body-class', 'is-about-page')

@section('content')
    <article class="about-page">
        {{-- เฮดข้อความล้วนแบบ hero — ประโยคเดิมที่เคยเป็น .about-lead ยกขึ้นมาเป็นหัวข้อหลัก
             พร้อมไฮไลต์คำว่า "ใกล้ธรรมชาติ" ด้วยสีแบรนด์ ตัดชื่อ "The Farm Concept" ออกเพราะซ้ำกับโลโก้บนแถบบน --}}
        <header class="about-hero">
            <h1 class="about-hero-title">พื้นที่เล็ก ๆ ที่อยากชวนทุกคนกลับมา<span class="about-accent">ใกล้ธรรมชาติ</span> ใกล้อาหาร และใกล้ตัวเองมากขึ้น</h1>
            <p class="about-hero-desc">The Farm Concept เป็นพื้นที่การเรียนรู้ที่เชื่อมโยงอาหาร สุขภาพ ธรรมชาติ และคุณภาพชีวิตเข้าด้วยกัน ผ่านการลงมือทำจริง</p>
        </header>

        {{-- แต่ละหัวข้อนำด้วย eyebrow แบบมีขีด แล้วตามด้วยหัวข้อรองขนาดใหญ่ + คำอธิบาย --}}
        <section class="about-block">
            <span class="about-eyebrow">แนวคิด</span>
            <h2 class="about-block-title">“สุขภาพที่ดี” ไม่ได้เริ่มจากแค่การออกกำลังกายหรืออาหาร</h2>
            <p>แต่เกิดจากวิถีชีวิต การเรียนรู้ การลงมือทำ และการได้ใช้เวลาร่วมกับผู้คนและธรรมชาติรอบตัว</p>
        </section>

        <section class="about-block">
            <span class="about-eyebrow">พื้นที่ของเรา</span>
            <h2 class="about-block-title">มากกว่าสถานที่จัดกิจกรรม คือที่สำหรับเรียนรู้ ทดลอง ลงมือทำ และแบ่งปัน</h2>
            <p>ทั้งเรื่องอาหาร การปลูกและผลิตอาหาร งานสร้างสรรค์ สุขภาพกายและใจ ตลอดจนกิจกรรมสำหรับครอบครัวและชุมชน — ทุกกิจกรรมเข้าถึงง่าย เป็นกันเอง และนำกลับไปใช้ในชีวิตประจำวันได้จริง</p>
        </section>

        {{-- สี่เรื่องที่เราเชื่อมโยงเข้าด้วยกัน — ใช้ eyebrow แทนหัวข้อใหญ่ ตามแบบมาใหม่ --}}
        <section class="about-pillars">
            <span class="about-eyebrow">สิ่งที่เราเชื่อมโยงเข้าด้วยกัน</span>
            <div class="about-pillar-grid">
                <div class="about-pillar">
                    <span class="about-pillar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4v9a4 4 0 0 0 4 4M8 4v13M20 4v6a3 3 0 0 1-3 3h-1v7"/></svg>
                    </span>
                    <span class="about-pillar-name">อาหาร</span>
                    <span class="about-pillar-desc">รู้ที่มา เลือกเป็น ทำเองได้</span>
                </div>
                <div class="about-pillar">
                    <span class="about-pillar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M20.8 6.6a5 5 0 0 0-8.8-1.7 5 5 0 1 0-7.6 6.5L12 20l7.6-8.6a5 5 0 0 0 1.2-4.8Z"/></svg>
                    </span>
                    <span class="about-pillar-name">สุขภาพ</span>
                    <span class="about-pillar-desc">ดูแลทั้งกายและใจ</span>
                </div>
                <div class="about-pillar">
                    <span class="about-pillar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M12 21v-7M12 14c-4 0-7-2.5-7-6.5C5 4 8 3 12 3s7 1 7 4.5c0 4-3 6.5-7 6.5Z"/></svg>
                    </span>
                    <span class="about-pillar-name">ธรรมชาติ</span>
                    <span class="about-pillar-desc">ใกล้ต้นไม้ ดิน และฤดูกาล</span>
                </div>
                <div class="about-pillar">
                    <span class="about-pillar-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9.5" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.9"/></svg>
                    </span>
                    <span class="about-pillar-name">คุณภาพชีวิต</span>
                    <span class="about-pillar-desc">เวลาดี ๆ กับคนรอบตัว</span>
                </div>
            </div>
        </section>

        <section class="about-block">
            <span class="about-eyebrow">ความเชื่อ</span>
            <h2 class="about-block-title">การเปลี่ยนแปลงที่ดี เริ่มจากเรื่องเล็ก ๆ ได้เสมอ</h2>
            <p>ลองปลูกผักสักต้น เลือกอาหารที่ดีขึ้นหนึ่งมื้อ ใช้เวลากับครอบครัว หรือได้เรียนรู้สิ่งใหม่ ๆ — ทุกอย่างอาจเป็นจุดเริ่มต้นของวิถีชีวิตที่ดีขึ้นได้</p>
        </section>

        {{-- ปิดท้ายจัดกลาง — เดิมเป็น blockquote ชิดซ้าย ปรับเป็นสองบรรทัดกลางหน้าให้เด่นขึ้นตามแบบใหม่ --}}
        <footer class="about-closing">
            <p class="about-closing-lead">มาเรียนรู้ ใช้เวลา และเติบโตไปด้วยกัน</p>
            <p class="about-closing-sub">เรียบง่าย เป็นธรรมชาติ และมีความหมาย</p>
        </footer>
    </article>
@endsection
