@extends('layouts.admin')

@section('title', $isCreate ? 'เพิ่มกิจกรรม' : 'แก้ไขกิจกรรม')
@section('main-class', 'ac-content')

@section('content')
      <nav class="breadcrumb" aria-label="Breadcrumb">
        <a href="/admin/dashboard.html">แดชบอร์ด</a> <span>/</span>
        <a href="{{ route('admin.activities.index') }}">จัดการกิจกรรม</a> <span>/</span>
        <span class="is-current">{{ $isCreate ? 'เพิ่มกิจกรรม' : 'แก้ไขกิจกรรม' }}</span>
      </nav>
      <div class="page-header">
        <div class="page-header-text">
          <h1>{{ $isCreate ? 'เพิ่มกิจกรรม' : 'แก้ไขกิจกรรม' }}</h1>
        </div>
        <div class="page-header-actions">
          <button type="button" class="btn btn-outline btn-sm" id="ac-side-toggle" aria-expanded="true" aria-controls="ac-side">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
              <rect x="3" y="4" width="18" height="16" rx="2"/><path d="M15 4v16"/>
            </svg>
            <span id="ac-side-toggle-text">ซ่อนแถบขวา</span>
          </button>
        </div>
      </div>

      {{-- อ่านสถานะพับก่อนเบราว์เซอร์วาดเฟรมแรก ไม่งั้นแถบขวาจะโผล่แล้วหายทุกครั้งที่เปิดหน้า
           วิธีเดียวกับที่ layout ใช้กับแถบเมนูซ้าย --}}
      <div class="ac-layout">
        <script>(function(){try{if(localStorage.getItem('tfc-ac-side-hidden')==='1'){document.currentScript.parentElement.classList.add('is-side-hidden');}}catch(e){}})();</script>
        <!-- ==================== คอลัมน์ฟอร์ม ==================== -->
        <form class="ac-form" id="ac-form" novalidate>

          <!-- ---------- 1. กิจกรรมนี้คืออะไร ---------- -->
          <section class="card ac-section">
            <div class="ac-section-head">
              <span class="ac-section-no">1</span>
              <h2 class="ac-section-title">กิจกรรมนี้คืออะไร</h2>
            </div>

            <div class="ac-field">
              <div class="ac-label-row">
                <label class="form-label" for="ac-title">ชื่อกิจกรรม<span class="form-required">*</span></label>
                <span class="ac-counter" id="ac-title-count">0/120</span>
              </div>
              <input type="text" class="input" id="ac-title" maxlength="120" placeholder="ตั้งชื่อให้เห็นภาพว่าผู้เข้าร่วมจะได้ทำอะไร" autocomplete="off">
            </div>

            {{-- สามช่องนี้เป็นการ "จัดประเภทกิจกรรม" ชุดเดียวกัน จึงอยู่แถวเดียวกัน
                 หมวดหมู่กับกลุ่มเป้าหมายเลือกได้หลายค่า จึงใช้ combobox แบบเดียวกับวิทยากร
                 ของเดิมเป็นชิปเรียงกัน ซึ่งกินความกว้างตามจำนวนตัวเลือกและคุมความสูงไม่ได้ --}}
            <div class="ac-field-row ac-field-row-classify">
              <div class="ac-field">
                <label class="form-label">ประเภท<span class="form-required">*</span></label>
                <div class="ac-combo" id="ac-kind-combo">
                  <div class="ac-combo-control" id="ac-kind-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox" aria-label="ประเภทกิจกรรม">
                    <span class="ac-combo-values" id="ac-kind-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-kind-panel" role="listbox" hidden></div>
                </div>
              </div>

              <div class="ac-field">
                <label class="form-label">หมวดหมู่<span class="form-required">*</span></label>
                <div class="ac-combo" id="ac-cat-combo">
                  <div class="ac-combo-control" id="ac-cat-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox">
                    <span class="ac-combo-values" id="ac-cat-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-cat-panel" role="listbox" hidden></div>
                </div>
              </div>

              <div class="ac-field">
                <div class="ac-label-row">
                  <label class="form-label">กลุ่มเป้าหมาย<span class="form-required">*</span></label>
                  <span class="ac-hint">ใช้ทำรายงาน</span>
                </div>
                <div class="ac-combo" id="ac-target-combo">
                  <div class="ac-combo-control" id="ac-target-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox">
                    <span class="ac-combo-values" id="ac-target-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-target-panel" role="listbox" hidden></div>
                </div>
              </div>
            </div>
            <!-- อีเวนท์หนึ่งมีได้หลายกิจกรรม — ช่องนี้ซ่อนเมื่อประเภทเป็นอีเวนท์ เพราะอีเวนท์ซ้อนอีเวนท์ไม่ได้ -->
            <div class="ac-field-row" id="ac-field-parent-event" hidden>
              <div class="ac-field ac-field-half">
                <div class="ac-label-row">
                  <label class="form-label">อยู่ในอีเวนท์</label>
                  <span class="ac-hint">เว้นว่างได้ ถ้าเป็นกิจกรรมเดี่ยว</span>
                </div>
                <div class="ac-combo" id="ac-event-combo">
                  <div class="ac-combo-control" id="ac-event-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox">
                    <span class="ac-combo-values" id="ac-event-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-event-panel" role="listbox" hidden></div>
                </div>
              </div>
            </div>

            <!-- รายละเอียดกับรูปปกเป็นข้อมูล "หน้าตาของกิจกรรม" ชุดเดียวกัน วางคู่กันให้เห็นพร้อมกัน -->
            <div class="ac-field-row is-even">
              <div class="ac-field ac-field-half">
                <div class="ac-label-row">
                  <label class="form-label" for="ac-detail">รายละเอียด<span class="form-required">*</span></label>
                  <span class="ac-counter" id="ac-detail-count">0/500</span>
                </div>
                <textarea class="input ac-detail-input" id="ac-detail" rows="5" maxlength="500" placeholder="อธิบายสั้นๆ ว่าผู้เข้าร่วมจะได้เรียนรู้หรือได้ทำอะไรบ้าง"></textarea>
              </div>

              <div class="ac-field ac-field-half">
                <label class="form-label">รูปภาพปก<span class="form-required">*</span></label>
                <div class="ac-cover is-stacked" id="ac-cover">
                  <img class="ac-cover-preview-image" id="ac-cover-preview-image" alt="" aria-hidden="true" hidden>
                  <span class="ac-cover-icon">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="8.5" cy="9.5" r="1.8"/><path d="M4 17l5-5 4 4 3-2 4 4"/></svg>
                  </span>
                  <span class="ac-cover-text">
                    <span class="ac-cover-headline" id="ac-cover-headline">ลากไฟล์มาวาง หรือเลือกจากเครื่อง</span>
                    <span class="ac-cover-hint">อัตราส่วน 16:9 · JPG/PNG ไม่เกิน 5MB</span>
                  </span>
                  <span class="ac-cover-actions">
                    <button type="button" class="btn btn-ghost btn-sm" id="ac-cover-remove" hidden>ลบรูป</button>
                    <button type="button" class="btn btn-outline btn-sm" id="ac-cover-pick">เลือกไฟล์</button>
                  </span>
                </div>
              </div>
            </div>
          </section>

          <!-- ---------- 2. จัดที่ไหน เมื่อไหร่ ---------- -->
          <section class="card ac-section">
            <div class="ac-section-head">
              <span class="ac-section-no">2</span>
              <h2 class="ac-section-title">จัดที่ไหน เมื่อไหร่</h2>
            </div>

            <div class="ac-field-row">
              <div class="ac-field ac-field-half">
                <label class="form-label">สถานที่จัด<span class="form-required">*</span></label>
                <div class="ac-combo" id="ac-place-combo">
                  <div class="ac-combo-control" id="ac-place-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox" aria-label="สถานที่จัด">
                    <span class="ac-combo-values" id="ac-place-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-place-panel" role="listbox" hidden></div>
                </div>
              </div>
              <div class="ac-field ac-field-half">
                <label class="form-label">รูปแบบ</label>
                <div class="ac-combo" id="ac-mode-combo">
                  <div class="ac-combo-control" id="ac-mode-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox" aria-label="รูปแบบ">
                    <span class="ac-combo-values" id="ac-mode-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-mode-panel" role="listbox" hidden></div>
                </div>
              </div>
            </div>

            <!-- วิทยากรเลือกได้อิสระจากรายชื่อทั้งหมด ไม่ผูกกับหลักสูตร — วางคู่กันไว้เพราะยังเป็นข้อมูล "จัดที่ไหน เมื่อไหร่" ชุดเดียวกัน -->
            <div class="ac-field-row">
              <div class="ac-field ac-field-half">
                <div class="ac-label-row">
                  <label class="form-label">หลักสูตรการเรียนรู้</label>
                </div>
                <div class="ac-combo" id="ac-course-combo">
                  <div class="ac-combo-control" id="ac-course-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox">
                    <span class="ac-combo-values" id="ac-course-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-course-panel" role="listbox" hidden></div>
                </div>
              </div>

              <div class="ac-field ac-field-half">
                <div class="ac-label-row">
                  <label class="form-label">วิทยากร</label>
                  <span class="ac-hint">เลือกได้อิสระ ไม่จำกัดตามหลักสูตร</span>
                </div>
                <div class="ac-combo" id="ac-host-combo">
                  <div class="ac-combo-control" id="ac-host-control" role="button" tabindex="0" aria-expanded="false" aria-haspopup="listbox">
                    <span class="ac-combo-values" id="ac-host-values"></span>
                    <svg class="ac-combo-caret" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"><path d="M6 9l6 6 6-6"/></svg>
                  </div>
                  <div class="ac-combo-panel" id="ac-host-panel" role="listbox" hidden></div>
                </div>
              </div>
            </div>

            <div class="ac-field">
              <div class="ac-label-row">
                <label class="form-label">รอบกิจกรรม<span class="form-required">*</span></label>
                <span class="ac-hint">สูงสุด 5 รอบ</span>
              </div>
              <div class="ac-slots" id="ac-slots"></div>
              <button type="button" class="btn btn-outline btn-sm ac-add-slot" id="ac-add-slot">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><path d="M12 5.5v13M5.5 12h13"/></svg>
                เพิ่มรอบ
              </button>
            </div>
          </section>

          <!-- ---------- 3. เงื่อนไข / แบบประเมิน (ยุบหัวข้อ "แบบประเมินที่ใช้" เดิมเข้ามา) ---------- -->
          <section class="card ac-section">
            <div class="ac-section-head">
              <span class="ac-section-no">3</span>
              <h2 class="ac-section-title">เงื่อนไข / แบบประเมิน</h2>
            </div>

            <!-- สองคำถามที่ตัดสินใจพร้อมกันเสมอ: จ่ายเงินไหม และเข้าร่วมยังไง -->
            <div class="ac-field-row">
              <div class="ac-field ac-field-half">
                <label class="form-label">ค่าเข้าร่วม<span class="form-required">*</span></label>
                <div class="ac-fee-row">
                  <div class="ac-radios" id="ac-fee" role="radiogroup" aria-label="ค่าเข้าร่วม"></div>
                  <span class="ac-fee-amount" id="ac-fee-amount" hidden>
                    <input type="number" class="input" id="ac-fee-input" min="0" step="10" placeholder="0" inputmode="numeric">
                    <span class="ac-unit">บาท / คน</span>
                  </span>
                </div>
              </div>

              <div class="ac-field ac-field-half">
                <div class="ac-label-row">
                  <span class="ac-label-info">
                    <label class="form-label">รูปแบบการเข้าร่วม<span class="form-required">*</span></label>
                    <button type="button" class="ac-info" tabindex="0"
                            aria-label="ติ๊กแล้วฟิลด์ที่เกี่ยวข้องด้านล่างจะแสดงขึ้นมา · ไม่ติ๊กเลย = เข้าร่วมได้ทันที"
                            data-tip="ติ๊กแล้วฟิลด์ที่เกี่ยวข้องด้านล่างจะแสดงขึ้นมา · ไม่ติ๊กเลย = เข้าร่วมได้ทันที"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 7.6v4.9M12 15.9v.01"/></svg></button>
                  </span>
                </div>
                <div class="ac-cond-list" id="ac-reg" role="group" aria-label="รูปแบบการเข้าร่วม"></div>
              </div>
            </div>

            {{-- แบบประเมินถูกยุบเข้ามาที่นี่ เพราะเป็น "เงื่อนไขการเข้าร่วม" ชุดเดียวกัน
                 ช่องติ๊กรูปแบบการเข้าร่วมด้านบนเป็นตัวกำหนดว่าฟิลด์ไหนด้านล่างจะแสดง
                 แยกเป็นคนละหัวข้อทำให้ต้องเลื่อนจอไปมาระหว่างเหตุกับผล --}}
            <div class="ac-field" id="ac-field-form-reg">
              <div class="ac-label-row">
                <label class="form-label">ตอนลงทะเบียน<span class="form-required">*</span></label>
              </div>
              <div class="ac-picks is-stacked" id="ac-form-reg"></div>
            </div>

            <div class="ac-field" id="ac-field-form-post">
              <div class="ac-label-row">
                <label class="form-label">หลังจบกิจกรรม</label>
              </div>
              <div class="ac-picks is-stacked" id="ac-form-post"></div>
            </div>

            <div class="ac-field">
              <div class="ac-label-row">
                <span class="ac-label-info">
                <label class="form-label">ช่วงเวลาเปิด–ปิดระบบ</label>
                <button type="button" class="ac-info" tabindex="0"
                        aria-label="ระบบเติมให้อัตโนมัติจากรอบกิจกรรม — แก้ไขได้ทุกช่อง"
                        data-tip="ระบบเติมให้อัตโนมัติจากรอบกิจกรรม — แก้ไขได้ทุกช่อง"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"><circle cx="12" cy="12" r="9"/><path d="M12 8.2v.01M12 11.5v4.3"/></svg></button>
                </span>
              </div>
              <div class="ac-windows" id="ac-windows"></div>
            </div>
          </section>

          <!-- ---------- 4. การเผยแพร่ ---------- -->
          <section class="card ac-section">
            <div class="ac-section-head">
              <span class="ac-section-no">4</span>
              <h2 class="ac-section-title">การเผยแพร่</h2>
            </div>
            <div class="ac-toggles" id="ac-toggles"></div>
          </section>
        </form>

        <!-- ==================== แผงขวา ==================== -->
        <aside class="ac-side" id="ac-side">
          <div class="card ac-panel">
            <div class="ac-panel-head">
              <span class="ac-panel-title">ตัวอย่างที่ผู้เข้าร่วมเห็น</span>
              <span class="badge badge-success ac-live">อัปเดตสด</span>
            </div>

            <div class="ac-preview">
              <div class="ac-preview-cover" id="ac-preview-cover">
                <img class="ac-preview-cover-image" id="ac-preview-cover-image" alt="รูปภาพปกที่ผู้เข้าร่วมเห็น" hidden>
                <span class="ac-preview-cover-text">ยังไม่ได้อัปโหลดรูปปก</span>
              </div>
              <div class="ac-preview-body">
                <div class="ac-preview-tags" id="ac-preview-tags"></div>
                <span class="ac-preview-title" id="ac-preview-title">ชื่อกิจกรรมจะแสดงตรงนี้</span>
                <div class="ac-preview-meta" id="ac-preview-meta"></div>
                <button type="button" class="btn btn-primary btn-block btn-sm" disabled>ลงทะเบียนเข้าร่วม</button>
              </div>
            </div>
          </div>

          <div class="card ac-panel">
            <div class="ac-panel-head">
              <span class="ac-panel-title">QR และลิงก์</span>
            </div>
            <span class="ac-panel-desc" id="ac-qr-hint"></span>
            <div class="ac-qr-list" id="ac-qr-list"></div>
            <button type="button" class="btn btn-outline btn-block btn-sm" id="ac-qr-all" disabled>ดาวน์โหลด QR ทั้งหมด</button>
          </div>

          <div class="card ac-panel">
            <div class="ac-panel-head">
              <span class="ac-panel-title">ก่อนเผยแพร่</span>
              <span class="ac-done-badge" id="ac-done-badge">0/9</span>
            </div>
            <div class="ac-checklist" id="ac-checklist"></div>
          </div>
        </aside>
      </div>
