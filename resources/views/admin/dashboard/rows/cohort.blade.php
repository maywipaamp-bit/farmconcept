{{-- แถว 3 — กลุ่มตัวอย่าง (โดนัท) | การตอบแบบประเมินสุขภาพ (แท่งรายรอบ)
     ตัวการ์ดจริงแยกไปที่ cards/ เพื่อให้หน้าผลการวิเคราะห์ประกอบเลย์เอาต์ของตัวเองได้
     โดยไม่ต้องลอก markup — ไฟล์นี้เหลือแค่การจัดแถวของแดชบอร์ด --}}
@php
    $cohort = $data['cohort'];
    $survey = $data['survey_rounds'];
@endphp

<div class="dbo-row">
  @include('admin.dashboard.cards.cohort-donut')
  @include('admin.dashboard.cards.survey-rounds')
</div>
