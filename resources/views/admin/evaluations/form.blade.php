@extends('layouts.admin')

@section('title', $form ? 'แก้ไขแบบประเมิน' : 'สร้างแบบประเมิน')
@section('main-class', 'ec-content')

@section('content')
  <nav class="breadcrumb" aria-label="Breadcrumb">
    <a href="{{ route('admin.dashboard') }}">แดชบอร์ด</a> <span>/</span>
    <a href="{{ route('admin.evaluations.index') }}">แบบประเมิน</a> <span>/</span>
    <span class="is-current">{{ $form ? 'แก้ไขแบบประเมิน' : 'สร้างแบบประเมิน' }}</span>
  </nav>
  <div class="ec-header">
    <a class="ec-back" href="{{ route('admin.evaluations.index') }}" aria-label="ย้อนกลับไปรายการแบบประเมิน">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round"><path d="M14 6l-6 6 6 6"/></svg>
    </a>
    <div class="ec-header-text">
      <h1 class="ec-title">{{ $form ? 'แก้ไขแบบประเมิน' : 'สร้างแบบประเมิน' }}</h1>
    </div>
  </div>

  <div class="alert alert-warning" id="ec-has-responses-notice" style="margin-bottom: 20px;" hidden>
    <span class="alert-icon">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
        <line x1="12" y1="9" x2="12" y2="13"/>
        <line x1="12" y1="17" x2="12.01" y2="17"/>
      </svg>
    </span>
    <div>
      <div class="alert-title">แบบประเมินนี้มีคำตอบแล้ว จึงแก้ไขโครงสร้างเดิมไม่ได้</div>
      <div>ระบบจะเปิดให้แก้ไขและบันทึกทำสำเนาเป็นชุดใหม่ให้อัตโนมัติ โดยไม่กระทบข้อมูลคำตอบเดิม</div>
    </div>
  </div>

  <div class="ec-layout">
    <div class="ec-main">
      <section class="card ec-section">
        <div class="ec-section-head">
          <span class="ec-section-no">1</span>
          <h2 class="ec-section-title">แบบประเมินชุดนี้คืออะไร</h2>
        </div>

        <div class="ec-field">
          <label class="form-label" for="ec-name">ชื่อชุดแบบประเมิน<span class="form-required">*</span></label>
          <input type="text" class="input" id="ec-name" placeholder="เช่น แบบประเมินความพึงพอใจ" autocomplete="off">
        </div>

        <div class="ec-field">
          <label class="form-label" for="ec-desc">คำอธิบายสั้น ๆ</label>
          <input type="text" class="input" id="ec-desc" placeholder="ใช้เมื่อไหร่ ถามอะไร เพื่อให้เจ้าหน้าที่คนอื่นเลือกถูกชุด" autocomplete="off">
        </div>

        <div class="ec-field">
          <label class="form-label">ใช้ตอนไหน<span class="form-required">*</span></label>
          <div class="ec-picks" id="ec-stages"></div>
        </div>
      </section>

      <section class="card ec-section" id="ec-registration-section" hidden>
        <div class="ec-section-head ec-section-head-split">
          <div class="ec-section-head">
            <span class="ec-section-no">2</span>
            <div class="ec-section-heading"><h2 class="ec-section-title">ฟิลด์ลงทะเบียน</h2></div>
          </div>
          <span class="ec-summary" id="ec-registration-summary"></span>
        </div>

        <div class="ec-booking-config">
          <div class="ec-booking-config-head">
            <div>
              <h3>จำนวนผู้เข้าร่วมต่อการลงทะเบียน</h3>
              <p>ใช้เบอร์โทรศัพท์ชุดเดียวเพื่อยืนยันการจองและค้นหาตอนเช็กอิน จากนั้นเลือกเช็กอินแยกรายชื่อ</p>
            </div>
          </div>
          <div class="ec-booking-selects">
            <div class="ec-booking-field">
              <label class="form-label" for="ec-booking-mode">รูปแบบการลงทะเบียน<span class="form-required">*</span></label>
              <select class="select" id="ec-booking-mode">
                <option value="single">1 คนเท่านั้น</option>
                <option value="group">จองแทนได้หลายคน</option>
              </select>
            </div>
            <div class="ec-booking-field" id="ec-booking-max" hidden>
              <label class="form-label" for="ec-booking-max-seats">จำนวนสูงสุดต่อการจอง<span class="form-required">*</span></label>
              <select class="select" id="ec-booking-max-seats">
                <option value="2">สูงสุด 2 ที่นั่ง</option>
                <option value="3">สูงสุด 3 ที่นั่ง</option>
                <option value="4">สูงสุด 4 ที่นั่ง</option>
                <option value="5">สูงสุด 5 ที่นั่ง</option>
              </select>
            </div>
          </div>
        </div>
        <div class="ec-registration-fields" id="ec-registration-fields"></div>
      </section>

      <section class="card ec-section">
        <div class="ec-section-head ec-section-head-split">
          <div class="ec-section-head">
            <span class="ec-section-no" id="ec-questions-section-no">2</span>
            <div class="ec-section-heading">
              <h2 class="ec-section-title" id="ec-questions-title">คำถาม</h2>
              <span class="ec-note" id="ec-questions-note" hidden>เพิ่มคำถามเฉพาะที่ไม่มีอยู่ในฟิลด์ระบบ โดยจะแสดงต่อท้ายข้อมูลลงทะเบียน</span>
            </div>
          </div>
          <span class="ec-summary" id="ec-summary"></span>
        </div>
        <div class="ec-items" id="ec-items"></div>
      </section>
    </div>

    <aside class="ec-side">
      <div class="card ec-preview-card">
        <div class="ec-preview-head">
          <span class="ec-panel-title">ตัวอย่างที่ผู้ตอบเห็น</span>
          <span class="ec-live">อัปเดตสด</span>
        </div>
        <div class="ec-preview-body">
          <div class="ec-preview-intro" id="ec-preview-intro">
            <span class="ec-preview-name" id="ec-preview-name">ชื่อชุดแบบประเมิน</span>
            <span class="ec-preview-desc" id="ec-preview-desc">คำอธิบายจะแสดงตรงนี้</span>
          </div>
          <div class="ec-preview-list" id="ec-preview-list"></div>
          <button type="button" class="btn btn-primary btn-block btn-sm ec-preview-submit" id="ec-preview-submit" disabled>ส่งแบบประเมิน</button>
        </div>
      </div>

      <div class="card ec-panel">
        <div class="ec-preview-head">
          <span class="ec-panel-title">ก่อนบันทึก</span>
          <span class="ec-done-badge" id="ec-done-badge">0/5</span>
        </div>
        <div class="ec-checklist" id="ec-checklist"></div>
      </div>
    </aside>
  </div>
@endsection

@section('after-content')
<div class="ec-bottombar">
  <div class="ec-bottombar-inner">
    <span class="ec-progress-text" id="ec-progress-text"></span>
    <div class="ec-bottombar-actions">
      <a class="btn btn-ghost" href="{{ route('admin.evaluations.index') }}" id="ec-cancel">ยกเลิก</a>
      <button type="button" class="btn btn-outline" id="ec-duplicate-btn" hidden>ทำสำเนาเป็นชุดใหม่</button>
      <button type="button" class="btn btn-outline" id="ec-save-draft">บันทึกร่าง</button>
      <button type="button" class="btn btn-primary" id="ec-save" disabled>บันทึกและเปิดใช้งาน</button>
    </div>
  </div>
</div>
@endsection

@push('page-script')
<script>
window.TFC_EVALUATION_FORM = @json($formPayload);
window.TFC_EVALUATION_INDEX_URL = @json(route('admin.evaluations.index'));
</script>
<script src="@assetv('assets/js/evaluation-create.js')"></script>
@endpush
