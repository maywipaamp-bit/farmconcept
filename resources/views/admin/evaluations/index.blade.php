@extends('layouts.admin')

@section('title', 'แบบประเมิน')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <span class="is-current">แบบประเมิน</span>
  </nav>
  <div class="page-header" id="el-page-header"></div>

  <div class="el-filter-bar">
    <div class="el-tabs" id="el-tabs" role="tablist"></div>
    <span class="el-count" id="el-count"></span>
  </div>

  <div class="card el-table-card">
    <div class="el-table-scroll">
      <div class="el-table">
        <div class="el-tr el-th">
          <div>รหัส</div>
          <div>ชื่อชุดแบบประเมิน</div>
          <div>ใช้ตอนไหน</div>
          <div class="text-right">คำถาม</div>
          <div class="text-right">คำตอบ</div>
          <div>แก้ไขล่าสุด</div>
          <div></div>
        </div>
        <div id="el-rows"></div>
      </div>
    </div>
    <div class="el-foot">
      <div id="el-pagination"></div>
    </div>
  </div>
@endsection

@section('modals')
<div class="el-preview" id="el-preview" hidden>
  <div class="el-preview-inner">
    <div class="el-preview-bar">
      <span class="el-preview-chip" id="el-preview-chip"></span>
      <button type="button" class="el-preview-close" id="el-preview-close" aria-label="ปิดตัวอย่าง">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M18 6L6 18M6 6l12 12"/></svg>
      </button>
    </div>

    <div class="el-phone">
      <div class="el-phone-screen">
        <div class="el-phone-status">
          <span id="el-phone-time">09:41</span>
          <span class="el-phone-icons">
            <svg width="15" height="11" viewBox="0 0 18 12" fill="currentColor" aria-hidden="true"><rect x="0" y="8" width="3" height="4" rx="1"/><rect x="5" y="5" width="3" height="7" rx="1"/><rect x="10" y="2" width="3" height="10" rx="1"/><rect x="15" y="0" width="3" height="12" rx="1" opacity="0.35"/></svg>
            <svg width="18" height="11" viewBox="0 0 24 12" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true"><rect x="1" y="1" width="18" height="10" rx="3"/><rect x="3" y="3" width="12" height="6" rx="1.5" fill="currentColor" stroke="none"/><path d="M21.5 4.5v3"/></svg>
          </span>
        </div>
        <div class="el-phone-head">
          <span class="el-phone-title" id="el-phone-title"></span>
          <span class="el-phone-sub" id="el-phone-sub"></span>
        </div>
        <div class="el-phone-body" id="el-phone-body"></div>
        <div class="el-phone-foot">
          <button type="button" class="el-phone-submit">ส่งแบบประเมิน</button>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
window.TFC_EVALUATION_FORMS = @json($forms);
window.TFC_EVALUATION_CREATE_URL = @json(route('admin.evaluations.create'));
</script>
<script src="@assetv('assets/js/evaluation-list.js')"></script>
@endpush