@endsection

@section('after-content')
    <!-- ==================== แถบปุ่มล่าง ==================== -->
    <div class="ac-bottombar">
      <div class="ac-bottombar-inner">
        <div class="ac-progress-wrap">
          <div class="ac-progress"><span id="ac-progress-fill"></span></div>
          <span class="ac-progress-text" id="ac-progress-text"></span>
        </div>
        <div class="ac-bottombar-actions">
          <a class="btn btn-ghost" href="{{ route('admin.activities.index') }}" id="ac-cancel">ยกเลิก</a>
          <button type="button" class="btn btn-outline" id="ac-save-draft">บันทึกร่าง</button>
          <button type="button" class="btn btn-primary" id="ac-publish" disabled>บันทึกและเผยแพร่</button>
        </div>
      </div>
    </div>
@endsection

@push('scripts')
{{-- ต้องมาก่อน activity-create.js เพราะไฟล์นั้นอ่าน mock ตั้งแต่ตอนโหลดเพื่อสร้างรายการตัวเลือก --}}
<script>
(function () {
  var mock = window.TFC_MOCK;

  /* สะพานชั่วคราว: ทับข้อมูลจำลองด้วยข้อมูลจริงจากฐาน
     ฟอร์มยังอ้างอิงทุกอย่างด้วย "ชื่อ" ไม่ใช่ id จึงต้องส่งตารางแปลงชื่อ->id มาให้ด้วย */
  mock.areas = @json($areas);
  mock.targetGroups = @json($targetGroups);
  mock.activityFormats = @json($formats);
  mock.activityTypes = @json($types);
  mock.activityVenueModes = @json($venueModes);
  mock.programCatalog = @json($catalog);
  mock.activities = @json($activities);
  mock.activitySessions = @json($sessions);
  mock.selectableEvents = @json($events);
  mock.evaluationForms = @json($evaluationForms);

  window.TFC_ACTIVITY_EDIT_ID = @json($activity->code);
  window.TFC_ACTIVITY_IS_NEW = @json($isCreate);
  window.TFC_ACTIVITY_LOOKUP = @json($lookup);
  window.TFC_ACTIVITY_CURRENT = @json($current);
  window.TFC_ACTIVITY_QR = @json($qrCodes);
})();
</script>
<script src="@assetv('assets/js/activity-module.js')"></script>
@endpush

