@extends('layouts.admin')

@section('title', 'ประเมินกิจกรรม')

@section('content')
  <div class="ar-page">
    <nav class="breadcrumb" aria-label="Breadcrumb">
      <a href="/admin/dashboard">แดชบอร์ด</a> <span>/</span>
      <a href="{{ route('admin.activities.index') }}">จัดการกิจกรรม</a> <span>/</span>
      <span class="is-current">ประเมินกิจกรรม</span>
    </nav>

    <div class="ar-head">
      <div class="ar-head-main">
        <h1 class="ar-title">ประเมินกิจกรรม</h1>
        <div class="ar-subline">
          <span class="ar-act-name" id="ar-act-name">{{ $activity['name'] ?? '' }}</span>
          <span class="ar-act-code" id="ar-act-code">{{ $activity['id'] ?? '' }}</span>
          {{-- popover เลือกกิจกรรม — ใช้ component .act-picker ชุดเดียวกับหน้า Check-in --}}
          <div class="act-picker">
            <button type="button" class="act-picker-btn" id="ar-picker-btn" aria-expanded="false" aria-haspopup="listbox">
              <span>เปลี่ยนกิจกรรม</span>
              <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M8 10l4 4 4-4"/></svg>
            </button>
            <div class="act-picker-panel" id="ar-picker-panel" role="listbox" aria-label="เลือกกิจกรรม" hidden></div>
          </div>
        </div>
      </div>
      <button type="button" class="btn btn-outline" id="ar-export">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M12 4v11M8 11.5l4 4 4-4M5 19.5h14"/></svg>
        ส่งออก Excel
      </button>
    </div>

    {{-- แผงสรุป — ทุกตัวเลขคำนวณจากคำตอบชุดเดียวกับตารางด้านล่าง จึงขัดกันเองไม่ได้ --}}
    <div class="ar-summary" id="ar-summary"></div>

    <div class="ar-toolbar">
      <div class="ar-tabs" id="ar-tabs" role="tablist" aria-label="กรองตามช่วงคะแนน"></div>
      <div class="ar-toolbar-right">
        <span class="ar-count" id="ar-count" aria-live="polite"></span>
        <div id="ar-search"></div>
      </div>
    </div>

    {{-- ตารางไม่มีชื่อคน ไม่ใช่ข้อมูลขาด แต่เป็นข้อกำหนดของแบบประเมิน จึงบอกเหตุผลไว้ตรงนี้ --}}
    <div class="ar-note">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 11v5.5M12 7.6v.9"/></svg>
      แบบประเมินหลังกิจกรรมเป็นแบบไม่ระบุตัวตน ระบบจึงแสดงได้เพียงลำดับผู้ตอบ ไม่มีชื่อ เบอร์โทร หรือรหัสลงทะเบียน
    </div>

    <div class="card ar-card">
      <div class="ar-scroll">
        <div class="ar-table" id="ar-table"></div>
      </div>
      <div class="ar-foot" id="ar-pagination"></div>
    </div>
  </div>
@endsection

@push('scripts')
{{-- TFC.exportCsv มาจากไฟล์นี้ — หน้านี้ใช้เฉพาะปุ่มส่งออก --}}
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
<script>
window.TFC_RESPONSES = {
  activities: @json($activityOptions),
  activity: @json($activity),
  summary: @json($summary),
  initial: @json($initial),
  pageSizes: @json($pageSizes),
  dataUrl: @json(route('admin.activities.responses.data')),
  summaryUrl: @json(route('admin.activities.responses.summary')),
  pageUrl: @json(route('admin.activities.responses.index'))
};
</script>
<script src="@assetv('assets/js/activity-response-list.js')"></script>
@endpush
