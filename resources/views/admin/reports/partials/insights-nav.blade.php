{{-- ลิงก์สลับไปมาระหว่างสี่รายงานเชิงบริหาร — เมนูซ้ายพาเข้าได้อยู่แล้ว
     แต่รายงานสี่หน้านี้เป็นชุดคำถามที่มักดูต่อกัน ปุ่มเดียวข้ามหน้าโดยไม่ต้องย้อนไปเมนูซ้าย
     $active คือ 'overview' | 'performance' | 'participants' | 'finance' --}}
@php
    $insightPages = [
        'overview' => ['label' => 'ภาพรวมกิจกรรม', 'route' => 'admin.reports.activities-insights.overview'],
        'performance' => ['label' => 'ประสิทธิภาพกิจกรรม', 'route' => 'admin.reports.activities-insights.performance'],
        'participants' => ['label' => 'ผู้เข้าร่วมและช่องทาง', 'route' => 'admin.reports.activities-insights.participants'],
        'finance' => ['label' => 'การเงิน', 'route' => 'admin.reports.activities-insights.finance'],
    ];
@endphp
<div class="aov-rp-insights-nav">
    @foreach ($insightPages as $key => $page)
        <a class="status-pill{{ $key === $active ? ' is-active' : '' }}" href="{{ route($page['route']) }}">
            {{ $page['label'] }}
        </a>
    @endforeach
</div>