@push('page-script')
{{-- ลำดับต้องตรงกับหน้า static เดิมเป๊ะ: app.js → datetime-picker.js → activity-create.js
     app.js ถูกวางไว้ใน layout ก่อน @stack('page-script') อยู่แล้ว
     สลับลำดับเมื่อไหร่ ช่องวันที่จะไม่ถูกแปลงเป็นปฏิทินแบบ popup --}}
<script src="@assetv('assets/js/datetime-picker.js')"></script>
<script src="@assetv('assets/js/activity-create.js')"></script>
<script>
(function () {
  var form = window.TFC.activityForm;
  var lookup = window.TFC_ACTIVITY_LOOKUP;
  var current = window.TFC_ACTIVITY_CURRENT;
  var PLACE_EMPTY = 'เลือกพื้นที่ดำเนินงาน';

  if (!form) return;

  /* แปลงชื่อเป็น id — ชื่อที่หาไม่เจอต้องดังพอให้เห็น ไม่ใช่กลายเป็น null เงียบ ๆ
     เพราะถ้า master data ถูกแก้ชื่อ ข้อมูลจะหายไปจากฟอร์มโดยไม่มีใครรู้ */
  var missing = [];

  function idOf(group, name) {
    if (!name) return null;
    var groupMap = lookup[group] || {};
    var id = groupMap[name];
    if (!id) {
      var cleanName = String(name).trim();
      id = groupMap[cleanName];

      if (!id) {
        var keys = Object.keys(groupMap);
        for (var i = 0; i < keys.length; i++) {
          if (keys[i].indexOf(cleanName) === 0 || cleanName.indexOf(keys[i]) === 0) {
            id = groupMap[keys[i]];
            break;
          }
        }
      }
    }
    if (!id) missing.push(group + ': ' + name);
    return id || null;
  }

  function idsOf(group, names) {
    return (names || []).map(function (n) { return idOf(group, n); }).filter(Boolean);
  }

  /* วิทยากรที่แสดงในฟอร์มมาจากหลักสูตรและมี id ติดมาด้วยแล้ว
     ใช้ id นี้ก่อนเพื่อไม่ให้การบันทึกขึ้นกับการเทียบชื่อ ส่วนข้อมูลเก่ายังถอยไปใช้ lookup เดิมได้ */
  function instructorIds(names) {
    var idsByName = {};
    ((window.TFC_MOCK || {}).programCatalog || []).forEach(function (program) {
      (program.courses || []).forEach(function (course) {
        (course.teachers || []).forEach(function (teacher) {
          if (teacher && typeof teacher === 'object' && teacher.id && teacher.name) {
            idsByName[teacher.name] = teacher.id;
          }
        });
      });
    });

    return (names || []).map(function (name) {
      if (idsByName[name]) return idsByName[name];
      return idOf('instructors', name);
    }).filter(Boolean);
  }

  /* ประกบช่องวันกับช่องเวลาของช่วงเวลาหนึ่งกลับเป็นค่าเดียว
     side = 'from' (ต้นช่วง) หรือ 'to' (ปลายช่วง) */
  function joinAt(win, side) {
    var date = (win || {})[side];
    var time = (win || {})[side + 'T'];
    return date && time ? date + ' ' + time + ':00' : null;
  }

  /* ฟิลด์ที่ฟอร์มไม่ได้คุม (visibility, organizer, data_source) ต้องคงค่าเดิมไว้
     จึงเริ่มจากค่าปัจจุบันของเซิร์ฟเวอร์แล้วทับเฉพาะส่วนที่ฟอร์มคุม */
  function payloadFrom(state, publish) {
    missing = [];

    var slots = (state.slots || []).filter(function (s) { return s.date && s.start && s.end; });
    var dates = slots.map(function (s) { return s.date; }).sort();
    var course = (state.courses || [])[0];
    var courseRow = course ? (lookup.courses || {})[course] : null;

    return Object.assign({}, current, {
      name: state.title,
      description: state.detail,
      type: state.kind,
      format_id: idOf('formats', (state.cats || [])[0]),
      venue_mode: state.mode,
      /* กิจกรรมเดี่ยวส่ง null — idOf จะฟ้อง missing ถ้าชื่อไม่ตรงกับรายการที่ฉีดมา */
      parent_event_id: state.kind === 'กิจกรรม' && state.parentEvent ? idOf('events', state.parentEvent) : null,
      course_id: courseRow ? courseRow.id : null,
      program_id: courseRow ? courseRow.program_id : current.program_id,

      area_ids: state.place && state.place !== PLACE_EMPTY ? idsOf('areas', [state.place]) : [],
      target_group_ids: idsOf('targetGroups', state.targets),
      instructor_ids: instructorIds(state.hosts),

      has_fee: state.fee !== 'ไม่มีค่าใช้จ่าย',
      fee: state.feeAmount ? Number(state.feeAmount) : 0,

      requires_registration: !!state.join.reg,
      requires_checkin: !!state.join.chk,
      has_post_survey: !!state.join.survey,
      registration_form_id: state.join.reg ? (state.formReg || null) : null,
      post_survey_form_id: state.join.survey ? (state.formsPost[0] || null) : null,

      start_date: dates[0] || null,
      end_date: dates[dates.length - 1] || null,
      capacity: slots.reduce(function (sum, s) { return sum + (Number(s.cap) || 0); }, 0),

      rounds: slots.map(function (s) {
        return {
          round_date: s.date,
          time_start: s.start,
          time_end: s.end,
          location: state.place !== PLACE_EMPTY ? state.place : null,
          capacity: Number(s.cap) || 0
        };
      }),

      /* ฟอร์มแยกช่องวันกับช่องเวลา ฐานข้อมูลเก็บเป็นค่าเดียว จึงต้องประกบกลับ
         ไม่ครบทั้งวันและเวลา = ถือว่าไม่ได้ตั้ง ส่ง null ไปแทนค่าครึ่ง ๆ ที่ใช้ไม่ได้ */
      registration_start_at: joinAt(state.windows.reg, 'from'),
      registration_end_at: joinAt(state.windows.reg, 'to'),
      checkin_start_at: joinAt(state.windows.chk, 'from'),
      checkin_end_at: joinAt(state.windows.chk, 'to'),
      survey_start_at: joinAt(state.windows.srv, 'from'),
      survey_end_at: joinAt(state.windows.srv, 'to'),

      is_published: publish,
      is_featured: !!state.pin,
      /* ค่าว่าง = ส่ง 0 ให้ฝั่งเซิร์ฟเวอร์ต่อท้ายรายการหน้าเว็บสาธารณะเองตอนเผยแพร่ */
      public_sort_order: state.sortOrder !== '' ? Math.max(0, parseInt(state.sortOrder, 10) || 0) : 0,
      /* กิจกรรมที่เผยแพร่อยู่แล้วไม่ควรถูกดันกลับเป็นฉบับร่างเพราะกดปุ่มบันทึกร่าง */
      status: publish
        ? (current.is_published ? current.status : 'เปิดรับสมัคร')
        : (current.is_published ? current.status : 'ฉบับร่าง')
    });
  }

  /* ---------- รูปภาพปก ----------
     ช่องเลือกไฟล์ถูกสร้างจาก JS ไม่ได้อยู่ใน markup เดิม เพราะฟอร์ม static ไม่เคยอัปโหลดจริง
     อัปทันทีที่เลือกไฟล์ ไม่รอกดบันทึก — จะได้เห็นผลเลยและไม่ต้องเปลี่ยน PUT เป็น multipart */
  /* เพดานจริงของเครื่องนี้ = ค่าที่เล็กที่สุดระหว่างกฎของแอปกับ php.ini */
  var COVER_MAX = @json($coverMaxBytes);

  var picker = document.createElement('input');
  picker.type = 'file';
  picker.accept = 'image/jpeg,image/png,image/webp';
  picker.hidden = true;
  document.body.appendChild(picker);

  /* โหมดสร้างยังไม่มีรหัสกิจกรรมให้ยิง endpoint รูปปก จึงพักไฟล์ไว้ก่อน
     แล้วอัปให้อัตโนมัติทันทีที่บันทึกครั้งแรกสำเร็จ ผู้ใช้จึงเลือกรูปได้ตั้งแต่ต้น
     และเช็กลิสต์ "รูปภาพปก" ติ๊กได้ ไม่งั้นปุ่มเผยแพร่จะกดไม่ได้ตลอดกาล */
@if ($isCreate)
  var pendingCover = null;
  var pendingCoverUrl = '';
@endif

  form.onPickCover = function () { picker.click(); };

  picker.addEventListener('change', function () {
    var file = picker.files && picker.files[0];
    if (!file) return;

    /* PHP ตัดไฟล์ที่ใหญ่เกินเพดานทิ้งก่อนถึง Laravel ฝั่งเซิร์ฟเวอร์จึงเห็นเป็น
       "ไม่ได้แนบไฟล์มา" แล้วฟ้องผิดเรื่อง — บอกให้ชัดตั้งแต่ตรงนี้ */
    if (file.size > COVER_MAX) {
      picker.value = '';
      window.TFC.showToast('รูปใหญ่เกินไป (' + (file.size / 1048576).toFixed(1) + ' MB) เครื่องนี้รับได้ไม่เกิน ' + (COVER_MAX / 1048576).toFixed(1) + ' MB', 'danger');
      return;
    }

@if ($isCreate)
    pendingCover = file;
    if (pendingCoverUrl) URL.revokeObjectURL(pendingCoverUrl);
    pendingCoverUrl = URL.createObjectURL(file);
    picker.value = '';
    form.setCover(file.name + ' · จะอัปโหลดตอนบันทึก', pendingCoverUrl);
    window.TFC.showToast('เลือกรูปปกแล้ว จะอัปโหลดให้ตอนกดบันทึกครั้งแรก', 'info');
    return;
@endif

    var previousCoverLabel = form.state.coverLabel;
    var previousCoverUrl = form.state.coverUrl;
    var selectedCoverUrl = URL.createObjectURL(file);
    form.setCover(file.name, selectedCoverUrl);

    var data = new FormData();
    data.append('cover', file);

    window.TFC.showToast('กำลังอัปโหลดรูปปก…', 'info');

    fetch('{{ $isCreate ? '' : route('admin.activities.cover.store', $activity->code) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: data
    })
      .then(readJson)
      .then(function (res) {
        URL.revokeObjectURL(selectedCoverUrl);
        form.setCover(res.label, res.url);
        window.TFC.showToast(res.message, 'success');
      })
      .catch(function (err) {
        URL.revokeObjectURL(selectedCoverUrl);
        form.setCover(previousCoverLabel, previousCoverUrl);
        window.TFC.showToast(err.message, 'danger');
      })
      /* ล้างค่าไว้เสมอ ไม่งั้นเลือกไฟล์เดิมซ้ำจะไม่เกิด event change */
      .finally(function () { picker.value = ''; });
  });

  form.onRemoveCover = function () {
@if ($isCreate)
    /* ยังไม่เคยขึ้นเซิร์ฟเวอร์ ลบทิ้งในหน้าจอก็พอ */
    pendingCover = null;
    if (pendingCoverUrl) URL.revokeObjectURL(pendingCoverUrl);
    pendingCoverUrl = '';
    form.setCover('', '');
    return;
@endif

    fetch('{{ $isCreate ? '' : route('admin.activities.cover.destroy', $activity->code) }}', {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json',
        'X-HTTP-Method-Override': 'DELETE'
      }
    })
      .then(readJson)
      .then(function (res) {
        form.setCover('', '');
        window.TFC.showToast(res.message, 'success');
      })
      .catch(function (err) { window.TFC.showToast(err.message, 'danger'); });
  };

  /* อ่านคำตอบเป็น JSON แล้วโยน error พร้อมข้อความไทยถ้าไม่สำเร็จ ใช้ร่วมกันทุกคำขอในหน้านี้ */
  function readJson(res) {
    return res.json().catch(function () { return {}; }).then(function (data) {
      if (res.ok) return data;

      var msg = data.errors
        ? Object.keys(data.errors).map(function (k) { return data.errors[k][0]; }).join(' · ')
        : (data.message || 'ทำรายการไม่สำเร็จ');

      throw new Error(msg);
    });
  }

  /* ส่งรูปที่พักไว้ขึ้นกิจกรรมที่เพิ่งสร้าง — ไม่มีรูปพักไว้ก็ผ่านไปเฉย ๆ
     รอให้เสร็จก่อนย้ายหน้า ไม่งั้นการย้ายหน้าจะตัดคำขอกลางคัน */
  function uploadPendingCover(code) {
@if ($isCreate)
    if (!pendingCover) return Promise.resolve();

    var data = new FormData();
    data.append('cover', pendingCover);

    return fetch('{{ route('admin.activities.cover.store', '__CODE__') }}'.replace('__CODE__', code), {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Accept': 'application/json'
      },
      body: data
    }).then(readJson);
