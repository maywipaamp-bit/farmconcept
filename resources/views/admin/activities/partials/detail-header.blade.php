{{-- หัวหน้ารายละเอียดกิจกรรม + แท็บนำทาง — ใช้ร่วมกันทุกแท็บของหน้ารายละเอียด
     ต้องส่ง $activity (พร้อม registrations_count / checked_in_count / responses_count)
     และ $activeTab ('overview' | 'participants' | 'checkins' | 'evaluations' | 'reports') --}}
<nav class="breadcrumb" aria-label="Breadcrumb">
  <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
  <a href="{{ route('admin.activities.index') }}">จัดการกิจกรรม</a> <span>/</span>
  <span class="is-current">{{ $activity->name }}</span>
</nav>

<div class="aov-header">
  <div class="aov-header-text">
    <h1 class="aov-title">{{ $activity->name }}</h1>
  </div>
  <a class="btn btn-outline" href="{{ route('admin.activities.edit', $activity->code) }}">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 20h9M16.5 3.5a2.1 2.1 0 013 3L7 19l-4 1 1-4 12.5-12.5z"/></svg>
    แก้ไขกิจกรรม
  </a>
</div>

{{-- แท็บนำทางของกิจกรรมเดียวกัน — ภาพรวมและผู้เข้าร่วมอยู่ในหน้ารายละเอียด
     ที่เหลือลิงก์ไปหน้าเดิมของโมดูล --}}
<nav class="aov-tabs" aria-label="เมนูกิจกรรม">
  @if (($activeTab ?? '') === 'overview')
    <span class="aov-tab is-active" aria-current="page">ภาพรวม</span>
  @else
    <a class="aov-tab" href="{{ route('admin.activities.show', $activity->code) }}">ภาพรวม</a>
  @endif

  @if (($activeTab ?? '') === 'participants')
    <span class="aov-tab is-active" aria-current="page">
      ลงทะเบียน <span class="aov-tab-count">{{ $activity->registrations_count }}</span>
    </span>
  @else
    <a class="aov-tab" href="{{ route('admin.activities.participants', $activity->code) }}">
      ลงทะเบียน <span class="aov-tab-count">{{ $activity->registrations_count }}</span>
    </a>
  @endif

  @if (($activeTab ?? '') === 'checkins')
    <span class="aov-tab is-active" aria-current="page">
      Check-in <span class="aov-tab-count">{{ $activity->checked_in_count }}</span>
    </span>
  @else
    <a class="aov-tab" href="{{ route('admin.activities.checkins', $activity->code) }}">
      Check-in <span class="aov-tab-count">{{ $activity->checked_in_count }}</span>
    </a>
  @endif
  @if (($activeTab ?? '') === 'evaluations')
    <span class="aov-tab is-active" aria-current="page">
      แบบประเมิน <span class="aov-tab-count">{{ $activity->responses_count }}</span>
    </span>
  @else
    <a class="aov-tab" href="{{ route('admin.activities.evaluations', $activity->code) }}">
      แบบประเมิน <span class="aov-tab-count">{{ $activity->responses_count }}</span>
    </a>
  @endif
  @if (($activeTab ?? '') === 'reports')
    <span class="aov-tab is-active" aria-current="page">รายงาน</span>
  @else
    <a class="aov-tab" href="{{ route('admin.activities.reports', $activity->code) }}">รายงาน</a>
  @endif
</nav>