@else
    return Promise.resolve();
@endif
  }

  form.onSubmit = function (state, opts) {
    var body = payloadFrom(state, !!opts.publish);

    if (missing.length) {
      window.TFC.showToast('ข้อมูลอ้างอิงไม่ตรงกับระบบ: ' + missing.join(' · '), 'danger');
      return;
    }

    form.setBusy(true);

    var reqMethod = @json($isCreate ? 'POST' : 'PUT');
    var reqHeaders = {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      'Accept': 'application/json',
      'Content-Type': 'application/json'
    };
    if (reqMethod !== 'POST') {
      reqHeaders['X-HTTP-Method-Override'] = reqMethod;
    }

    fetch(@json($isCreate ? route('admin.activities.store') : route('admin.activities.update', $activity->code)), {
      method: 'POST',
      headers: reqHeaders,
      body: JSON.stringify(body)
    })
      .then(function (res) {
        return res.json().catch(function () { return {}; }).then(function (data) {
          if (res.ok) return data;
          /* 422 ส่งรายการ error รายฟิลด์มา รวมเป็นข้อความเดียวให้ผู้ใช้เห็นว่าต้องแก้อะไรบ้าง */
          var msg = data.errors
            ? Object.keys(data.errors).map(function (k) { return data.errors[k][0]; }).join(' · ')
            : (data.message || 'บันทึกไม่สำเร็จ');
          throw new Error(msg);
        });
      })
      .then(function (data) {
        form.markSaved();
        Object.assign(current, body);

        /* โหมดแก้ไขไม่ redirect (ดู ActivityController::update) จึงต้องขึ้น toast เองตรงนี้
           ไม่งั้นกดบันทึกแล้วดูเหมือนไม่มีอะไรเกิดขึ้น ทั้งที่บันทึกสำเร็จแล้ว */
        if (!data.redirect) {
          window.TFC.showToast(data.message || 'บันทึกสำเร็จ', 'success');
          return;
        }

        /* สร้างเสร็จแล้วต้องย้ายไปหน้าแก้ไขของรหัสที่เพิ่งได้ ไม่งั้นกดบันทึกซ้ำ
           จะสร้างกิจกรรมใหม่อีกใบ — ส่งรูปที่พักไว้ตามไปก่อนแล้วค่อยย้ายหน้า */
        return uploadPendingCover(data.code).then(function () {
          try {
            sessionStorage.setItem('tfc-activity-success', 'บันทึกสำเร็จ');
          } catch (e) { /* ถ้าเบราว์เซอร์ปิด sessionStorage ยังกลับหน้ารายการได้ตามปกติ */ }
          window.location.assign(data.redirect);
        });
      })
      .catch(function (err) {
        window.TFC.showToast(err.message, 'danger');
      })
      .finally(function () { form.setBusy(false); });
  };
})();
</script>
@endpush

@push('page-script')
<script>
(function () {
  var KEY = 'tfc-ac-side-hidden';
  var layout = document.querySelector('.ac-layout');
  var button = document.getElementById('ac-side-toggle');
  var label = document.getElementById('ac-side-toggle-text');

  if (!layout || !button) return;

  /* คลาสถูกใส่ไว้ตั้งแต่ก่อนวาดเฟรมแรกแล้ว ตรงนี้แค่ทำให้ปุ่มบอกสถานะให้ตรงกัน */
  function sync() {
    var hidden = layout.classList.contains('is-side-hidden');
    label.textContent = hidden ? 'แสดงแถบขวา' : 'ซ่อนแถบขวา';
    button.setAttribute('aria-expanded', String(!hidden));
  }

  button.addEventListener('click', function () {
    var hidden = layout.classList.toggle('is-side-hidden');
    try { localStorage.setItem(KEY, hidden ? '1' : '0'); } catch (e) { /* โหมดส่วนตัวเขียนไม่ได้ ไม่ต้องทำอะไร */ }
    sync();
  });

  sync();
})();
</script>
@endpush
